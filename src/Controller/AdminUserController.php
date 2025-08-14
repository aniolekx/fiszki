<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CreditTransaction;
use App\Entity\User;
use App\Entity\UserCredits;
use App\Repository\DoctrineUserRepository;
use App\Repository\UserCreditsRepository;
use App\Repository\CreditTransactionRepository;
use App\Repository\AIUsageLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DoctrineUserRepository $userRepository,
        private readonly UserCreditsRepository $creditsRepository,
        private readonly CreditTransactionRepository $transactionRepository,
        private readonly AIUsageLogRepository $aiUsageRepository
    ) {
    }

    #[Route('/', name: 'app_admin_users')]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();
        
        $userData = [];
        foreach ($users as $user) {
            $credits = $this->creditsRepository->findByUser($user);
            $tokensUsed = $this->aiUsageRepository->getTotalTokensByUser($user);
            
            $userData[] = [
                'user' => $user,
                'credits' => $credits,
                'tokens_used' => $tokensUsed,
            ];
        }

        return $this->render('admin/users/index.html.twig', [
            'users_data' => $userData,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show')]
    public function show(User $user): Response
    {
        $credits = $this->creditsRepository->findByUser($user);
        $transactions = $this->transactionRepository->findByUser($user, 20);
        $tokensUsed = $this->aiUsageRepository->getTotalTokensByUser($user);

        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'credits' => $credits,
            'transactions' => $transactions,
            'tokens_used' => $tokensUsed,
        ]);
    }

    #[Route('/{id}/add-credits', name: 'app_admin_user_add_credits', methods: ['POST'])]
    public function addCredits(User $user, Request $request): Response
    {
        $amount = (int) $request->request->get('amount', 0);
        $description = $request->request->get('description', 'Kredyty dodane przez administratora');

        if ($amount <= 0) {
            $this->addFlash('error', 'Kwota musi być większa od zera');
            return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
        }

        $credits = $this->creditsRepository->findByUser($user);
        
        if (!$credits) {
            $credits = new UserCredits($user, 0);
            $this->entityManager->persist($credits);
        }

        $credits->addCredits($amount);
        
        $transaction = new CreditTransaction(
            $user,
            CreditTransaction::TYPE_ADMIN_GRANT,
            $amount,
            $credits->getBalance(),
            $description,
            $this->getUser()
        );
        
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Dodano %d kredytów dla użytkownika %s', $amount, $user->getEmail()));

        return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/toggle-admin', name: 'app_admin_user_toggle_admin', methods: ['POST'])]
    public function toggleAdmin(User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Nie możesz zmienić własnych uprawnień administratora');
            return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
        }

        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $roles = array_diff($roles, ['ROLE_ADMIN']);
            $message = 'Usunięto uprawnienia administratora';
        } else {
            $roles[] = 'ROLE_ADMIN';
            $message = 'Nadano uprawnienia administratora';
        }
        
        $user->setRoles(array_values($roles));
        $this->entityManager->flush();

        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
    }
}