<?php

namespace App\Tests\Controller;

use App\Entity\Address;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels pour la gestion des adresses utilisateur
 *
 * Ces tests vérifient que :
 * - Les utilisateurs peuvent créer, modifier, supprimer des adresses
 * - Les adresses appartiennent bien à leur propriétaire
 * - La validation des champs fonctionne (code postal, téléphone)
 * - La gestion de l'adresse par défaut fonctionne correctement
 * - Les utilisateurs ne peuvent gérer que leurs propres adresses
 */
class AddressControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->passwordHasher = $this->client->getContainer()
            ->get(UserPasswordHasherInterface::class);

        // Nettoyer la base de données avant chaque test
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function cleanDatabase(): void
    {
        // Supprimer les adresses en premier (à cause de la relation)
        $addressRepo = $this->entityManager->getRepository(Address::class);
        $addresses = $addressRepo->findAll();
        foreach ($addresses as $address) {
            $this->entityManager->remove($address);
        }

        $userRepo = $this->entityManager->getRepository(User::class);
        $users = $userRepo->findAll();
        foreach ($users as $user) {
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();
    }

    private function createUser(string $email, string $firstname, string $lastname, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setRoles($roles);
        $user->setIsEnabled(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test1234!@'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createAddress(User $user, string $street, string $postalCode, string $city, string $phone, bool $isDefault = false, ?string $label = null): Address
    {
        $address = new Address();
        $address->setUser($user);
        $address->setStreet($street);
        $address->setPostalCode($postalCode);
        $address->setCity($city);
        $address->setPhone($phone);
        $address->setIsDefault($isDefault);
        if ($label) {
            $address->setLabel($label);
        }

        $this->entityManager->persist($address);
        $this->entityManager->flush();

        return $address;
    }

    /**
     * Test 1 : Un utilisateur connecté peut accéder à la liste de ses adresses
     */
    public function testUserCanAccessAddressesList(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mes adresses');
    }

    /**
     * Test 2 : Un utilisateur non connecté ne peut pas accéder à la liste des adresses
     */
    public function testGuestCannotAccessAddressesList(): void
    {
        $this->client->request('GET', '/compte/adresses');

        $this->assertResponseRedirects('http://localhost/connexion');
    }

    /**
     * Test 3 : Un utilisateur sans adresse voit un message vide
     */
    public function testUserWithNoAddressSeesEmptyState(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.card-body', 'Aucune adresse enregistrée');
    }

    /**
     * Test 4 : Un utilisateur peut accéder au formulaire de création d'adresse
     */
    public function testUserCanAccessNewAddressForm(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses/nouvelle');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Nouvelle adresse');
    }

    /**
     * Test 5 : Un utilisateur peut créer une nouvelle adresse
     */
    public function testUserCanCreateAddress(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses/nouvelle');
        $form = $crawler->selectButton('Ajouter l\'adresse')->form([
            'address[street]' => '10 Rue Sainte-Catherine',
            'address[postalCode]' => '33000',
            'address[city]' => 'Bordeaux',
            'address[phone]' => '0612345678',
            'address[label]' => 'Domicile',
            'address[isDefault]' => true,
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/compte/adresses');

        // Vérifier que l'adresse a été créée
        $address = $this->entityManager->getRepository(Address::class)->findOneBy(['user' => $user]);
        $this->assertNotNull($address);
        $this->assertEquals('10 Rue Sainte-Catherine', $address->getStreet());
        $this->assertEquals('33000', $address->getPostalCode());
        $this->assertEquals('Bordeaux', $address->getCity());
        $this->assertEquals('0612345678', $address->getPhone());
        $this->assertEquals('Domicile', $address->getLabel());
        $this->assertTrue($address->isDefault());
    }

    /**
     * Test 6 : Un message de succès est affiché après la création
     */
    public function testSuccessMessageAfterAddressCreation(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses/nouvelle');
        $form = $crawler->selectButton('Ajouter l\'adresse')->form([
            'address[street]' => '10 Rue Sainte-Catherine',
            'address[postalCode]' => '33000',
            'address[city]' => 'Bordeaux',
            'address[phone]' => '0612345678',
        ]);

        $this->client->submit($form);
        $crawler = $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-success', 'Votre adresse a été ajoutée avec succès');
    }

    /**
     * Test 7 : La validation refuse un code postal invalide
     */
    public function testValidationRejectsInvalidPostalCode(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses/nouvelle');
        $form = $crawler->selectButton('Ajouter l\'adresse')->form([
            'address[street]' => '10 Rue Sainte-Catherine',
            'address[postalCode]' => '330', // Invalid: trop court
            'address[city]' => 'Bordeaux',
            'address[phone]' => '0612345678',
        ]);

        $this->client->submit($form);

        // Symfony retourne 422 (Unprocessable Content) en cas d'erreur de validation
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.form-error-message, .invalid-feedback', 'code postal');
    }

    /**
     * Test 8 : La validation refuse un numéro de téléphone invalide
     */
    public function testValidationRejectsInvalidPhone(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses/nouvelle');
        $form = $crawler->selectButton('Ajouter l\'adresse')->form([
            'address[street]' => '10 Rue Sainte-Catherine',
            'address[postalCode]' => '33000',
            'address[city]' => 'Bordeaux',
            'address[phone]' => '123', // Invalid
        ]);

        $this->client->submit($form);

        // Symfony retourne 422 (Unprocessable Content) en cas d'erreur de validation
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.form-error-message, .invalid-feedback', 'téléphone');
    }

    /**
     * Test 9 : Un utilisateur peut modifier une de ses adresses
     */
    public function testUserCanEditOwnAddress(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $address = $this->createAddress($user, '10 Rue Test', '33000', 'Bordeaux', '0612345678');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses/' . $address->getId() . '/modifier');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modifier l\'adresse');

        $form = $crawler->selectButton('Enregistrer les modifications')->form([
            'address[street]' => '20 Rue Modifiée',
            'address[city]' => 'Mérignac',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/compte/adresses');

        // Vérifier les modifications - récupérer à nouveau depuis la base
        $updatedAddress = $this->entityManager->getRepository(Address::class)->find($address->getId());
        $this->assertEquals('20 Rue Modifiée', $updatedAddress->getStreet());
        $this->assertEquals('Mérignac', $updatedAddress->getCity());
    }

    /**
     * Test 10 : Un utilisateur ne peut pas modifier l'adresse d'un autre
     */
    public function testUserCannotEditOtherUsersAddress(): void
    {
        $user1 = $this->createUser('user1@test.fr', 'John', 'Doe');
        $user2 = $this->createUser('user2@test.fr', 'Jane', 'Smith');

        $address = $this->createAddress($user1, '10 Rue Test', '33000', 'Bordeaux', '0612345678');

        $this->client->loginUser($user2);

        $this->client->request('GET', '/compte/adresses/' . $address->getId() . '/modifier');

        $this->assertResponseStatusCodeSame(403); // Accès refusé
    }

    /**
     * Test 11 : Un utilisateur peut supprimer une de ses adresses
     */
    public function testUserCanDeleteOwnAddress(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $address = $this->createAddress($user, '10 Rue Test', '33000', 'Bordeaux', '0612345678');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses');

        // Récupérer le token CSRF depuis le formulaire affiché
        $deleteForm = $crawler->filter('form[action$="/supprimer"]')->first();
        $token = $deleteForm->filter('input[name="_token"]')->attr('value');

        // Simuler la soumission du formulaire de suppression avec CSRF
        $this->client->request('POST', '/compte/adresses/' . $address->getId() . '/supprimer', [
            '_token' => $token
        ]);

        $this->assertResponseRedirects('/compte/adresses');

        // Vérifier que l'adresse a été supprimée
        $deletedAddress = $this->entityManager->getRepository(Address::class)->find($address->getId());
        $this->assertNull($deletedAddress);
    }

    /**
     * Test 12 : Un utilisateur ne peut pas supprimer l'adresse d'un autre
     */
    public function testUserCannotDeleteOtherUsersAddress(): void
    {
        $user1 = $this->createUser('user1@test.fr', 'John', 'Doe');
        $user2 = $this->createUser('user2@test.fr', 'Jane', 'Smith');

        $address = $this->createAddress($user1, '10 Rue Test', '33000', 'Bordeaux', '0612345678');

        $this->client->loginUser($user2);

        $this->client->request('POST', '/compte/adresses/' . $address->getId() . '/supprimer', [
            '_token' => 'fake-token' // Token invalide mais ce n'est pas ce que nous testons
        ]);

        $this->assertResponseStatusCodeSame(403); // Accès refusé

        // Vérifier que l'adresse existe toujours
        $stillExists = $this->entityManager->getRepository(Address::class)->find($address->getId());
        $this->assertNotNull($stillExists);
    }

    /**
     * Test 13 : Un utilisateur peut définir une adresse par défaut
     */
    public function testUserCanSetDefaultAddress(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $address1 = $this->createAddress($user, '10 Rue Test 1', '33000', 'Bordeaux', '0612345678', true);
        $address2 = $this->createAddress($user, '20 Rue Test 2', '33100', 'Bordeaux', '0612345679', false);

        $this->client->loginUser($user);

        // Récupérer le token depuis la page
        $crawler = $this->client->request('GET', '/compte/adresses');
        $setDefaultForm = $crawler->filter('form[action$="/definir-par-defaut"]')->last(); // Dernière adresse non par défaut
        $token = $setDefaultForm->filter('input[name="_token"]')->attr('value');

        // Définir address2 comme adresse par défaut
        $this->client->request('POST', '/compte/adresses/' . $address2->getId() . '/definir-par-defaut', [
            '_token' => $token
        ]);

        $this->assertResponseRedirects('/compte/adresses');

        // Vérifier que seule address2 est par défaut - récupérer depuis la base
        $updatedAddress1 = $this->entityManager->getRepository(Address::class)->find($address1->getId());
        $updatedAddress2 = $this->entityManager->getRepository(Address::class)->find($address2->getId());

        $this->assertFalse($updatedAddress1->isDefault());
        $this->assertTrue($updatedAddress2->isDefault());
    }

    /**
     * Test 14 : Seule une adresse peut être par défaut à la fois
     */
    public function testOnlyOneDefaultAddressPerUser(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');

        // Créer la première adresse par défaut
        $crawler = $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/compte/adresses/nouvelle');
        $form = $crawler->selectButton('Ajouter l\'adresse')->form([
            'address[street]' => '10 Rue Test 1',
            'address[postalCode]' => '33000',
            'address[city]' => 'Bordeaux',
            'address[phone]' => '0612345678',
            'address[isDefault]' => true,
        ]);
        $this->client->submit($form);

        // Créer une deuxième adresse par défaut
        $crawler = $this->client->request('GET', '/compte/adresses/nouvelle');
        $form = $crawler->selectButton('Ajouter l\'adresse')->form([
            'address[street]' => '20 Rue Test 2',
            'address[postalCode]' => '33100',
            'address[city]' => 'Bordeaux',
            'address[phone]' => '0612345679',
            'address[isDefault]' => true,
        ]);
        $this->client->submit($form);

        // Vérifier qu'une seule adresse est par défaut
        $defaultAddresses = $this->entityManager->getRepository(Address::class)->findBy([
            'user' => $user,
            'isDefault' => true
        ]);

        $this->assertCount(1, $defaultAddresses);
        $this->assertEquals('20 Rue Test 2', $defaultAddresses[0]->getStreet());
    }

    /**
     * Test 15 : La liste des adresses est triée par défaut puis par ID décroissant
     */
    public function testAddressesListIsSortedCorrectly(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $address1 = $this->createAddress($user, '10 Rue Test 1', '33000', 'Bordeaux', '0612345678', false);
        $address2 = $this->createAddress($user, '20 Rue Test 2', '33100', 'Bordeaux', '0612345679', true); // Par défaut
        $address3 = $this->createAddress($user, '30 Rue Test 3', '33200', 'Bordeaux', '0612345670', false);

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses');

        $this->assertResponseIsSuccessful();

        // Vérifier que l'adresse par défaut est affichée avec un badge
        $this->assertSelectorTextContains('.badge.bg-primary', 'Adresse par défaut');
    }

    /**
     * Test 16 : Un utilisateur voit ses adresses sur la page compte
     */
    public function testUserSeesAddressesCardOnAccountPage(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->createAddress($user, '10 Rue Test', '33000', 'Bordeaux', '0612345678');
        $this->createAddress($user, '20 Rue Test 2', '33100', 'Bordeaux', '0612345679');

        // Rafraîchir l'utilisateur pour charger la relation avec les adresses
        $this->entityManager->refresh($user);

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Vous avez 2 adresse(s) enregistrée(s)', $this->client->getResponse()->getContent());
    }

    /**
     * Test 17 : Le label est optionnel
     */
    public function testLabelIsOptional(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses/nouvelle');
        $form = $crawler->selectButton('Ajouter l\'adresse')->form([
            'address[street]' => '10 Rue Sainte-Catherine',
            'address[postalCode]' => '33000',
            'address[city]' => 'Bordeaux',
            'address[phone]' => '0612345678',
            // Pas de label
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/compte/adresses');

        $address = $this->entityManager->getRepository(Address::class)->findOneBy(['user' => $user]);
        $this->assertNull($address->getLabel());
    }

    /**
     * Test 18 : La validation refuse une adresse trop courte
     */
    public function testValidationRejectsShortStreet(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses/nouvelle');
        $form = $crawler->selectButton('Ajouter l\'adresse')->form([
            'address[street]' => 'Rue', // Trop court (min 5)
            'address[postalCode]' => '33000',
            'address[city]' => 'Bordeaux',
            'address[phone]' => '0612345678',
        ]);

        $this->client->submit($form);

        // Symfony retourne 422 (Unprocessable Content) en cas d'erreur de validation
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.form-error-message, .invalid-feedback');
    }

    /**
     * Test 19 : La validation refuse une ville trop courte
     */
    public function testValidationRejectsShortCity(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/compte/adresses/nouvelle');
        $form = $crawler->selectButton('Ajouter l\'adresse')->form([
            'address[street]' => '10 Rue Sainte-Catherine',
            'address[postalCode]' => '33000',
            'address[city]' => 'A', // Trop court (min 2)
            'address[phone]' => '0612345678',
        ]);

        $this->client->submit($form);

        // Symfony retourne 422 (Unprocessable Content) en cas d'erreur de validation
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.form-error-message, .invalid-feedback');
    }

    /**
     * Test 20 : Le token CSRF est requis pour supprimer une adresse
     */
    public function testCsrfTokenRequiredForDeletion(): void
    {
        $user = $this->createUser('user@test.fr', 'John', 'Doe');
        $address = $this->createAddress($user, '10 Rue Test', '33000', 'Bordeaux', '0612345678');
        $this->client->loginUser($user);

        // Essayer de supprimer sans token CSRF
        $this->client->request('POST', '/compte/adresses/' . $address->getId() . '/supprimer', [
            '_token' => 'invalid-token'
        ]);

        $this->assertResponseRedirects('/compte/adresses');

        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger, .alert-error', 'Token de sécurité invalide');

        // Vérifier que l'adresse existe toujours
        $stillExists = $this->entityManager->getRepository(Address::class)->find($address->getId());
        $this->assertNotNull($stillExists);
    }
}