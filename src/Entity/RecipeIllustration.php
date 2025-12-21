<?php

namespace App\Entity;

use App\Repository\RecipeIllustrationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecipeIllustrationRepository::class)]
class RecipeIllustration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom du fichier stocké sur le serveur
     * Ex: "mon-plat-507f1f77bcf86cd799439011.jpg"
     */
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * URL relative pour afficher l'image dans les templates
     * Ex: "/uploads/recipe_illustrations/mon-plat-507f1f77bcf86cd799439011.jpg"
     */
    #[ORM\Column(length: 255)]
    private ?string $url = null;

    /**
     * Texte alternatif pour l'accessibilité et le SEO
     * Décrit le contenu de l'image pour les lecteurs d'écran
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $alt_text = null;

    /**
     * Relation Many-to-One: Une illustration appartient à une recette
     */
    #[ORM\ManyToOne(inversedBy: 'recipeIllustrations')]
    private ?Recipe $recipe = null;

    /**
     * Propriété NON mappée en base de données (transient)
     * Sert uniquement à stocker temporairement le fichier uploadé depuis le formulaire
     * Cette propriété n'existe que pendant le traitement de la requête
     */
    #[Assert\NotNull(message: 'Veuillez télécharger une image')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Veuillez uploader une image valide (JPEG, PNG ou WebP)'
    )]
    private ?File $imageFile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getAltText(): ?string
    {
        return $this->alt_text;
    }

    public function setAltText(?string $alt_text): static
    {
        $this->alt_text = $alt_text;

        return $this;
    }

    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }

    public function setRecipe(?Recipe $recipe): static
    {
        $this->recipe = $recipe;

        return $this;
    }

    /**
     * Récupère le fichier image uploadé depuis le formulaire
     */
    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    /**
     * Définit le fichier image uploadé depuis le formulaire
     * C'est cette méthode qui sera appelée par le formulaire lors de la soumission
     */
    public function setImageFile(?File $imageFile): static
    {
        $this->imageFile = $imageFile;

        return $this;
    }
}