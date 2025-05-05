<?php

declare(strict_types=1);

namespace App\Security;

use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security; // Use the new Security class
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        // Symfony expects form fields under the form name, e.g., login[email]
        $credentials = $request->request->all('login');

        if (!isset($credentials['email']) || !isset($credentials['password']) || !isset($credentials['_csrf_token'])) {
            throw new CustomUserMessageAuthenticationException('Invalid form data.');
        }

        $email = $credentials['email'];
        $password = $credentials['password'];
        $csrfToken = $credentials['_csrf_token'];

        // Store email in session for pre-filling form on error
        $request->getSession()->set(Security::LAST_USERNAME, $email);

        // Validate CSRF token
        $token = new CsrfToken('authenticate', $csrfToken); // 'authenticate' is the ID from LoginType
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        // Find user - UserBadge handles loading the user via the provider
        $userBadge = new UserBadge($email, function ($userIdentifier) {
            $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);
            if (!$user) {
                throw new CustomUserMessageAuthenticationException('Email could not be found.');
            }
            return $user;
        });

        return new Passport(
            $userBadge,
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken), // Add CSRF badge for re-validation
                // new RememberMeBadge(), // Uncomment if you want remember me functionality
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirect to intended target path if available, otherwise default
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Default redirection (e.g., to dashboard or main page)
        // Using 'app_test' as the default redirect for now.
        return new RedirectResponse($this->urlGenerator->generate('app_test'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
