<?php

namespace App\Tests\Service;

use App\Service\FileUploader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

/**
 * Tests unitaires du service FileUploader
 *
 * Ces tests vérifient le comportement du service d'upload de fichiers
 * sans nécessiter de base de données ou de serveur web
 */
class FileUploaderTest extends TestCase
{
    private string $testUploadDir;
    private FileUploader $fileUploader;
    private SluggerInterface&\PHPUnit\Framework\MockObject\MockObject $slugger;

    /**
     * Méthode exécutée AVANT chaque test
     */
    protected function setUp(): void
    {
        // Créer un répertoire temporaire pour les tests
        $this->testUploadDir = sys_get_temp_dir() . '/test_uploads_' . uniqid();
        mkdir($this->testUploadDir, 0777, true);

        // Créer un mock du Slugger
        $this->slugger = $this->createMock(SluggerInterface::class);

        // Créer le service FileUploader avec le répertoire de test
        $this->fileUploader = new FileUploader($this->testUploadDir, $this->slugger);
    }

    /**
     * Méthode exécutée APRÈS chaque test
     */
    protected function tearDown(): void
    {
        // Nettoyer : supprimer tous les fichiers du répertoire de test
        if (is_dir($this->testUploadDir)) {
            $files = glob($this->testUploadDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testUploadDir);
        }
    }

    /**
     * Test 1 : Vérifier qu'un fichier peut être uploadé avec succès
     *
     * Ce test vérifie que :
     * - Le fichier est bien déplacé dans le répertoire cible
     * - Le nom du fichier retourné est unique
     * - Le nom du fichier est "slugifié" (nettoyé)
     */
    public function testUploadFileSuccessfully(): void
    {
        // Créer un fichier temporaire pour simuler un upload
        // Créer une vraie image JPEG minimale (1x1 pixel rouge)
        $sourceFile = sys_get_temp_dir() . '/test_image.jpg';
        $image = imagecreatetruecolor(1, 1);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefilledrectangle($image, 0, 0, 1, 1, $red);
        imagejpeg($image, $sourceFile);
        imagedestroy($image);

        // Créer un objet UploadedFile (simule un fichier uploadé)
        $uploadedFile = new UploadedFile(
            $sourceFile,
            'mon image.jpg',
            'image/jpeg',
            null,
            true // mode test
        );

        // Configurer le mock du slugger pour qu'il transforme "mon image" en "mon-image"
        $slugged = new UnicodeString('mon-image');
        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('mon image')
            ->willReturn($slugged);

        // Uploader le fichier
        $fileName = $this->fileUploader->upload($uploadedFile);

        // Vérifications
        $this->assertNotEmpty($fileName, 'Le nom du fichier ne devrait pas être vide');
        $this->assertStringStartsWith('mon-image-', $fileName, 'Le nom devrait commencer par "mon-image-"');
        $this->assertTrue(
            str_ends_with($fileName, '.jpeg') || str_ends_with($fileName, '.jpg'),
            'Le nom devrait se terminer par .jpeg ou .jpg'
        );

        // Vérifier que le fichier existe dans le répertoire cible
        $uploadedFilePath = $this->testUploadDir . '/' . $fileName;
        $this->assertFileExists($uploadedFilePath, 'Le fichier devrait exister dans le répertoire cible');

        // Vérifier que c'est bien une image
        $this->assertNotFalse(getimagesize($uploadedFilePath), 'Le fichier devrait être une image valide');
    }

    /**
     * Test 2 : Vérifier que les noms de fichiers sont uniques
     *
     * Ce test vérifie que :
     * - Deux uploads du même fichier génèrent des noms différents
     * - Cela évite les écrasements de fichiers
     */
    public function testUploadGeneratesUniqueFileNames(): void
    {
        // Créer deux fichiers temporaires identiques
        $sourceFile1 = sys_get_temp_dir() . '/test1.jpg';
        $sourceFile2 = sys_get_temp_dir() . '/test2.jpg';
        file_put_contents($sourceFile1, 'content 1');
        file_put_contents($sourceFile2, 'content 2');

        $uploadedFile1 = new UploadedFile($sourceFile1, 'photo.jpg', 'image/jpeg', null, true);
        $uploadedFile2 = new UploadedFile($sourceFile2, 'photo.jpg', 'image/jpeg', null, true);

        // Configurer le slugger pour retourner le même slug
        $slugged = new UnicodeString('photo');
        $this->slugger
            ->expects($this->exactly(2))
            ->method('slug')
            ->with('photo')
            ->willReturn($slugged);

        // Uploader les deux fichiers
        $fileName1 = $this->fileUploader->upload($uploadedFile1);
        $fileName2 = $this->fileUploader->upload($uploadedFile2);

        // Vérifier que les noms sont différents
        $this->assertNotEquals($fileName1, $fileName2, 'Les noms de fichiers devraient être différents');

        // Vérifier que les deux fichiers existent
        $this->assertFileExists($this->testUploadDir . '/' . $fileName1);
        $this->assertFileExists($this->testUploadDir . '/' . $fileName2);
    }

