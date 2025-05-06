<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils; // Add this use statement

class SecurityController extends AbstractController
{
    // Remove MessageBusInterface dependency if no longer needed elsewhere
    public function __construct(
        private readonly FormFactoryInterface $formFactory
    ) {
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])] // Re-allow POST
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Get the login error if there is one (handled by security bundle)
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user (handled by security bundle)
        $lastUsername = $authenticationUtils->getLastUsername();

        // Create the form (but don't handle request or check submission)
        // Pre-fill email using the last username entered
        $form = $this->formFactory->create(LoginType::class, ['email' => $lastUsername]);

        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
            // Optionally pass last username if needed in Twig:
            // 'last_username' => $lastUsername
        ]);
    }

    // Add logout method if needed, e.g.:
     #[Route(path: '/logout', name: 'app_logout')]
     public function logout(): void
     {
         throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
     }
}
