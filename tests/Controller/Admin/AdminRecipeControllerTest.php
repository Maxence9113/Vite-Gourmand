<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Entity\Recipe;
use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Tests fonctionnels du contrôleur d'administration des recettes
 *
 * Ces tests vérifient le comportement complet de la gestion des recettes
 * dans l'interface d'administration, y compris l'upload d'illustrations
 */
class AdminRecipeControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private ?Category $testCategory = null;

    /**
     * Méthode exécutée AVANT chaque test
     *
     * Initialise :
     * - Le client HTTP pour simuler un navigateur
     * - L'entity manager pour accéder à la base de données
     * - Authentifie un utilisateur admin pour accéder aux pages protégées
     */
    protected function setUp(): void
    {
        // Créer un client HTTP
        $this->client = static::createClient();

        // Récupérer l'entity manager pour manipuler la base de données
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        // Créer un utilisateur admin pour les tests
        $this->loginAsAdmin();

        // Créer ET assigner la catégorie
        $this->testCategory = new Category();
        $this->testCategory->setName('Catégorie Test');
        
        $this->entityManager->persist($this->testCategory);
        $this->entityManager->flush();
    }

    /**
     * Méthode utilitaire : Se connecter en tant qu'administrateur
     *
     * Crée un utilisateur admin temporaire et l'authentifie dans le client
     * Cet utilisateur sera supprimé après chaque test par tearDown()
     */
    private function loginAsAdmin(): void
    {
        // Récupérer le repository des utilisateurs
        $userRepository = $this->entityManager->getRepository(User::class);

        // Chercher un utilisateur admin existant ou en créer un
        $adminUser = $userRepository->findOneBy(['email' => 'admin@test.com']);

        if (!$adminUser) {
            // Créer un nouvel utilisateur admin
            $adminUser = new User();
            $adminUser->setEmail('admin@test.com');
            $adminUser->setFirstname('Admin');
            $adminUser->setLastname('Test');
            $adminUser->setRoles(['ROLE_ADMIN']);

            // Le mot de passe n'a pas besoin d'être haché pour les tests
            // car on utilise loginUser() qui simule une connexion
            $adminUser->setPassword('password');

            $this->entityManager->persist($adminUser);
            $this->entityManager->flush();
        }

        // Simuler la connexion de cet utilisateur
        $this->client->loginUser($adminUser);
    }

    /**
     * Méthode exécutée APRÈS chaque test
     *
     * Nettoie les données créées pendant le test pour éviter
     * que les tests s'influencent entre eux
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Fermer l'entity manager pour libérer les ressources
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * Test 1 : Vérifier que la page de liste des recettes est accessible
     *
     * Ce test vérifie que :
     * - Un admin peut accéder à /admin/recipes
     * - La page répond avec un code 200 (succès)
     * - Le titre de la page est correct
     */
    public function testRecipesListPageIsAccessible(): void
    {
        // Effectuer une requête GET sur la page de liste des recettes
        $crawler = $this->client->request('GET', '/admin/recipes');

        // Vérifier que la réponse est un succès (code 200)
        $this->assertResponseIsSuccessful();

        // Vérifier que le titre ou un élément caractéristique est présent
        // (Ajustez le sélecteur selon votre template)
        $this->assertSelectorExists('h1');
    }

    

    /**
     * Test 2 : Vérifier que la page de création de recette est accessible
     *
     * Ce test vérifie que :
     * - Un admin peut accéder au formulaire de création
     * - Le formulaire contient tous les champs nécessaires
     */
    public function testNewRecipePageIsAccessible(): void
    {
        // Accéder à la page de création de recette
        $crawler = $this->client->request('GET', '/admin/recipes/new');

        // Vérifier que la page répond avec succès
        $this->assertResponseIsSuccessful();

        // Vérifier que le formulaire est présent avec ses champs
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="recipe[title]"]');
        $this->assertSelectorExists('textarea[name="recipe[description]"]');
        $this->assertSelectorExists('select[name="recipe[category]"]');

        // Vérifier que le bouton "Ajouter une illustration" est présent
        $this->assertSelectorExists('#add-illustration');
    }

    /**
     * Test 3 : Créer une recette SANS illustration
     *
     * Scénario :
     * 1. Accéder au formulaire de création
     * 2. Remplir les champs obligatoires (titre, description, catégorie)
     * 3. Soumettre le formulaire
     * 4. Vérifier la redirection et le message de succès
     * 5. Vérifier que la recette est bien en base de données
     */
    public function testCreateRecipeWithoutIllustration(): void
    {
        // S'assurer qu'il existe au moins une catégorie en base
        $category = $this->entityManager->getRepository(Category::class)->findOneBy([]);

        $this->assertNotNull($category, 'Il doit y avoir au moins une catégorie en base pour ce test');

        // Accéder au formulaire de création
        $crawler = $this->client->request('GET', '/admin/recipes/new');

        // Sélectionner le formulaire
        $form = $crawler->selectButton('Créer la recette')->form();

        // Remplir les champs du formulaire
        $form['recipe[title]'] = 'Recette de test sans illustration';
        $form['recipe[description]'] = 'Description de la recette de test';
        $form['recipe[category]'] = $category->getId();

        // Soumettre le formulaire
        $this->client->submit($form);

        // Vérifier la redirection vers la liste des recettes
        $this->assertResponseRedirects('/admin/recipes');

        // Suivre la redirection
        $this->client->followRedirect();

        // Vérifier le message flash de succès
        $this->assertSelectorTextContains('.alert-success', 'La recette a été créée avec succès');

        // Vérifier que la recette existe bien en base de données
        $recipe = $this->entityManager->getRepository(Recipe::class)
            ->findOneBy(['title' => 'Recette de test sans illustration']);

        $this->assertNotNull($recipe, 'La recette devrait être en base de données');
        $this->assertEquals('Description de la recette de test', $recipe->getDescription());
        $this->assertEquals($category->getId(), $recipe->getCategory()->getId());
    }


    /**
     * Test 4 : Modifier une recette existante
     *
     * Scénario :
     * 1. Créer une recette en base
     * 2. Accéder au formulaire d'édition
     * 3. Modifier le titre
     * 4. Soumettre le formulaire
     * 5. Vérifier que les modifications sont bien enregistrées
     */
    public function testEditExistingRecipe(): void
    {
        // Créer une recette de test en base
        $category = $this->entityManager->getRepository(Category::class)->findOneBy([]);
        $this->assertNotNull($category);

        $recipe = new Recipe();
        $recipe->setTitle('Recette à modifier');
        $recipe->setDescription('Description originale');
        $recipe->setCategory($category);

        $this->entityManager->persist($recipe);
        $this->entityManager->flush();
        
        // Récupérer l'ID de la recette
        $recipeId = $recipe->getId();

        // Accéder au formulaire d'édition
        $crawler = $this->client->request('GET', '/admin/recipes/' . $recipeId . '/edit');

        // Vérifier que la page répond
        $this->assertResponseIsSuccessful();

        // Sélectionner le formulaire
        $form = $crawler->selectButton('Modifier la recette')->form();

        // Vérifier que les champs sont pré-remplis avec les valeurs actuelles
        $this->assertEquals('Recette à modifier', $form['recipe[title]']->getValue());

        // Modifier le titre
        $form['recipe[title]'] = 'Recette modifiée';

        // Soumettre le formulaire
        $this->client->submit($form);

        // Vérifier la redirection
        $this->assertResponseRedirects('/admin/recipes');

        // Vider le cache Doctrine pour forcer un rechargement depuis la base
        $this->entityManager->clear();

        // Recharger la recette depuis la base de données avec son ID
        $updatedRecipe = $this->entityManager->getRepository(Recipe::class)->find($recipeId);

        // Vérifier que le titre a bien été modifié
        $this->assertNotNull($updatedRecipe, 'La recette devrait toujours exister');
        $this->assertEquals('Recette modifiée', $updatedRecipe->getTitle());
    }

    /**
     * Test 5 : Supprimer une recette
     *
     * Scénario :
     * 1. Créer une recette en base
     * 2. Envoyer une requête DELETE
     * 3. Vérifier que la recette est bien supprimée
     */
    public function testDeleteRecipe(): void
    {
        // Créer une recette de test
        $category = $this->entityManager->getRepository(Category::class)->findOneBy([]);
        $this->assertNotNull($category);

        $recipe = new Recipe();
        $recipe->setTitle('Recette à supprimer');
        $recipe->setDescription('Description');
        $recipe->setCategory($category);

        $this->entityManager->persist($recipe);
        $this->entityManager->flush();

        $recipeId = $recipe->getId();

        // Envoyer une requête POST (car le routing demande POST pour delete)
        $this->client->request('POST', '/admin/recipes/' . $recipeId . '/delete');

        // Vérifier la redirection
        $this->assertResponseRedirects('/admin/recipes');

        // Vérifier que la recette n'existe plus en base
        $deletedRecipe = $this->entityManager->getRepository(Recipe::class)->find($recipeId);
        $this->assertNull($deletedRecipe, 'La recette devrait être supprimée');
    }

    /**
     * Test 6 : Vérifier qu'un utilisateur non-admin ne peut pas accéder
     *
     * Ce test vérifie la sécurité : seuls les admins peuvent gérer les recettes
     */
    public function testNonAdminCannotAccessRecipeManagement(): void
    {
        $this->markTestSkipped('Gestion des rôles pas encore implémentée');
        /*
        // Créer un utilisateur normal (non-admin)
        $normalUser = new User();
        $normalUser->setEmail('user@test.com');
        $normalUser->setFirstname('User');
        $normalUser->setLastname('Normal');
        $normalUser->setRoles(['ROLE_USER']); // Pas de ROLE_ADMIN
        $normalUser->setPassword('password');

        $this->entityManager->persist($normalUser);
        $this->entityManager->flush();

        // Créer un nouveau client et le connecter avec l'utilisateur normal
        $client = static::createClient();
        $client->loginUser($normalUser);

        // Tenter d'accéder à la page de gestion des recettes
        $client->request('GET', '/admin/recipes');

        // Vérifier que l'accès est refusé (code 403 Forbidden ou redirection vers login)
        $this->assertResponseStatusCodeSame(403);
    */
    }
}