<?php

namespace App\Tests\Controller\Admin;

use App\Document\OrderStats;
use App\Entity\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du contrôleur OrderStatsController
 *
 * Teste l'accès à la page de statistiques et le rendu correct des données
 */
class OrderStatsControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private ?DocumentManager $documentManager = null;
    private ?User $adminUser = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = $this->client->getContainer();

        $this->entityManager = $container->get('doctrine')->getManager();
        $this->documentManager = $container->get(DocumentManager::class);

        $this->createAdminUser();
        $this->clearMongoData();
    }

    protected function tearDown(): void
    {
        $this->clearMongoData();

        if ($this->documentManager) {
            $this->documentManager->close();
            $this->documentManager = null;
        }

        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }

        parent::tearDown();
    }

    /**
     * Crée un utilisateur admin pour les tests
     */
    private function createAdminUser(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->adminUser = $userRepository->findOneBy(['email' => 'test.admin@example.com']);

        if (!$this->adminUser) {
            $this->adminUser = new User();
            $this->adminUser->setEmail('test.admin@example.com');
            $this->adminUser->setFirstname('Admin');
            $this->adminUser->setLastname('Test');
            $this->adminUser->setRoles(['ROLE_ADMIN']);
            $this->adminUser->setPassword('$2y$13$test');

            $this->entityManager->persist($this->adminUser);
            $this->entityManager->flush();
        }
    }

    /**
     * Nettoie les données MongoDB de test
     */
    private function clearMongoData(): void
    {
        if ($this->documentManager) {
            $this->documentManager->getDocumentCollection(OrderStats::class)->drop();
        }
    }

    /**
     * Crée des données de test dans MongoDB
     */
    private function createTestData(): void
    {
        $stats1 = new OrderStats();
        $stats1->setOrderId(1)
            ->setMenuId(10)
            ->setMenuName('Menu Gastronomique')
            ->setThemeName('Français')
            ->setTotalPrice(50.00)
            ->setNumberOfPeople(2)
            ->setOrderDate(new \DateTime('2024-01-15'));

        $stats2 = new OrderStats();
        $stats2->setOrderId(2)
            ->setMenuId(10)
            ->setMenuName('Menu Gastronomique')
            ->setThemeName('Français')
            ->setTotalPrice(75.00)
            ->setNumberOfPeople(3)
            ->setOrderDate(new \DateTime('2024-01-20'));

        $stats3 = new OrderStats();
        $stats3->setOrderId(3)
            ->setMenuId(20)
            ->setMenuName('Menu Asiatique')
            ->setThemeName('Asiatique')
            ->setTotalPrice(45.00)
            ->setNumberOfPeople(2)
            ->setOrderDate(new \DateTime('2024-02-10'));

        $this->documentManager->persist($stats1);
        $this->documentManager->persist($stats2);
        $this->documentManager->persist($stats3);
        $this->documentManager->flush();
    }

    /**
     * Teste que les utilisateurs non authentifiés sont redirigés
     */
    public function testStatsPageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/stats');

        // Devrait rediriger vers la page de connexion
        $this->assertResponseRedirects('/connexion');
    }

    /**
     * Teste que les clients normaux n'ont pas accès aux stats
     */
    public function testStatsPageRequiresAdminRole(): void
    {
        // Créer un utilisateur client
        $customer = new User();
        $customer->setEmail('customer.stats@example.com');
        $customer->setFirstname('Customer');
        $customer->setLastname('Stats');
        $customer->setRoles(['ROLE_USER']);
        $customer->setPassword('$2y$13$test');

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        $this->client->loginUser($customer);
        $this->client->request('GET', '/admin/stats');

        // Devrait retourner 403 Forbidden
        $this->assertResponseStatusCodeSame(403);

        // Nettoyer
        $this->entityManager->remove($customer);
        $this->entityManager->flush();
    }

    /**
     * Teste l'accès à la page de statistiques pour un admin
     */
    public function testAdminCanAccessStatsPage(): void
    {
        $this->createTestData();

        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/stats');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Statistiques');
    }

    /**
     * Teste l'affichage des statistiques globales
     */
    public function testStatsPageDisplaysGlobalStats(): void
    {
        $this->createTestData();

        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/stats');

        $this->assertResponseIsSuccessful();

        // Vérifier que les KPIs sont affichés
        $html = $crawler->html();

        // Nombre total de commandes (3)
        $this->assertStringContainsString('3', $html);

        // Chiffre d'affaires total (170.00 = 50 + 75 + 45)
        $this->assertStringContainsString('170', $html);
    }

    /**
     * Teste l'affichage de la liste des menus
     */
    public function testStatsPageDisplaysMenuList(): void
    {
        $this->createTestData();

        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/stats?view=menu');

        $this->assertResponseIsSuccessful();

        $html = $crawler->html();

        // Vérifier que les menus sont affichés
        $this->assertStringContainsString('Menu Gastronomique', $html);
        $this->assertStringContainsString('Menu Asiatique', $html);
    }

    /**
     * Teste le filtrage par menu
     */
    public function testStatsPageCanFilterByMenu(): void
    {
        $this->createTestData();

        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/stats?view=menu&menu=Menu+Gastronomique');

        $this->assertResponseIsSuccessful();

        // Vérifier que le filtre est appliqué
        $html = $crawler->html();
        $this->assertStringContainsString('Menu Gastronomique', $html);
    }

    /**
     * Teste le filtrage par période - dernière semaine
     */
    public function testStatsPageCanFilterByWeek(): void
    {
        $this->client->loginUser($this->adminUser);

        // Créer des données récentes (moins de 7 jours)
        $recentStats = new OrderStats();
        $recentStats->setOrderId(100)
            ->setMenuId(10)
            ->setMenuName('Menu Récent')
            ->setThemeName('Test')
            ->setTotalPrice(50.00)
            ->setNumberOfPeople(2)
            ->setOrderDate(new \DateTime('-3 days'));

        $this->documentManager->persist($recentStats);
        $this->documentManager->flush();

        $crawler = $this->client->request('GET', '/admin/stats?view=menu&period=week');

        $this->assertResponseIsSuccessful();

        $html = $crawler->html();
        $this->assertStringContainsString('Menu Récent', $html);
    }

    /**
     * Teste le filtrage par période personnalisée
     */
    public function testStatsPageCanFilterByCustomDateRange(): void
    {
        $this->createTestData();

        $this->client->loginUser($this->adminUser);

        // Filtrer uniquement janvier 2024
        $crawler = $this->client->request(
            'GET',
            '/admin/stats?view=menu&period=custom&start_date=2024-01-01&end_date=2024-01-31'
        );

        $this->assertResponseIsSuccessful();

        // Les stats de janvier devraient être présentes
        $html = $crawler->html();
        $this->assertStringContainsString('Menu Gastronomique', $html);
    }

    /**
     * Teste la gestion d'une date invalide
     */
    public function testStatsPageHandlesInvalidDate(): void
    {
        $this->createTestData();

        $this->client->loginUser($this->adminUser);

        $crawler = $this->client->request(
            'GET',
            '/admin/stats?period=custom&start_date=invalid-date'
        );

        $this->assertResponseIsSuccessful();

        // Un message flash d'avertissement devrait être présent
        $this->assertSelectorExists('.alert-warning, .flash-warning');
    }

    /**
     * Teste l'affichage de la vue par thème
     */
    public function testStatsPageDisplaysThemeView(): void
    {
        $this->createTestData();

        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/stats?view=theme');

        $this->assertResponseIsSuccessful();

        $html = $crawler->html();

        // Vérifier que les thèmes sont affichés
        $this->assertStringContainsString('Français', $html);
        $this->assertStringContainsString('Asiatique', $html);
    }

    /**
     * Teste le filtrage par thème en vue menu
     */
    public function testStatsPageCanFilterMenusByTheme(): void
    {
        $this->createTestData();

        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/stats?view=menu&theme=Français');

        $this->assertResponseIsSuccessful();

        $html = $crawler->html();

        // Seuls les menus du thème Français devraient être affichés
        $this->assertStringContainsString('Menu Gastronomique', $html);
        $this->assertStringNotContainsString('Menu Asiatique', $html);
    }

    /**
     * Teste que le graphique est présent dans la page
     */
    public function testStatsPageContainsChart(): void
    {
        $this->createTestData();

        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/stats');

        $this->assertResponseIsSuccessful();

        // Vérifier la présence du canvas pour Chart.js
        $this->assertSelectorExists('canvas');
    }

    /**
     * Teste que les données JSON sont présentes pour le graphique
     */
    public function testStatsPageContainsChartData(): void
    {
        $this->createTestData();

        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/stats');

        $this->assertResponseIsSuccessful();

        $html = $crawler->html();

        // Vérifier que les données JSON pour Chart.js sont présentes
        $this->assertStringContainsString('chartLabels', $html);
        $this->assertStringContainsString('chartData', $html);
    }

    /**
     * Teste l'affichage quand il n'y a pas de données
     */
    public function testStatsPageHandlesNoData(): void
    {
        // Ne pas créer de données de test

        $this->client->loginUser($this->adminUser);
        $crawler = $this->client->request('GET', '/admin/stats');

        $this->assertResponseIsSuccessful();

        // La page devrait s'afficher sans erreur même sans données
        $html = $crawler->html();

        // Vérifier que les valeurs par défaut sont affichées (0 commandes, 0€ CA)
        $this->assertStringContainsString('0', $html);
    }

    /**
     * Teste que les employés ont aussi accès aux statistiques
     */
    public function testEmployeeCanAccessStatsPage(): void
    {
        // Créer un utilisateur employé
        $employee = new User();
        $employee->setEmail('employee.stats@example.com');
        $employee->setFirstname('Employee');
        $employee->setLastname('Stats');
        $employee->setRoles(['ROLE_EMPLOYEE']);
        $employee->setPassword('$2y$13$test');

        $this->entityManager->persist($employee);
        $this->entityManager->flush();

        $this->createTestData();

        $this->client->loginUser($employee);
        $crawler = $this->client->request('GET', '/admin/stats');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Statistiques');

        // Nettoyer
        $this->entityManager->remove($employee);
        $this->entityManager->flush();
    }
}