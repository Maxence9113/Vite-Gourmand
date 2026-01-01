<?php

namespace App\Tests\Form;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class ContactTypeTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->formFactory = static::getContainer()->get('form.factory');
    }

    public function testFormHasAllRequiredFields(): void
    {
        $form = $this->formFactory->create(ContactType::class);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('subject'));
        $this->assertTrue($form->has('message'));
    }

    public function testNameTooShort(): void
    {
        $formData = [
            'name' => 'A',
            'email' => 'test@example.com',
            'subject' => 'Test subject',
            'message' => 'Test message with enough characters',
        ];

        $form = $this->formFactory->create(ContactType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('name')->getErrors()->count());
    }

    public function testInvalidEmail(): void
    {
        $formData = [
            'name' => 'Jean Dupont',
            'email' => 'invalid-email',
            'subject' => 'Test subject',
            'message' => 'Test message with enough characters',
        ];

        $form = $this->formFactory->create(ContactType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('email')->getErrors()->count());
    }

    public function testSubjectTooShort(): void
    {
        $formData = [
            'name' => 'Jean Dupont',
            'email' => 'test@example.com',
            'subject' => 'Test',
            'message' => 'Test message with enough characters',
        ];

        $form = $this->formFactory->create(ContactType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('subject')->getErrors()->count());
    }

    public function testMessageTooShort(): void
    {
        $formData = [
            'name' => 'Jean Dupont',
            'email' => 'test@example.com',
            'subject' => 'Test subject',
            'message' => 'Short',
        ];

        $form = $this->formFactory->create(ContactType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('message')->getErrors()->count());
    }

    public function testBlankName(): void
    {
        $formData = [
            'name' => '',
            'email' => 'test@example.com',
            'subject' => 'Test subject',
            'message' => 'Test message with enough characters',
        ];

        $form = $this->formFactory->create(ContactType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('name')->getErrors()->count());
    }

    public function testBlankEmail(): void
    {
        $formData = [
            'name' => 'Jean Dupont',
            'email' => '',
            'subject' => 'Test subject',
            'message' => 'Test message with enough characters',
        ];

        $form = $this->formFactory->create(ContactType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('email')->getErrors()->count());
    }
}
