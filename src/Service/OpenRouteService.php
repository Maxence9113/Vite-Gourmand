<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer les appels à l'API OpenRouteService
 * Permet le géocodage d'adresses et le calcul de distances
 */
class OpenRouteService
{
    private const BASE_URL = 'https://api.openrouteservice.org';
    private const GEOCODE_ENDPOINT = '/geocode/search';
    private const DIRECTIONS_ENDPOINT = '/v2/directions/driving-car';

    // Coordonnées approximatives de Bordeaux (centre-ville) pour le point de départ
    private const BORDEAUX_LAT = 44.8378;
    private const BORDEAUX_LON = -0.5792;

    // Codes postaux de Bordeaux (pas de frais de distance pour ces codes)
    private const BORDEAUX_POSTAL_CODES = ['33000', '33100', '33200', '33300', '33800'];

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $apiKey
    ) {
    }

    /**
     * Géocode une adresse pour obtenir ses coordonnées GPS et son code postal
     *
     * @param string $address Adresse complète à géocoder
     * @return array{lat: float, lon: float, postalCode: string|null}|null Coordonnées GPS et code postal ou null si non trouvé
     */
    public function geocodeAddress(string $address): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . self::GEOCODE_ENDPOINT, [
                'query' => [
                    'api_key' => $this->apiKey,
                    'text' => $address,
                    'boundary.country' => 'FR', // Limiter à la France
                    'size' => 1 // Ne récupérer que le meilleur résultat
                ]
            ]);

            $data = $response->toArray();

            if (empty($data['features'])) {
                $this->logger->warning('Aucun résultat de géocodage pour l\'adresse', ['address' => $address]);
                return null;
            }

            $feature = $data['features'][0];
            $coordinates = $feature['geometry']['coordinates'];
            $postalCode = $feature['properties']['postalcode'] ?? null;

            return [
                'lon' => $coordinates[0],
                'lat' => $coordinates[1],
                'postalCode' => $postalCode
            ];
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Erreur lors du géocodage de l\'adresse', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur inattendue lors du géocodage', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calcule la distance routière entre deux points GPS
     *
     * @param float $startLat Latitude de départ
     * @param float $startLon Longitude de départ
     * @param float $endLat Latitude d'arrivée
     * @param float $endLon Longitude d'arrivée
     * @return float|null Distance en kilomètres ou null en cas d'erreur
     */
    public function calculateDistance(float $startLat, float $startLon, float $endLat, float $endLon): ?float
    {
        try {
            $response = $this->httpClient->request('POST', self::BASE_URL . self::DIRECTIONS_ENDPOINT, [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'coordinates' => [
                        [$startLon, $startLat],
                        [$endLon, $endLat]
                    ]
                ]
            ]);

            $data = $response->toArray();

            if (empty($data['routes'])) {
                $this->logger->warning('Aucun itinéraire trouvé entre les deux points');
                return null;
            }

            // La distance est en mètres, on convertit en kilomètres
            $distanceMeters = $data['routes'][0]['summary']['distance'];
            $distanceKm = $distanceMeters / 1000;

            return round($distanceKm, 2);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Erreur lors du calcul de distance', [
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur inattendue lors du calcul de distance', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calcule la distance entre Bordeaux et une adresse donnée
     *
     * @param string $deliveryAddress Adresse de livraison
     * @return array{distance: float|null, isInBordeaux: bool, postalCode: string|null}
     *         Distance en km (null si erreur), booléen indiquant si l'adresse est dans Bordeaux, et code postal
     */
    public function getDistanceFromBordeaux(string $deliveryAddress): array
    {
        // Géocoder l'adresse de livraison
        $deliveryCoords = $this->geocodeAddress($deliveryAddress);

        if (!$deliveryCoords) {
            $this->logger->error('Impossible de géocoder l\'adresse de livraison', ['address' => $deliveryAddress]);
            return [
                'distance' => null,
                'isInBordeaux' => false,
                'postalCode' => null
            ];
        }

        $postalCode = $deliveryCoords['postalCode'];

        // Vérifier si le code postal est dans la liste des codes postaux de Bordeaux
        $isInBordeaux = $postalCode && in_array($postalCode, self::BORDEAUX_POSTAL_CODES, true);

        // Si on est dans Bordeaux, pas de frais de distance
        if ($isInBordeaux) {
            return [
                'distance' => 0,
                'isInBordeaux' => true,
                'postalCode' => $postalCode
            ];
        }

        // Calculer la distance routière réelle pour les adresses hors Bordeaux
        $roadDistance = $this->calculateDistance(
            self::BORDEAUX_LAT,
            self::BORDEAUX_LON,
            $deliveryCoords['lat'],
            $deliveryCoords['lon']
        );

        return [
            'distance' => $roadDistance,
            'isInBordeaux' => false,
            'postalCode' => $postalCode
        ];
    }

    /**
     * Vérifie si une adresse est dans la ville de Bordeaux (selon les codes postaux)
     *
     * @param string $address Adresse à vérifier
     * @return bool True si l'adresse est dans Bordeaux
     */
    public function isAddressInBordeaux(string $address): bool
    {
        $result = $this->getDistanceFromBordeaux($address);
        return $result['isInBordeaux'];
    }

    /**
     * Extrait le code postal d'une adresse
     *
     * @param string $postalCode Code postal à vérifier
     * @return bool True si le code postal correspond à Bordeaux
     */
    public function isPostalCodeInBordeaux(string $postalCode): bool
    {
        return in_array($postalCode, self::BORDEAUX_POSTAL_CODES, true);
    }
}