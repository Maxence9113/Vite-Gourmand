<?php

namespace App\Tests\Service;

use App\Document\OrderStats;
use App\Entity\Order;
use App\Service\OrderStatsService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitaires du service OrderStatsService
 *
 * Teste la sauvegarde et la gestion des statistiques de commandes dans MongoDB
 */
class OrderStatsServiceTest extends TestCase
{
    private DocumentManager&\PHPUnit\Framework\MockObject\MockObject $documentManager;
    private LoggerInterface&\PHPUnit\Framework\MockObject\MockObject $logger;
    private OrderStatsService $orderStatsService;

    protected function setUp(): void
    {
        // Créer les mocks des dépendances
        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Instancier le service avec les mocks
        $this->orderStatsService = new OrderStatsService(
            $this->documentManager,
            $this->logger
        );
    }

    /**
     * Teste la création de nouvelles statistiques pour une commande
     */
    public function testSaveOrderStatsCreatesNewStats(): void
    {
        // Créer une commande de test
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(123);
        $order->method('getMenuName')->willReturn('Menu Gastronomique');
        $order->method('getTotalPrice')->willReturn(5000); // 50€ en centimes
        $order->method('getNumberOfPersons')->willReturn(4);
        $order->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2024-01-15'));

        // Créer un mock du repository
        $repository = $this->createMock(DocumentRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['orderId' => 123])
            ->willReturn(null); // Aucune stat existante

        $this->documentManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(OrderStats::class)
            ->willReturn($repository);

        // Vérifier que persist() et flush() sont appelés
        $this->documentManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(OrderStats::class));

        $this->documentManager
            ->expects($this->once())
            ->method('flush');

        // Vérifier qu'un log info est créé
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Statistiques créées dans MongoDB',
                $this->arrayHasKey('order_id')
            );

        // Exécuter le service
        $this->orderStatsService->saveOrderStats($order);
    }

    /**
     * Teste la mise à jour de statistiques existantes
     */
    public function testSaveOrderStatsUpdatesExistingStats(): void
    {
        // Créer une commande de test
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(123);
        $order->method('getTotalPrice')->willReturn(6000); // 60€ en centimes (prix modifié)
        $order->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2024-01-15'));

        // Créer une stat existante
        $existingStats = $this->createMock(OrderStats::class);
        $existingStats
            ->expects($this->once())
            ->method('setTotalPrice')
            ->with(60.0); // Conversion centimes -> euros

        // Créer un mock du repository qui retourne la stat existante
        $repository = $this->createMock(DocumentRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['orderId' => 123])
            ->willReturn($existingStats);

        $this->documentManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(OrderStats::class)
            ->willReturn($repository);

        // Vérifier que persist() n'est PAS appelé (mise à jour uniquement)
        $this->documentManager
            ->expects($this->never())
            ->method('persist');

        // Mais flush() doit être appelé pour sauvegarder les modifications
        $this->documentManager
            ->expects($this->once())
            ->method('flush');

        // Vérifier qu'un log info est créé
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Statistiques mises à jour dans MongoDB',
                $this->arrayHasKey('order_id')
            );

        // Exécuter le service
        $this->orderStatsService->saveOrderStats($order);
    }

    /**
     * Teste la gestion des erreurs lors de la sauvegarde
     */
    public function testSaveOrderStatsHandlesExceptions(): void
    {
        // Créer une commande de test
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(123);

        // Simuler une erreur lors de l'accès au repository
        $this->documentManager
            ->expects($this->once())
            ->method('getRepository')
            ->willThrowException(new \Exception('MongoDB connection failed'));

        // Vérifier qu'un log d'erreur est créé
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Erreur lors de la sauvegarde des stats MongoDB',
                $this->callback(function ($context) {
                    return isset($context['order_id']) &&
                           isset($context['error']) &&
                           $context['error'] === 'MongoDB connection failed';
                })
            );

        // Le service ne doit pas lancer d'exception (erreur silencieuse)
        $this->orderStatsService->saveOrderStats($order);
    }

    /**
     * Teste la suppression de statistiques
     */
    public function testDeleteOrderStatsRemovesExistingStats(): void
    {
        $orderId = 123;

        // Créer une stat existante
        $existingStats = $this->createMock(OrderStats::class);

        // Créer un mock du repository
        $repository = $this->createMock(DocumentRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['orderId' => $orderId])
            ->willReturn($existingStats);

        $this->documentManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(OrderStats::class)
            ->willReturn($repository);

        // Vérifier que remove() et flush() sont appelés
        $this->documentManager
            ->expects($this->once())
            ->method('remove')
            ->with($existingStats);

        $this->documentManager
            ->expects($this->once())
            ->method('flush');

        // Vérifier qu'un log info est créé
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Statistiques supprimées de MongoDB',
                $this->arrayHasKey('order_id')
            );

        // Exécuter le service
        $this->orderStatsService->deleteOrderStats($orderId);
    }

    /**
     * Teste la suppression quand aucune stat n'existe
     */
    public function testDeleteOrderStatsDoesNothingWhenNoStatsExist(): void
    {
        $orderId = 123;

        // Créer un mock du repository qui ne trouve rien
        $repository = $this->createMock(DocumentRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['orderId' => $orderId])
            ->willReturn(null);

        $this->documentManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(OrderStats::class)
            ->willReturn($repository);

        // Vérifier que remove() et flush() ne sont PAS appelés
        $this->documentManager
            ->expects($this->never())
            ->method('remove');

        $this->documentManager
            ->expects($this->never())
            ->method('flush');

        // Aucun log ne devrait être créé
        $this->logger
            ->expects($this->never())
            ->method('info');

        // Exécuter le service
        $this->orderStatsService->deleteOrderStats($orderId);
    }

    /**
     * Teste la gestion des erreurs lors de la suppression
     */
    public function testDeleteOrderStatsHandlesExceptions(): void
    {
        $orderId = 123;

        // Simuler une erreur lors de l'accès au repository
        $this->documentManager
            ->expects($this->once())
            ->method('getRepository')
            ->willThrowException(new \Exception('MongoDB connection failed'));

        // Vérifier qu'un log d'erreur est créé
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Erreur lors de la suppression des stats MongoDB',
                $this->callback(function ($context) {
                    return isset($context['order_id']) &&
                           isset($context['error']) &&
                           $context['error'] === 'MongoDB connection failed';
                })
            );

        // Le service ne doit pas lancer d'exception (erreur silencieuse)
        $this->orderStatsService->deleteOrderStats($orderId);
    }
}