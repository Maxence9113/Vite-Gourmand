<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * API pour les fonctionnalités liées aux adresses
 */
#[Route('/api/adresse')]
final class AddressApiController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * Obtient la liste des villes correspondant à un code postal
     * Utilise l'API du gouvernement français (api-adresse.data.gouv.fr)
     *
     * @param string $postalCode Le code postal à rechercher
     * @return JsonResponse Liste des villes ou message d'erreur
     */
    #[Route('/ville-depuis-code-postal/{postalCode}', name: 'app_address_get_city_from_postal_code', methods: ['GET'])]
    public function getCityFromPostalCode(string $postalCode): JsonResponse
    {
        // Validation basique du code postal français (5 chiffres)
        if (!preg_match('/^[0-9]{5}$/', $postalCode)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Code postal invalide'
            ], 400);
        }

        try {
            // Appel à l'API du gouvernement français pour géocoder le code postal
            $response = $this->httpClient->request('GET', 'https://api-adresse.data.gouv.fr/search/', [
                'query' => [
                    'q' => $postalCode,
                    'limit' => 20
                ]
            ]);

            $data = $response->toArray();

            // Vérifier si on a des résultats
            if (empty($data['features'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Aucune ville trouvée pour ce code postal'
                ], 404);
            }

            // Extraire toutes les villes uniques pour ce code postal
            $cities = $this->extractUniqueCities($data['features'], $postalCode);

            if (empty($cities)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Aucune ville trouvée pour ce code postal'
                ], 404);
            }

            return new JsonResponse([
                'success' => true,
                'cities' => $cities
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la récupération de la ville : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extrait les villes uniques depuis les résultats de l'API
     *
     * @param array $features Les résultats de l'API
     * @param string $postalCode Le code postal pour filtrage
     * @return array Liste des villes uniques
     */
    private function extractUniqueCities(array $features, string $postalCode): array
    {
        $cities = [];

        foreach ($features as $feature) {
            $city = $feature['properties']['city'] ?? null;
            $postcode = $feature['properties']['postcode'] ?? null;

            // Vérifier que la ville existe et que le code postal correspond exactement
            if ($city && $postcode === $postalCode && !in_array($city, $cities, true)) {
                $cities[] = $city;
            }
        }

        return $cities;
    }
}