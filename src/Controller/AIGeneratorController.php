<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Entity\Flashcard;
use App\Entity\GenerationSession;
use App\Entity\AIUsageLog;
use App\Form\AIGenerateType;
use App\Repository\DeckRepository;
use App\Repository\GenerationSessionRepository;
use App\Repository\SystemSettingsRepository;
use App\Service\AIFlashcardServiceEnhanced;
use App\Exception\OpenAIException;
use App\Service\CreditService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ai')]
#[IsGranted('ROLE_USER')]
class AIGeneratorController extends AbstractController
{
    public function __construct(
        private AIFlashcardServiceEnhanced $aiService,
        private EntityManagerInterface $entityManager,
        private GenerationSessionRepository $sessionRepository,
        private DeckRepository $deckRepository,
        private CreditService $creditService,
        private SystemSettingsRepository $settingsRepository,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/generate', name: 'app_ai_generate', methods: ['GET', 'POST'])]
    public function generate(Request $request): Response
    {
        $user = $this->getUser();
        $decks = $this->deckRepository->findBy(['user' => $user]);
        
        $form = $this->createForm(AIGenerateType::class, null, [
            'decks' => $decks
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Sprawdz czy uzytkownik ma wystarczajaco kredytow
            $aiCost = (int) $this->settingsRepository->getValue('ai_generation_cost', 100);
            
            if (!$this->creditService->hasEnoughCredits($user, $aiCost)) {
                $userCredits = $this->creditService->getUserCredits($user);
                $this->addFlash('error', sprintf(
                    'Niewystarczająca liczba kredytów. Potrzebujesz %d kredytów, masz %d.',
                    $aiCost,
                    $userCredits->getBalance()
                ));
                return $this->redirectToRoute('app_ai_generate');
            }
            
            // Create generation session
            $session = new GenerationSession();
            $session->setUser($user);
            $session->setInputText($data['text']);
            $session->setDeck($data['deck']);
            $session->setStatus(GenerationSession::STATUS_PROCESSING);
            
            $this->entityManager->persist($session);
            $this->entityManager->flush();
            
            try {
                // Check service availability first
                if (!$this->aiService->checkAvailability()) {
                    throw new OpenAIException(
                        'Serwis AI jest tymczasowo niedostępny',
                        OpenAIException::ERROR_SERVICE_UNAVAILABLE
                    );
                }
                
                // Estimate cost first
                $costEstimate = $this->aiService->estimateCost($data['text']);
                
                // Generate flashcards
                $flashcards = $this->aiService->generateFlashcards($data['text'], $user->getId());
                
                // Pobierz kredyty
                $this->creditService->chargeCredits(
                    $user, 
                    $aiCost, 
                    sprintf('Generacja fiszek AI (sesja #%d)', $session->getId()),
                    ['session_id' => $session->getId()]
                );
                
                // Zapisz log uzycia AI
                $aiLog = new AIUsageLog(
                    $user,
                    $costEstimate['tokens'] ?? 0,
                    $aiCost,
                    $session
                );
                $aiLog->setModel($costEstimate['model'] ?? 'gpt-3.5-turbo');
                $aiLog->setPrompt(substr($data['text'], 0, 1000));
                $aiLog->setEstimatedCost($costEstimate['estimated_cost'] ?? 0.002);
                $this->entityManager->persist($aiLog);
                
                // Update session with results
                $session->setGeneratedFlashcards($flashcards);
                $session->setStatus(GenerationSession::STATUS_COMPLETED);
                $session->setModel($costEstimate['model']);
                
                $this->entityManager->flush();
                
                // Redirect to review page
                return $this->redirectToRoute('app_ai_review', ['id' => $session->getId()]);
                
            } catch (OpenAIException $e) {
                $session->setStatus(GenerationSession::STATUS_FAILED);
                $session->setErrorMessage($e->getMessage());
                $this->entityManager->flush();
                
                // Zwróć kredyty jesli generacja się nie powiodła i kredyty zostały pobrane
                if (isset($aiCost) && $session->getStatus() === GenerationSession::STATUS_PROCESSING) {
                    $this->creditService->refundCredits(
                        $user,
                        $aiCost,
                        'Błąd generacji AI: ' . $e->getErrorType()
                    );
                }
                
                // User-friendly error message
                $this->addFlash('error', $e->getUserMessage());
                
                // Log detailed error for debugging
                $this->logger->error('OpenAI generation failed', [
                    'error_type' => $e->getErrorType(),
                    'message' => $e->getMessage(),
                    'user_id' => $user->getId(),
                    'session_id' => $session->getId()
                ]);
                
                // If rate limited, show retry info
                if ($e->isRetryable() && $e->getRetryAfter()) {
                    $this->addFlash('warning', sprintf(
                        'Możesz spróbować ponownie za %d sekund.',
                        $e->getRetryAfter()
                    ));
                }
                
            } catch (\Exception $e) {
                $session->setStatus(GenerationSession::STATUS_FAILED);
                $session->setErrorMessage($e->getMessage());
                $this->entityManager->flush();
                
                // Zwróć kredyty jesli generacja się nie powiodła
                if (isset($aiCost)) {
                    $this->creditService->refundCredits(
                        $user,
                        $aiCost,
                        'Błąd generacji AI'
                    );
                }
                
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd. Spróbuj ponownie później.');
                
                $this->logger->critical('Unexpected error during AI generation', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'user_id' => $user->getId()
                ]);
            }
        }

        $userCredits = $this->creditService->getUserCredits($user);
        $aiCost = (int) $this->settingsRepository->getValue('ai_generation_cost', 100);
        
        return $this->render('ai_generator/generate.html.twig', [
            'form' => $form,
            'recent_sessions' => $this->sessionRepository->findBy(
                ['user' => $user],
                ['createdAt' => 'DESC'],
                5
            ),
            'user_credits' => $userCredits->getBalance(),
            'generation_cost' => $aiCost,
            'can_generate' => $userCredits->hasEnoughCredits($aiCost)
        ]);
    }

    #[Route('/review/{id}', name: 'app_ai_review', methods: ['GET', 'POST'])]
    public function review(GenerationSession $session, Request $request): Response
    {
        // Ensure user owns this session
        if ($session->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $selectedFlashcards = $request->request->all('flashcards') ?? [];
            
            if ($action === 'save' && !empty($selectedFlashcards)) {
                $deck = $session->getDeck();
                
                // If no deck selected, create a new one
                if (!$deck) {
                    $deck = new Deck();
                    $deck->setName('Wygenerowane ' . date('Y-m-d H:i'));
                    $deck->setDescription('Fiszki wygenerowane przez AI');
                    $deck->setUser($this->getUser());
                    $this->entityManager->persist($deck);
                }
                
                $acceptedFlashcards = [];
                $generatedFlashcards = $session->getGeneratedFlashcards();
                
                foreach ($selectedFlashcards as $index) {
                    if (isset($generatedFlashcards[$index])) {
                        $flashcardData = $generatedFlashcards[$index];
                        
                        // Check if user modified the flashcard
                        $front = $request->request->get("front_$index", $flashcardData['front']);
                        $back = $request->request->get("back_$index", $flashcardData['back']);
                        
                        $flashcard = new Flashcard();
                        $flashcard->setFront($front);
                        $flashcard->setBack($back);
                        $flashcard->setDeck($deck);
                        
                        $this->entityManager->persist($flashcard);
                        
                        $acceptedFlashcards[] = [
                            'front' => $front,
                            'back' => $back
                        ];
                    }
                }
                
                // Update session with accepted flashcards
                $session->setAcceptedFlashcards($acceptedFlashcards);
                $this->entityManager->flush();
                
                $this->addFlash('success', sprintf(
                    'Zapisano %d fiszek do talii "%s"',
                    count($acceptedFlashcards),
                    $deck->getName()
                ));
                
                return $this->redirectToRoute('app_deck_show', ['id' => $deck->getId()]);
            }
        }

        return $this->render('ai_generator/review.html.twig', [
            'session' => $session,
            'flashcards' => $session->getGeneratedFlashcards()
        ]);
    }

    #[Route('/history', name: 'app_ai_history', methods: ['GET'])]
    public function history(): Response
    {
        $sessions = $this->sessionRepository->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('ai_generator/history.html.twig', [
            'sessions' => $sessions
        ]);
    }

    #[Route('/estimate-cost', name: 'app_ai_estimate_cost', methods: ['POST'])]
    public function estimateCost(Request $request): JsonResponse
    {
        $text = $request->request->get('text', '');
        
        if (empty($text)) {
            return $this->json(['error' => 'No text provided'], 400);
        }
        
        try {
            $estimate = $this->aiService->estimateCost($text);
            return $this->json($estimate);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}