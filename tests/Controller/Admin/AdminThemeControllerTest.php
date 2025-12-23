<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Entity\Theme;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du contrôleur d'administration des thèmes
 */
class AdminThemeControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->loginAsAdmin();
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

    public function testThemesListPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/admin/themes');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testNewThemePageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/admin/themes/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="theme[name]"]');
    }


    public function testEditTheme(): void
    {
        $theme = new Theme();
        $theme->setName('Thème à modifier');
        $theme->setDescription('Description originale');

        $this->entityManager->persist($theme);
        $this->entityManager->flush();

        $themeId = $theme->getId();

        $crawler = $this->client->request('GET', '/admin/themes/' . $themeId . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Modifier le thème')->form();
        $this->assertEquals('Thème à modifier', $form['theme[name]']->getValue());

        $form['theme[name]'] = 'Thème modifié';
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/themes');
        $this->entityManager->clear();

        $updatedTheme = $this->entityManager->getRepository(Theme::class)->find($themeId);
        $this->assertNotNull($updatedTheme);
        $this->assertEquals('Thème modifié', $updatedTheme->getName());
    }

    public function testDeleteTheme(): void
    {
        $theme = new Theme();
        $theme->setName('Thème à supprimer');
        $theme->setDescription('Description test');

        $this->entityManager->persist($theme);
        $this->entityManager->flush();

        $themeId = $theme->getId();

        $this->client->request('POST', '/admin/themes/' . $themeId . '/delete');
        $this->assertResponseRedirects('/admin/themes');

        $deletedTheme = $this->entityManager->getRepository(Theme::class)->find($themeId);
        $this->assertNull($deletedTheme);
    }
}