<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactControllerTest extends WebTestCase
{
    public function testContactPageIsAccessible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Contactez-nous');
    }

    public function testContactFormIsDisplayed(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        // Vérifier que le formulaire contient tous les champs
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="contact[name]"]');
        $this->assertSelectorExists('input[name="contact[email]"]');
        $this->assertSelectorExists('input[name="contact[subject]"]');
        $this->assertSelectorExists('textarea[name="contact[message]"]');
        $this->assertSelectorExists('button[type="submit"]');
    }

    public function testSubmitContactFormWithValidData(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        // Remplir et soumettre le formulaire
        $form = $crawler->selectButton('Envoyer le message')->form([
            'contact[name]' => 'Jean Dupont',
            'contact[email]' => 'jean.dupont@example.com',
            'contact[subject]' => 'Question sur un menu',
            'contact[message]' => 'Bonjour, je souhaite des informations sur vos menus pour un événement.',
        ]);

        $client->submit($form);

        // Vérifier la redirection
        $this->assertResponseRedirects('/contact');
        
        // Suivre la redirection
        $client->followRedirect();

        // Vérifier le message de succès
        $this->assertSelectorExists('.alert-success');
    }

    public function testSubmitContactFormWithInvalidEmail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        $form = $crawler->selectButton('Envoyer le message')->form([
            'contact[name]' => 'Jean Dupont',
            'contact[email]' => 'invalid-email',
            'contact[subject]' => 'Question sur un menu',
            'contact[message]' => 'Bonjour, je souhaite des informations.',
        ]);

        $client->submit($form);

        // Le formulaire doit être réaffiché avec le code 422
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.is-invalid, .invalid-feedback');
    }

    public function testSubmitContactFormWithShortMessage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        $form = $crawler->selectButton('Envoyer le message')->form([
            'contact[name]' => 'Jean Dupont',
            'contact[email]' => 'jean@example.com',
            'contact[subject]' => 'Question sur un menu',
            'contact[message]' => 'Court',
        ]);

        $client->submit($form);

        // Le formulaire doit être réaffiché avec le code 422
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.is-invalid, .invalid-feedback');
    }

    public function testSubmitContactFormWithEmptyFields(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        $form = $crawler->selectButton('Envoyer le message')->form([
            'contact[name]' => '',
            'contact[email]' => '',
            'contact[subject]' => '',
            'contact[message]' => '',
        ]);

        $client->submit($form);

        // Le formulaire doit être réaffiché avec le code 422
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.is-invalid, .invalid-feedback');
    }

    public function testContactPageContainsContactInfo(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        // Vérifier que la page contient les informations de contact
        $this->assertSelectorExists('a[href="mailto:contact@vitegourmand.fr"]');
        $this->assertSelectorExists('a[href="tel:+33556123456"]');
    }
}
