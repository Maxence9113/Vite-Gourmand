<?php

namespace App\Tests\Repository;

use App\Document\OrderStats;
use App\Repository\OrderStatsRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests d'intégration pour OrderStatsRepository
 *
 * Ces tests utilisent une vraie base MongoDB (de test) pour vérifier
 * que les requêtes d'agrégation fonctionnent correctement.
 */
class OrderStatsRepositoryTest extends KernelTestCase
{
    private ?DocumentManager $documentManager;
    private ?OrderStatsRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->documentManager = self::getContainer()->get(DocumentManager::class);
        $this->repository = new OrderStatsRepository($this->documentManager);

        // Nettoyer la collection avant chaque test
        $this->documentManager->getDocumentCollection(OrderStats::class)->drop();
    }

    protected function tearDown(): void
    {
        // Nettoyer après chaque test
        if ($this->documentManager) {
            try {
                $this->documentManager->getDocumentCollection(OrderStats::class)->drop();
            } catch (\Exception $e) {
                // Ignorer les erreurs de nettoyage
            }
            $this->documentManager->close();
        }

        $this->documentManager = null;
        $this->repository = null;

        parent::tearDown();
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

        $stats4 = new OrderStats();
        $stats4->setOrderId(4)
            ->setMenuId(30)
            ->setMenuName('Menu Végétarien')
            ->setThemeName('Végétarien')
            ->setTotalPrice(40.00)
            ->setNumberOfPeople(2)
            ->setOrderDate(new \DateTime('2024-03-05'));

        $this->documentManager->persist($stats1);
        $this->documentManager->persist($stats2);
        $this->documentManager->persist($stats3);
        $this->documentManager->persist($stats4);
        $this->documentManager->flush();
    }

    /**
     * Teste la récupération du nombre de commandes par menu
     */
    public function testGetOrderCountByMenu(): void
    {
        $this->createTestData();

        $result = $this->repository->getOrderCountByMenu();

        // Vérifier qu'on a bien 3 menus
        $this->assertCount(3, $result);

        // Vérifier que le menu le plus commandé est en premier
        $this->assertEquals('Menu Gastronomique', $result[0]['menuName']);
        $this->assertEquals(2, $result[0]['count']);

        // Vérifier que les deux autres menus ont un count de 1
        $this->assertEquals(1, $result[1]['count']);
        $this->assertEquals(1, $result[2]['count']);

        // Vérifier que les 3 menus sont présents (ordre peut varier pour ceux avec le même count)
        $menuNames = array_map(fn($item) => $item['menuName'], $result);
        $this->assertContains('Menu Gastronomique', $menuNames);
        $this->assertContains('Menu Asiatique', $menuNames);
        $this->assertContains('Menu Végétarien', $menuNames);
    }

    /**
     * Teste le filtrage par nom de menu
     */
    public function testGetOrderCountByMenuWithFilter(): void
    {
        $this->createTestData();

        $result = $this->repository->getOrderCountByMenu('Menu Gastronomique');

        // On devrait avoir qu'un seul résultat
        $this->assertCount(1, $result);
        $this->assertEquals('Menu Gastronomique', $result[0]['menuName']);
        $this->assertEquals(2, $result[0]['count']);
    }

    /**
     * Teste le filtrage par période
     */
    public function testGetOrderCountByMenuWithDateRange(): void
    {
        $this->createTestData();

        $startDate = new \DateTime('2024-02-01');
        $endDate = new \DateTime('2024-03-31');

        $result = $this->repository->getOrderCountByMenu(null, $startDate, $endDate);

        // On devrait avoir 2 menus (Asiatique et Végétarien)
        $this->assertCount(2, $result);

        $menuNames = array_map(fn($item) => $item['menuName'], $result);
        $this->assertContains('Menu Asiatique', $menuNames);
        $this->assertContains('Menu Végétarien', $menuNames);
        $this->assertNotContains('Menu Gastronomique', $menuNames);
    }

    /**
     * Teste le calcul du chiffre d'affaires par menu
     */
    public function testGetRevenueByMenu(): void
    {
        $this->createTestData();

        $result = $this->repository->getRevenueByMenu();

        // Vérifier qu'on a bien 3 menus
        $this->assertCount(3, $result);

        // Vérifier que le menu avec le plus grand CA est en premier
        $this->assertEquals('Menu Gastronomique', $result[0]['menuName']);
        $this->assertEquals(125.00, $result[0]['totalRevenue']); // 50 + 75
    }

    /**
     * Teste le filtrage du CA par nom de menu
     */
    public function testGetRevenueByMenuWithFilter(): void
    {
        $this->createTestData();

        $result = $this->repository->getRevenueByMenu('Menu Asiatique');

        $this->assertCount(1, $result);
        $this->assertEquals('Menu Asiatique', $result[0]['menuName']);
        $this->assertEquals(45.00, $result[0]['totalRevenue']);
    }

    /**
     * Teste les statistiques globales
     */
    public function testGetGlobalStats(): void
    {
        $this->createTestData();

        $result = $this->repository->getGlobalStats();

        // Vérifier les totaux
        $this->assertEquals(4, $result['totalOrders']);
        $this->assertEquals(210.00, $result['totalRevenue']); // 50 + 75 + 45 + 40
        $this->assertEquals(2.25, $result['avgPeoplePerOrder']); // (2 + 3 + 2 + 2) / 4
    }

    /**
     * Teste les statistiques globales avec période
     */
    public function testGetGlobalStatsWithDateRange(): void
    {
        $this->createTestData();

        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');

        $result = $this->repository->getGlobalStats($startDate, $endDate);

        // Seulement les commandes de janvier
        $this->assertEquals(2, $result['totalOrders']);
        $this->assertEquals(125.00, $result['totalRevenue']); // 50 + 75
        $this->assertEquals(2.5, $result['avgPeoplePerOrder']); // (2 + 3) / 2
    }

    /**
     * Teste les statistiques globales quand il n'y a pas de données
     */
    public function testGetGlobalStatsWithNoData(): void
    {
        // Ne pas créer de données de test

        $result = $this->repository->getGlobalStats();

        // Vérifier les valeurs par défaut
        $this->assertEquals(0, $result['totalOrders']);
        $this->assertEquals(0, $result['totalRevenue']);
        $this->assertEquals(0, $result['avgPeoplePerOrder']);
    }

    /**
     * Teste la récupération des noms de menus distincts
     */
    public function testGetDistinctMenuNames(): void
    {
        $this->createTestData();

        $result = $this->repository->getDistinctMenuNames();

        $this->assertCount(3, $result);
        $this->assertContains('Menu Gastronomique', $result);
        $this->assertContains('Menu Asiatique', $result);
        $this->assertContains('Menu Végétarien', $result);

        // Vérifier que les menus sont triés alphabétiquement
        $this->assertEquals('Menu Asiatique', $result[0]);
        $this->assertEquals('Menu Gastronomique', $result[1]);
        $this->assertEquals('Menu Végétarien', $result[2]);
    }

    /**
     * Teste la récupération des noms de thèmes distincts
     */
    public function testGetDistinctThemeNames(): void
    {
        $this->createTestData();

        $result = $this->repository->getDistinctThemeNames();

        $this->assertCount(3, $result);
        $this->assertContains('Français', $result);
        $this->assertContains('Asiatique', $result);
        $this->assertContains('Végétarien', $result);
    }

    /**
     * Teste le nombre de commandes par thème
     */
    public function testGetOrderCountByTheme(): void
    {
        $this->createTestData();

        $result = $this->repository->getOrderCountByTheme();

        $this->assertCount(3, $result);

        // Le thème Français devrait être le plus populaire
        $this->assertEquals('Français', $result[0]['themeName']);
        $this->assertEquals(2, $result[0]['count']);
    }

    /**
     * Teste le filtrage du nombre de commandes par thème avec période
     */
    public function testGetOrderCountByThemeWithDateRange(): void
    {
        $this->createTestData();

        $startDate = new \DateTime('2024-02-01');
        $endDate = new \DateTime('2024-03-31');

        $result = $this->repository->getOrderCountByTheme(null, $startDate, $endDate);

        $this->assertCount(2, $result);

        $themeNames = array_map(fn($item) => $item['themeName'], $result);
        $this->assertContains('Asiatique', $themeNames);
        $this->assertContains('Végétarien', $themeNames);
        $this->assertNotContains('Français', $themeNames);
    }

    /**
     * Teste le CA par thème
     */
    public function testGetRevenueByTheme(): void
    {
        $this->createTestData();

        $result = $this->repository->getRevenueByTheme();

        $this->assertCount(3, $result);

        // Le thème Français devrait avoir le plus grand CA
        $this->assertEquals('Français', $result[0]['themeName']);
        $this->assertEquals(125.00, $result[0]['totalRevenue']);
    }

    /**
     * Teste la récupération avec filtres
     */
    public function testFindWithFilters(): void
    {
        $this->createTestData();

        // Sans filtre
        $result = $this->repository->findWithFilters();
        $this->assertCount(4, $result);

        // Avec filtre par menu
        $result = $this->repository->findWithFilters('Menu Gastronomique');
        $this->assertCount(2, $result);

        // Avec filtre par période
        $startDate = new \DateTime('2024-02-01');
        $result = $this->repository->findWithFilters(null, $startDate);
        $this->assertCount(2, $result);

        // Tous les filtres combinés
        $endDate = new \DateTime('2024-02-28');
        $result = $this->repository->findWithFilters('Menu Asiatique', $startDate, $endDate);
        $this->assertCount(1, $result);
    }

    /**
     * Teste le tri des résultats par date décroissante
     */
    public function testFindWithFiltersSortsDescending(): void
    {
        $this->createTestData();

        $result = $this->repository->findWithFilters();

        // La commande la plus récente devrait être en premier
        $this->assertEquals(4, $result[0]->getOrderId()); // Mars 2024
        $this->assertEquals(3, $result[1]->getOrderId()); // Février 2024
        $this->assertEquals(2, $result[2]->getOrderId()); // Janvier 20
        $this->assertEquals(1, $result[3]->getOrderId()); // Janvier 15
    }
}