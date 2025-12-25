<?php

namespace App\Tests\Entity;

use App\Entity\Address;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'entité Address
 *
 * Ces tests vérifient que :
 * - Les getters et setters fonctionnent correctement
 * - Les relations avec User sont correctes
 * - Les valeurs par défaut sont appropriées
 */
class AddressTest extends TestCase
{
    /**
     * Test 1 : On peut créer une adresse et définir ses propriétés
     */
    public function testCanCreateAddressWithProperties(): void
    {
        $address = new Address();

        $address->setStreet('10 Rue Sainte-Catherine');
        $address->setPostalCode('33000');
        $address->setCity('Bordeaux');
        $address->setPhone('0612345678');
        $address->setLabel('Domicile');
        $address->setIsDefault(true);

        $this->assertEquals('10 Rue Sainte-Catherine', $address->getStreet());
        $this->assertEquals('33000', $address->getPostalCode());
        $this->assertEquals('Bordeaux', $address->getCity());
        $this->assertEquals('0612345678', $address->getPhone());
        $this->assertEquals('Domicile', $address->getLabel());
        $this->assertTrue($address->isDefault());
    }

    /**
     * Test 2 : Une adresse peut être créée sans label
     */
    public function testAddressCanHaveNullLabel(): void
    {
        $address = new Address();
        $address->setStreet('10 Rue Test');
        $address->setPostalCode('33000');
        $address->setCity('Bordeaux');
        $address->setPhone('0612345678');
        // Pas de label défini

        $this->assertNull($address->getLabel());
    }

    /**
     * Test 3 : Par défaut, isDefault est false
     */
    public function testIsDefaultIsFalseByDefault(): void
    {
        $address = new Address();

        $this->assertFalse($address->isDefault());
    }

    /**
     * Test 4 : On peut associer une adresse à un utilisateur
     */
    public function testCanAssociateAddressWithUser(): void
    {
        $user = new User();
        $user->setEmail('user@test.fr');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $address = new Address();
        $address->setUser($user);

        $this->assertSame($user, $address->getUser());
    }

    /**
     * Test 5 : L'ID est null avant la persistance
     */
    public function testIdIsNullBeforePersistence(): void
    {
        $address = new Address();

        $this->assertNull($address->getId());
    }

    /**
     * Test 6 : On peut modifier isDefault
     */
    public function testCanToggleIsDefault(): void
    {
        $address = new Address();

        $this->assertFalse($address->isDefault());

        $address->setIsDefault(true);
        $this->assertTrue($address->isDefault());

        $address->setIsDefault(false);
        $this->assertFalse($address->isDefault());
    }

    /**
     * Test 7 : On peut modifier le label
     */
    public function testCanUpdateLabel(): void
    {
        $address = new Address();

        $address->setLabel('Domicile');
        $this->assertEquals('Domicile', $address->getLabel());

        $address->setLabel('Travail');
        $this->assertEquals('Travail', $address->getLabel());

        $address->setLabel(null);
        $this->assertNull($address->getLabel());
    }

    /**
     * Test 8 : Toutes les propriétés peuvent être modifiées
     */
    public function testAllPropertiesCanBeUpdated(): void
    {
        $address = new Address();

        $address->setStreet('10 Rue A');
        $address->setPostalCode('33000');
        $address->setCity('Bordeaux');
        $address->setPhone('0612345678');

        // Modifier toutes les propriétés
        $address->setStreet('20 Rue B');
        $address->setPostalCode('33100');
        $address->setCity('Mérignac');
        $address->setPhone('0612345679');

        $this->assertEquals('20 Rue B', $address->getStreet());
        $this->assertEquals('33100', $address->getPostalCode());
        $this->assertEquals('Mérignac', $address->getCity());
        $this->assertEquals('0612345679', $address->getPhone());
    }

    /**
     * Test 9 : L'association avec User est bidirectionnelle
     */
    public function testUserAddressRelationshipIsBidirectional(): void
    {
        $user = new User();
        $user->setEmail('user@test.fr');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $address = new Address();
        $address->setStreet('10 Rue Test');
        $address->setPostalCode('33000');
        $address->setCity('Bordeaux');
        $address->setPhone('0612345678');
        $address->setUser($user);

        // Vérifier que l'adresse est liée à l'utilisateur
        $this->assertSame($user, $address->getUser());

        // Vérifier que l'utilisateur contient l'adresse (via addAddress dans User)
        $user->addAddress($address);
        $this->assertTrue($user->getAddresses()->contains($address));
    }
}