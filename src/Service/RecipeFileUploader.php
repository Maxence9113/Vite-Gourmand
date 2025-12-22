<?php

namespace App\Service;

use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Service spécialisé pour l'upload des illustrations de recettes
 */
class RecipeFileUploader extends FileUploader
{
    public function __construct(
        string $targetDirectory,
        SluggerInterface $slugger
    ) {
        parent::__construct($targetDirectory, $slugger);
    }
}
