<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Entity\FlashcardProgress;
use App\Entity\StudySession;
use App\Repository\DeckRepository;
use App\Repository\FlashcardProgressRepository;
use App\Repository\StudySessionRepository;
use App\Service\SpacedRepetitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/study')]
#[IsGranted('ROLE_USER')]
class StudyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpacedRepetitionService $spacedRepetitionService,
        private FlashcardProgressRepository $progressRepository,
        private StudySessionRepository $sessionRepository,
        private DeckRepository $deckRepository
    ) {
    }

    #[Route('/', name: 'app_study_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $decks = $this->deckRepository->findBy(['user' => $user]);
        
        $deckStats = [];
        foreach ($decks as $deck) {
            $flashcards = $deck->getFlashcards();
            $dueCount = 0;
            $newCount = 0;
            
            foreach ($flashcards as $flashcard) {
                $progress = $this->progressRepository->findOneBy([
                    'user' => $user,
                    'flashcard' => $flashcard
                ]);
                
                if (!$progress) {
                    $newCount++;
                } elseif ($progress->isDue()) {
                    $dueCount++;
                }
            }
            
            $deckStats[] = [
                'deck' => $deck,
                'totalCards' => count($flashcards),
                'dueCards' => $dueCount,
                'newCards' => $newCount
            ];
        }

        $recentSessions = $this->sessionRepository->findBy(
            ['user' => $user],
            ['startedAt' => 'DESC'],
            5
        );

        return $this->render('study/index.html.twig', [
            'deckStats' => $deckStats,
            'recentSessions' => $recentSessions
        ]);
    }

    #[Route('/deck/{id}/start', name: 'app_study_start', methods: ['GET', 'POST'])]
    public function startSession(Deck $deck, Request $request): Response
    {
        $user = $this->getUser();
        
        // Verify user owns this deck
        if ($deck->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // Get or create progress for all flashcards in deck
        $flashcards = $deck->getFlashcards();
        $progresses = [];
        
        foreach ($flashcards as $flashcard) {
            $progress = $this->progressRepository->findOneBy([
                'user' => $user,
                'flashcard' => $flashcard
            ]);
            
            if (!$progress) {
                $progress = new FlashcardProgress();
                $progress->setUser($user);
                $progress->setFlashcard($flashcard);
                $this->entityManager->persist($progress);
            }
            
            $progresses[] = $progress;
        }
        
        $this->entityManager->flush();

        // Get due cards
        $dueCards = $this->spacedRepetitionService->getDueCards($progresses);
        
        if (empty($dueCards)) {
            $this->addFlash('info', 'Nie masz żadnych fiszek do powtórki w tej talii!');
            return $this->redirectToRoute('app_study_index');
        }

        // Create new study session
        $session = new StudySession();
        $session->setUser($user);
        $session->setDeck($deck);
        $session->setTotalCards(count($dueCards));
        $session->setStatus('in_progress');
        
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        // Store due cards in session
        $cardIds = array_map(fn($p) => $p->getId(), $dueCards);
        $request->getSession()->set('study_cards', $cardIds);
        $request->getSession()->set('study_index', 0);
        $request->getSession()->set('study_session_id', $session->getId());

        return $this->redirectToRoute('app_study_card');
    }

    #[Route('/card', name: 'app_study_card', methods: ['GET'])]
    public function studyCard(Request $request): Response
    {
        $sessionData = $request->getSession();
        $cardIds = $sessionData->get('study_cards', []);
        $currentIndex = $sessionData->get('study_index', 0);
        $sessionId = $sessionData->get('study_session_id');

        if (empty($cardIds) || $currentIndex >= count($cardIds) || !$sessionId) {
            return $this->redirectToRoute('app_study_complete');
        }

        $progress = $this->progressRepository->find($cardIds[$currentIndex]);
        if (!$progress || $progress->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_study_index');
        }

        $session = $this->sessionRepository->find($sessionId);

        return $this->render('study/card.html.twig', [
            'progress' => $progress,
            'flashcard' => $progress->getFlashcard(),
            'currentCard' => $currentIndex + 1,
            'totalCards' => count($cardIds),
            'session' => $session,
            'qualityDescriptions' => [
                0 => $this->spacedRepetitionService->getQualityDescription(0),
                1 => $this->spacedRepetitionService->getQualityDescription(1),
                2 => $this->spacedRepetitionService->getQualityDescription(2),
                3 => $this->spacedRepetitionService->getQualityDescription(3),
                4 => $this->spacedRepetitionService->getQualityDescription(4),
                5 => $this->spacedRepetitionService->getQualityDescription(5),
            ]
        ]);
    }

    #[Route('/card/answer', name: 'app_study_answer', methods: ['POST'])]
    public function answerCard(Request $request): JsonResponse
    {
        $sessionData = $request->getSession();
        $cardIds = $sessionData->get('study_cards', []);
        $currentIndex = $sessionData->get('study_index', 0);
        $sessionId = $sessionData->get('study_session_id');
        
        if (empty($cardIds) || $currentIndex >= count($cardIds)) {
            return new JsonResponse(['success' => false, 'error' => 'No cards in session']);
        }

        $quality = $request->request->getInt('quality');
        $progressId = $cardIds[$currentIndex];
        $progress = $this->progressRepository->find($progressId);
        
        if (!$progress || $progress->getUser() !== $this->getUser()) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid card']);
        }

        // Update progress with spaced repetition algorithm
        $this->spacedRepetitionService->calculateNextReview($progress, $quality);
        
        // Update study session
        $session = $this->sessionRepository->find($sessionId);
        if ($session) {
            $session->incrementReviewedCards();
            if ($quality >= 3) {
                $session->incrementCorrectAnswers();
            }
            $progress->setStudySession($session);
        }
        
        $this->entityManager->flush();

        // Move to next card
        $sessionData->set('study_index', $currentIndex + 1);

        $hasNext = ($currentIndex + 1) < count($cardIds);

        return new JsonResponse([
            'success' => true,
            'hasNext' => $hasNext,
            'nextUrl' => $hasNext ? $this->generateUrl('app_study_card') : $this->generateUrl('app_study_complete')
        ]);
    }

    #[Route('/complete', name: 'app_study_complete', methods: ['GET'])]
    public function completeSession(Request $request): Response
    {
        $sessionData = $request->getSession();
        $sessionId = $sessionData->get('study_session_id');
        
        if (!$sessionId) {
            return $this->redirectToRoute('app_study_index');
        }

        $session = $this->sessionRepository->find($sessionId);
        if ($session && $session->getUser() === $this->getUser()) {
            $session->setCompletedAt(new \DateTime());
            $session->setStatus('completed');
            $this->entityManager->flush();
        }

        // Clear session data
        $sessionData->remove('study_cards');
        $sessionData->remove('study_index');
        $sessionData->remove('study_session_id');

        return $this->render('study/complete.html.twig', [
            'session' => $session
        ]);
    }

    #[Route('/history', name: 'app_study_history', methods: ['GET'])]
    public function history(): Response
    {
        $user = $this->getUser();
        $sessions = $this->sessionRepository->findBy(
            ['user' => $user],
            ['startedAt' => 'DESC'],
            20
        );

        return $this->render('study/history.html.twig', [
            'sessions' => $sessions
        ]);
    }
}