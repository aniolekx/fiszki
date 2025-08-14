<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Entity\UserCredits;
use App\Entity\CreditTransaction;
use App\Entity\SystemSettings;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AdminUserControllerTest extends WebTestCase
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
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        
        $this->createDefaultSettings();
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
    
    private function createDefaultSettings(): void
    {
        $setting = new SystemSettings('default_credits', '500', 'integer');
        $this->entityManager->persist($setting);
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
    
    private function createRegularUser(string $email = 'user@test.com', int $creditBalance = 200): User
    {
        $user = new User($email);
        $user->setPassword('$2y$13$test');
        $user->setRoles(['ROLE_USER']);
        $user->setIsConfirmed(true);
        
        $this->entityManager->persist($user);
        
        $credits = new UserCredits($user, $creditBalance);
        $this->entityManager->persist($credits);
        
        $this->entityManager->flush();
        
        return $user;
    }
    
    public function testUsersListRequiresAdminRole(): void
    {
        $user = $this->createRegularUser();
        $this->client->loginUser($user);
        
        $this->client->request('GET', '/admin/users/');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
    
    public function testAdminCanViewUsersList(): void
    {
        $admin = $this->createAdminUser();
        $user1 = $this->createRegularUser('user1@test.com', 500);
        $user2 = $this->createRegularUser('user2@test.com', 50);
        
        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Zarządzanie Użytkownikami');
        
        // Check if users are displayed
        $this->assertSelectorTextContains('.table', 'admin@test.com');
        $this->assertSelectorTextContains('.table', 'user1@test.com');
        $this->assertSelectorTextContains('.table', 'user2@test.com');
        
        // Check credit badges
        $this->assertSelectorExists('.badge.bg-success'); // 500+ credits
        $this->assertSelectorExists('.badge.bg-danger');  // <100 credits
    }
    
    public function testAdminCanViewUserDetails(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        
        // Add some transactions
        $transaction = new CreditTransaction(
            $user,
            CreditTransaction::TYPE_AI_GENERATION,
            -100,
            100,
            'Test generation'
        );
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
        
        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/' . $user->getId());
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'user@test.com');
        $this->assertSelectorTextContains('.card', 'Stan kredytów');
        $this->assertSelectorTextContains('.table', 'Test generation');
    }
    
    public function testAdminCanAddCreditsToUser(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        
        $this->client->loginUser($admin);
        
        // Get initial balance
        $credits = $this->entityManager->getRepository(UserCredits::class)->findByUser($user);
        $initialBalance = $credits->getBalance();
        
        // Add credits
        $this->client->request('POST', '/admin/users/' . $user->getId() . '/add-credits', [
            'amount' => '500',
            'description' => 'Test bonus'
        ]);
        
        $this->assertResponseRedirects('/admin/users/' . $user->getId());
        
        // Check new balance
        $this->entityManager->refresh($credits);
        $this->assertEquals($initialBalance + 500, $credits->getBalance());
        
        // Check transaction was created
        $transactions = $this->entityManager->getRepository(CreditTransaction::class)->findByUser($user);
        $lastTransaction = end($transactions);
        
        $this->assertEquals(CreditTransaction::TYPE_ADMIN_GRANT, $lastTransaction->getType());
        $this->assertEquals(500, $lastTransaction->getAmount());
        $this->assertEquals('Test bonus', $lastTransaction->getDescription());
        $this->assertSame($admin, $lastTransaction->getPerformedBy());
    }
    
    public function testCannotAddNegativeCredits(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        
        $this->client->loginUser($admin);
        
        $this->client->request('POST', '/admin/users/' . $user->getId() . '/add-credits', [
            'amount' => '-100',
            'description' => 'Invalid'
        ]);
        
        $this->assertResponseRedirects('/admin/users/' . $user->getId());
        
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Kwota musi być większa od zera');
    }
    
    public function testAdminCanToggleAdminRoleForOtherUsers(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        
        $this->client->loginUser($admin);
        
        // Grant admin role
        $this->client->request('POST', '/admin/users/' . $user->getId() . '/toggle-admin');
        
        $this->assertResponseRedirects('/admin/users/' . $user->getId());
        
        $this->entityManager->refresh($user);
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        
        // Remove admin role
        $this->client->request('POST', '/admin/users/' . $user->getId() . '/toggle-admin');
        
        $this->assertResponseRedirects('/admin/users/' . $user->getId());
        
        $this->entityManager->refresh($user);
        $this->assertNotContains('ROLE_ADMIN', $user->getRoles());
    }
    
    public function testAdminCannotToggleOwnAdminRole(): void
    {
        $admin = $this->createAdminUser();
        
        $this->client->loginUser($admin);
        
        $this->client->request('POST', '/admin/users/' . $admin->getId() . '/toggle-admin');
        
        $this->assertResponseRedirects('/admin/users/' . $admin->getId());
        
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Nie możesz zmienić własnych uprawnień');
        
        // Check admin still has ROLE_ADMIN
        $this->entityManager->refresh($admin);
        $this->assertContains('ROLE_ADMIN', $admin->getRoles());
    }
    
    public function testUserDetailsShowsCorrectStatistics(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser('user@test.com', 300);
        
        // Add transactions
        $grant = new CreditTransaction(
            $user,
            CreditTransaction::TYPE_ADMIN_GRANT,
            200,
            500,
            'Monthly bonus',
            $admin
        );
        $this->entityManager->persist($grant);
        
        $charge = new CreditTransaction(
            $user,
            CreditTransaction::TYPE_AI_GENERATION,
            -100,
            400,
            'AI Generation'
        );
        $this->entityManager->persist($charge);
        
        $this->entityManager->flush();
        
        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/' . $user->getId());
        
        $this->assertResponseIsSuccessful();
        
        // Check credits display
        $content = $crawler->html();
        $this->assertStringContainsString('300', $content); // Current balance
        
        // Check transaction history
        $this->assertSelectorTextContains('.table', 'Monthly bonus');
        $this->assertSelectorTextContains('.table', 'AI Generation');
        $this->assertSelectorTextContains('.table', '+200');
        $this->assertSelectorTextContains('.table', '-100');
    }
    
    public function testQuickAddCreditsModal(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser('user@test.com', 50);
        
        $this->client->loginUser($admin);
        $crawler = $this->client->request('GET', '/admin/users/');
        
        $this->assertResponseIsSuccessful();
        
        // Check that quick add button exists for low credit users
        $this->assertSelectorExists('.quick-add-credits[data-user-id="' . $user->getId() . '"]');
        
        // Check modal exists
        $this->assertSelectorExists('#quickAddCreditsModal');
    }
    
    public function testUserCannotAccessOtherUserDetails(): void
    {
        $user1 = $this->createRegularUser('user1@test.com');
        $user2 = $this->createRegularUser('user2@test.com');
        
        $this->client->loginUser($user1);
        
        $this->client->request('GET', '/admin/users/' . $user2->getId());
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}