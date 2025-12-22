<?php

namespace App\Service;

use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Service spécialisé pour l'upload des illustrations de thèmes
 */
class ThemeFileUploader extends FileUploader
{
    public function __construct(
        string $targetDirectory,
        SluggerInterface $slugger
    ) {
        parent::__construct($targetDirectory, $slugger);
    }
}
