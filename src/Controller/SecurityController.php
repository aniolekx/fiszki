<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\LoginType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormFactoryInterface;
use App\Form\RegistrationType;
use App\Application\User\Command\RegisterUserCommand;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Security\AppAuthenticator; // Assuming AppAuthenticator is your main authenticator
use App\Repository\DoctrineUserRepository; // Inject UserRepository
use Psr\Log\LoggerInterface; // Inject LoggerInterface

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly MessageBusInterface $messageBus,
        private readonly UserAuthenticatorInterface $userAuthenticator,
        private readonly AppAuthenticator $appAuthenticator, // Inject your authenticator
        private readonly DoctrineUserRepository $userRepository, // Inject UserRepository
        private readonly LoggerInterface $logger // Inject LoggerInterface
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

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->formFactory->create(RegistrationType::class);

        // Pobierz surowe dane POST
        $requestData = $request->request->all();

        // Dodatkowe sprawdzenie struktury surowych danych żądania
        if ($request->isMethod('POST')) { // Sprawdź, czy to na pewno żądanie POST
            if (!isset($requestData['registration']) || !is_array($requestData['registration']) ||
                !isset($requestData['registration']['email'], $requestData['registration']['password'], $requestData['registration']['agreeTerms'])) {

                // Jeśli struktura danych jest niepoprawna, dodaj komunikat błędu i przerwij
                 $this->addFlash('danger', 'Wystąpił błąd podczas przetwarzania danych formularza. Spróbuj ponownie.');
                 // Można tu również dodać logowanie błędu
                 // $this->logger->error('Invalid raw form data structure in registration request.');

                // Renderuj formularz (bez przetwarzania niepoprawnych danych)
                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }
        }

        // Jeśli surowe dane wyglądają poprawnie lub to żądanie GET, pozwól formularzowi je przetworzyć
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData(); // Teraz $data powinno być bezpieczne i zawierać poprawne dane

            // Access data for individual fields, especially unmapped ones
            $email = $form->get('email')->getData();
            $password = $form->get('password')->getData();
            $agreeTerms = $form->get('agreeTerms')->getData();

            $command = new RegisterUserCommand(
                $email,
                $password,
                $agreeTerms
            );

            try {
                $this->messageBus->dispatch($command);

                $this->addFlash('success', 'Registration successful! Please check your email to confirm your account.');

                return $this->redirectToRoute('app_check_email');

            } catch (\DomainException $e) {
                // Handle validation errors from the command handler
                $this->addFlash('danger', $e->getMessage());
            } catch (\Exception $e) {
                // Handle other potential errors
                $this->addFlash('danger', 'An error occurred during registration.');
                // Log the error for debugging
                $this->logger->error('Registration Error: ' . $e->getMessage(), ['exception' => $e]);
            }
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(string $token, EntityManagerInterface $em): Response
    {
        $user = $this->userRepository->findOneBy(['confirmationToken' => $token]);

        if (!$user) {
            // Handle invalid or expired token
            $this->addFlash('danger', 'Invalid or expired confirmation token.');
            return $this->redirectToRoute('app_register');
        }

        // Assuming token is valid (no expiration check implemented yet)
        $user->setIsConfirmed(true);
        $user->setConfirmationToken(null);

        $em->flush(); // Persist the changes

        $this->addFlash('success', 'Your email address has been confirmed. You can now log in.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/check-email', name: 'app_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('security/check_email.html.twig');
    }
}
