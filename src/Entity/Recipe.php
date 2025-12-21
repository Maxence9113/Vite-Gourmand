<?php

namespace App\Entity;

use App\Repository\RecipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipeRepository::class)]
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'recipes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    /**
     * @var Collection<int, Allergen>
     */
    #[ORM\ManyToMany(targetEntity: Allergen::class, inversedBy: 'recipes')]
    private Collection $allergen;

    /**
     * @var Collection<int, RecipeIllustration>
     */
    #[ORM\OneToMany(targetEntity: RecipeIllustration::class, mappedBy: 'recipe', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $recipeIllustrations;

    public function __construct()
    {
        $this->allergen = new ArrayCollection();
        $this->recipeIllustrations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, Allergen>
     */
    public function getAllergen(): Collection
    {
        return $this->allergen;
    }

    public function addAllergen(Allergen $allergen): static
    {
        if (!$this->allergen->contains($allergen)) {
            $this->allergen->add($allergen);
        }

        return $this;
    }

    public function removeAllergen(Allergen $allergen): static
    {
        $this->allergen->removeElement($allergen);

        return $this;
    }

    /**
     * @return Collection<int, RecipeIllustration>
     */
    public function getRecipeIllustrations(): Collection
    {
        return $this->recipeIllustrations;
    }

    public function addRecipeIllustration(RecipeIllustration $recipeIllustration): static
    {
        if (!$this->recipeIllustrations->contains($recipeIllustration)) {
            $this->recipeIllustrations->add($recipeIllustration);
            $recipeIllustration->setRecipe($this);
        }

        return $this;
    }

    public function removeRecipeIllustration(RecipeIllustration $recipeIllustration): static
    {
        if ($this->recipeIllustrations->removeElement($recipeIllustration)) {
            // set the owning side to null (unless already changed)
            if ($recipeIllustration->getRecipe() === $this) {
                $recipeIllustration->setRecipe(null);
            }
        }

        return $this;
    }
}
