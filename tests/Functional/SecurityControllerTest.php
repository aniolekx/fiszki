<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    private function cleanDatabase(): void
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            $this->entityManager->remove($user);
        }
        $this->entityManager->flush();
    }

    private function createUser(string $email = 'test@example.com', string $password = 'Test123!', bool $verified = true): User
    {
        $user = new User($email);
        
        $hashedPassword = $this->client->getContainer()
            ->get('security.user_password_hasher')
            ->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setIsConfirmed($verified);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testLoginPageIsAccessible(): void
    {
        $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Logowanie');
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->createUser('test@example.com', 'Test123!');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Zaloguj się')->form([
            'login[email]' => 'test@example.com',
            'login[password]' => 'Test123!',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/');
        
        $this->client->followRedirect();
        $this->assertSelectorTextContains('body', 'Witaj, test@example.com');
    }

    public function testLoginWithInvalidPassword(): void
    {
        $this->createUser('test@example.com', 'Test123!');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Zaloguj się')->form([
            'login[email]' => 'test@example.com',
            'login[password]' => 'WrongPassword',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/login');
        
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Invalid credentials');
    }

    public function testLoginWithNonExistentEmail(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Zaloguj się')->form([
            'login[email]' => 'nonexistent@example.com',
            'login[password]' => 'Password123',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/login');
        
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Invalid credentials');
    }

    public function testRegisterPageIsAccessible(): void
    {
        $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Rejestracja');
    }

    public function testSuccessfulRegistration(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Zarejestruj się')->form([
            'registration[email]' => 'newuser@example.com',
            'registration[password]' => 'SecurePass123!',
            'registration[agreeTerms]' => true,
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/verify/email');

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'newuser@example.com']);
        
        $this->assertNotNull($user);
        $this->assertFalse($user->isConfirmed());
        $this->assertNotNull($user->getConfirmationToken());
    }

    public function testRegistrationWithExistingEmail(): void
    {
        $this->createUser('existing@example.com');

        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Zarejestruj się')->form([
            'registration[email]' => 'existing@example.com',
            'registration[password]' => 'Password123!',
            'registration[agreeTerms]' => true,
        ]);

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.invalid-feedback', 'This value is already used');
    }

    public function testRegistrationWithWeakPassword(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Zarejestruj się')->form([
            'registration[email]' => 'test@example.com',
            'registration[password]' => '123',
            'registration[agreeTerms]' => true,
        ]);

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.invalid-feedback', 'Your password should be at least');
    }

    public function testLogout(): void
    {
        $this->createUser('test@example.com', 'Test123!');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Zaloguj się')->form([
            'login[email]' => 'test@example.com',
            'login[password]' => 'Test123!',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->client->request('GET', '/logout');
        $this->assertResponseRedirects('/login');
    }

    public function testAccessProtectedRouteWithoutLogin(): void
    {
        $this->client->request('GET', '/deck/');
        $this->assertResponseRedirects('/login');

        $this->client->request('GET', '/flashcard/');
        $this->assertResponseRedirects('/login');

        $this->client->request('GET', '/dashboard');
        $this->assertResponseRedirects('/login');
    }

    public function testAccessProtectedRouteAfterLogin(): void
    {
        $this->createUser('test@example.com', 'Test123!');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Zaloguj się')->form([
            'login[email]' => 'test@example.com',
            'login[password]' => 'Test123!',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->client->request('GET', '/deck/');
        $this->assertResponseIsSuccessful();
    }

    public function testPublicPagesAreAccessible(): void
    {
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
    }
}