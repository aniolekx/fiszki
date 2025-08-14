<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Entity\UserCredits;
use App\Entity\CreditTransaction;
use App\Entity\SystemSettings;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AdminControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        
        // Clear database
        $this->entityManager->createQuery('DELETE FROM App\Entity\CreditTransaction')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\UserCredits')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\SystemSettings')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        
        // Create default settings
        $this->createDefaultSettings();
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
    
    private function createDefaultSettings(): void
    {
        $settings = [
            ['default_credits', '500', 'integer'],
            ['ai_generation_cost', '100', 'integer'],
            ['openai_monthly_limit', '1000000', 'integer'],
        ];
        
        foreach ($settings as [$key, $value, $type]) {
            $setting = new SystemSettings($key, $value, $type);
            $this->entityManager->persist($setting);
        }
        
        $this->entityManager->flush();
    }
    
    private function createAdminUser(): User
    {
        $admin = new User('admin@test.com');
        $admin->setPassword('$2y$13$test');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setIsConfirmed(true);
        
        $this->entityManager->persist($admin);
        
        $credits = new UserCredits($admin, 1000);
        $this->entityManager->persist($credits);
        
        $this->entityManager->flush();
        
        return $admin;
    }
    
    private function createRegularUser(): User
    {
        $user = new User('user@test.com');
        $user->setPassword('$2y$13$test');
        $user->setRoles(['ROLE_USER']);
        $user->setIsConfirmed(true);
        
        $this->entityManager->persist($user);
        
        $credits = new UserCredits($user, 200);
        $this->entityManager->persist($credits);
        
        $this->entityManager->flush();
        
        return $user;
    }
    
    public function testAdminDashboardRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/');
        
        $this->assertResponseRedirects('/login');
    }
    
    public function testRegularUserCannotAccessAdminDashboard(): void
    {
        $user = $this->createRegularUser();
        $this->client->loginUser($user);
        
        $this->client->request('GET', '/admin/');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
    
    public function testAdminCanAccessDashboard(): void
    {
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);
        
        $crawler = $this->client->request('GET', '/admin/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Panel Administracyjny');
    }
    
    public function testDashboardDisplaysCorrectStatistics(): void
    {
        $admin = $this->createAdminUser();
        $user1 = $this->createRegularUser();
        
        // Create another user with low credits
        $user2 = new User('user2@test.com');
        $user2->setPassword('$2y$13$test');
        $user2->setIsConfirmed(true);
        $this->entityManager->persist($user2);
        
        $credits2 = new UserCredits($user2, 50);
        $this->entityManager->persist($credits2);
        
        // Create some transactions
        $transaction1 = new CreditTransaction(
            $user1,
            CreditTransaction::TYPE_AI_GENERATION,
            -100,
            100,
            'AI Generation'
        );
        $this->entityManager->persist($transaction1);
        
        $transaction2 = new CreditTransaction(
            $user2,
            CreditTransaction::TYPE_ADMIN_GRANT,
            200,
            250,
            'Admin grant',
            $admin
        );
        $this->entityManager->persist($transaction2);
        
        $this->entityManager->flush();
        
        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/');
        
        $this->assertResponseIsSuccessful();
        
        // Check statistics
        $content = $crawler->html();
        $this->assertStringContainsString('3', $content); // 3 users
        $this->assertStringContainsString('1250', $content); // Total credits (1000 + 200 + 50)
    }
    
    public function testDashboardShowsRecentTransactions(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        
        $transaction = new CreditTransaction(
            $user,
            CreditTransaction::TYPE_AI_GENERATION,
            -100,
            100,
            'Test AI Generation'
        );
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
        
        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.table', 'user@test.com');
        $this->assertSelectorTextContains('.table', 'AI');
        $this->assertSelectorTextContains('.table', '-100');
    }
    
    public function testDashboardShowsUsersWithLowCredits(): void
    {
        $admin = $this->createAdminUser();
        
        // Create user with low credits
        $lowCreditUser = new User('lowcredit@test.com');
        $lowCreditUser->setPassword('$2y$13$test');
        $lowCreditUser->setIsConfirmed(true);
        $this->entityManager->persist($lowCreditUser);
        
        $credits = new UserCredits($lowCreditUser, 50);
        $this->entityManager->persist($credits);
        $this->entityManager->flush();
        
        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.card', 'lowcredit@test.com');
        $this->assertSelectorTextContains('.badge.bg-danger', '50 kredytów');
    }
    
    public function testDashboardLinksToUsersAndSettings(): void
    {
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);
        
        $crawler = $this->client->request('GET', '/admin/');
        
        $this->assertResponseIsSuccessful();
        
        // Check for links
        $usersLink = $crawler->selectLink('Użytkownicy')->link();
        $settingsLink = $crawler->selectLink('Ustawienia')->link();
        
        $this->assertStringContainsString('/admin/users', $usersLink->getUri());
        $this->assertStringContainsString('/admin/settings', $settingsLink->getUri());
    }
    
    public function testAdminNavigationMenuShowsAdminLink(): void
    {
        $admin = $this->createAdminUser();
        $this->client->loginUser($admin);
        
        $crawler = $this->client->request('GET', '/dashboard');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.navbar', 'Panel Admin');
    }
    
    public function testRegularUserDoesNotSeeAdminLink(): void
    {
        $user = $this->createRegularUser();
        $this->client->loginUser($user);
        
        $crawler = $this->client->request('GET', '/dashboard');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextNotContains('.navbar', 'Panel Admin');
    }
}