<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Entity\Menu;
use App\Entity\Theme;
use App\Entity\Dietetary;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du contrôleur d'administration des menus
 */
class AdminMenuControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private ?Theme $testTheme = null;
    private ?Dietetary $testDietetary = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->loginAsAdmin();

        // Créer un thème et un régime alimentaire pour les tests
        $this->testTheme = new Theme();
        $this->testTheme->setName('Thème Test');
        $this->testTheme->setTextAlt('Test');
        $this->testTheme->setIllustration('/uploads/theme_illustrations/test.jpg');
        $this->testTheme->setDescription('Description test');
        $this->entityManager->persist($this->testTheme);

        $this->testDietetary = new Dietetary();
        $this->testDietetary->setName('Régime Test');
        $this->entityManager->persist($this->testDietetary);

        $this->entityManager->flush();
    }

    private function loginAsAdmin(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $adminUser = $userRepository->findOneBy(['email' => 'admin@test.com']);

        if (!$adminUser) {
            $adminUser = new User();
            $adminUser->setEmail('admin@test.com');
            $adminUser->setFirstname('Admin');
            $adminUser->setLastname('Test');
            $adminUser->setRoles(['ROLE_ADMIN']);
            $adminUser->setPassword('password');

            $this->entityManager->persist($adminUser);
            $this->entityManager->flush();
        }

        $this->client->loginUser($adminUser);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testMenusListPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/admin/menus');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testNewMenuPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/admin/menus/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="menu[name]"]');
        $this->assertSelectorExists('select[name="menu[theme]"]');
    }

}