    /**
     * Test 3 : Vérifier le getter du répertoire cible
     */
    public function testGetTargetDirectory(): void
    {
        $targetDir = $this->fileUploader->getTargetDirectory();

        $this->assertEquals($this->testUploadDir, $targetDir);
    }

    /**
     * Test 4 : Vérifier qu'un fichier peut être supprimé
     *
     * Ce test vérifie que :
     * - La méthode remove() supprime bien le fichier du système
     */
    public function testRemoveFileSuccessfully(): void
    {
        // Créer un fichier dans le répertoire de test
        $fileName = 'test-file.jpg';
        $filePath = $this->testUploadDir . '/' . $fileName;
        file_put_contents($filePath, 'test content');

        // Vérifier que le fichier existe
        $this->assertFileExists($filePath);

        // Supprimer le fichier via le service
        $this->fileUploader->remove($fileName);

        // Vérifier que le fichier n'existe plus
        $this->assertFileDoesNotExist($filePath, 'Le fichier devrait être supprimé');
    }

    /**
     * Test 5 : Vérifier que la suppression d'un fichier inexistant ne génère pas d'erreur
     *
     * Ce test vérifie que :
     * - Appeler remove() sur un fichier qui n'existe pas ne génère pas d'exception
     */
    public function testRemoveNonExistentFileDoesNotThrowException(): void
    {
        // Tenter de supprimer un fichier qui n'existe pas
        $this->fileUploader->remove('fichier-inexistant.jpg');

        // Si on arrive ici sans exception, le test passe
        $this->assertTrue(true);
    }

    /**
     * Test 6 : Vérifier qu'une exception est levée si le répertoire n'est pas accessible
     *
     * Ce test vérifie que :
     * - Une FileException est levée si l'upload échoue
     */
    public function testUploadThrowsExceptionWhenDirectoryNotWritable(): void
    {
        // Créer un répertoire en lecture seule
        $readOnlyDir = sys_get_temp_dir() . '/readonly_' . uniqid();
        mkdir($readOnlyDir, 0444, true); // Permissions en lecture seule

        $fileUploader = new FileUploader($readOnlyDir, $this->slugger);

        // Créer un fichier à uploader
        $sourceFile = sys_get_temp_dir() . '/test.jpg';
        file_put_contents($sourceFile, 'content');
        $uploadedFile = new UploadedFile($sourceFile, 'test.jpg', 'image/jpeg', null, true);

        $slugged = new UnicodeString('test');
        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('test')
            ->willReturn($slugged);

        // Attendre une FileException
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Erreur lors de l\'upload du fichier');

        // Tenter l'upload (devrait échouer)
        $fileUploader->upload($uploadedFile);

        // Nettoyer
        chmod($readOnlyDir, 0777);
        rmdir($readOnlyDir);
    }

    /**
     * Test 7 : Vérifier que le slugger est bien utilisé pour nettoyer les noms
     *
     * Ce test vérifie que :
     * - Les caractères spéciaux sont bien "nettoyés" par le slugger
     */
    public function testUploadUsesSluggerToCleanFilename(): void
    {
        $sourceFile = sys_get_temp_dir() . '/test.jpg';
        file_put_contents($sourceFile, 'content');

        $uploadedFile = new UploadedFile(
            $sourceFile,
            'Fichier avec des espaces & caractères spéciaux!.jpg',
            'image/jpeg',
            null,
            true
        );

        // Le slugger devrait être appelé avec le nom sans extension
        $slugged = new UnicodeString('fichier-avec-des-espaces-caracteres-speciaux');
        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->with('Fichier avec des espaces & caractères spéciaux!')
            ->willReturn($slugged);

        $fileName = $this->fileUploader->upload($uploadedFile);

        // Vérifier que le nom commence par le nom slugifié
        $this->assertStringStartsWith('fichier-avec-des-espaces-caracteres-speciaux-', $fileName);
    }
}