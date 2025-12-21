<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Service responsable de l'upload de fichiers
 *
 * Ce service centralise la logique d'upload pour éviter la duplication de code
 * et faciliter la maintenance
 */
class FileUploader
{
    /**
     * @param string $targetDirectory Le répertoire où les fichiers seront stockés
     * @param SluggerInterface $slugger Service pour "nettoyer" les noms de fichiers (enlever accents, espaces, etc.)
     */
    public function __construct(
        private string $targetDirectory,
        private SluggerInterface $slugger,
    ) {
    }

    /**
     * Upload un fichier dans le répertoire cible
     *
     * @param UploadedFile $file Le fichier uploadé depuis le formulaire
     * @return string Le nom du fichier généré (unique)
     * @throws FileException Si l'upload échoue
     */
    public function upload(UploadedFile $file): string
    {
        // 1. Récupérer le nom original du fichier (sans l'extension)
        // Ex: "mon image.jpg" -> "mon image"
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // 2. Nettoyer le nom du fichier pour éviter les problèmes
        // Ex: "mon image" -> "mon-image"
        $safeFilename = $this->slugger->slug($originalFilename);

        // 3. Créer un nom de fichier unique pour éviter les doublons
        // Ex: "mon-image-507f1f77bcf86cd799439011.jpg"
        // uniqid() génère un identifiant unique basé sur le timestamp
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        // 4. Tenter de déplacer le fichier vers le répertoire cible
        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            // En cas d'erreur (permissions, disque plein, etc.), on relance une exception claire
            throw new FileException('Erreur lors de l\'upload du fichier: ' . $e->getMessage());
        }

        // 5. Retourner le nom du fichier pour le stocker en base de données
        return $fileName;
    }

    /**
     * Retourne le répertoire cible des uploads
     */
    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    /**
     * Supprime un fichier du système de fichiers
     *
     * @param string $fileName Le nom du fichier à supprimer
     */
    public function remove(string $fileName): void
    {
        $filePath = $this->getTargetDirectory() . '/' . $fileName;

        // Vérifier que le fichier existe avant de tenter de le supprimer
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}