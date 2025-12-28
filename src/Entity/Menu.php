<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $nb_person_min = null;

    #[ORM\Column]
    private ?int $price_per_person = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $illustration = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $textAlt = null;

    #[ORM\Column(nullable: true)]
    private ?int $stock = null;

    #[ORM\ManyToOne(inversedBy: 'menus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Theme $theme = null;

    /**
     * @var Collection<int, Dietetary>
     */
    #[ORM\ManyToMany(targetEntity: Dietetary::class, inversedBy: 'menus')]
    private Collection $dietetary;

    /**
     * @var Collection<int, Recipe>
     */
    #[ORM\ManyToMany(targetEntity: Recipe::class, inversedBy: 'menus')]
    private Collection $recipes;

    public function __construct()
    {
        $this->dietetary = new ArrayCollection();
        $this->recipes = new ArrayCollection();
    }

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

    public function getNbPersonMin(): ?int
    {
        return $this->nb_person_min;
    }

    public function setNbPersonMin(int $nb_person_min): static
    {
        $this->nb_person_min = $nb_person_min;

        return $this;
    }

    public function getPricePerPerson(): ?int
    {
        return $this->price_per_person;
    }

    public function setPricePerPerson(int $price_per_person): static
    {
        $this->price_per_person = $price_per_person;

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

    public function getIllustration(): ?string
    {
        return $this->illustration;
    }

    public function setIllustration(?string $illustration): static
    {
        $this->illustration = $illustration;

        return $this;
    }

    public function getTextAlt(): ?string
    {
        return $this->textAlt;
    }

    public function setTextAlt(?string $textAlt): static
    {
        $this->textAlt = $textAlt;

        return $this;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function setTheme(?Theme $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * @return Collection<int, Dietetary>
     */
    public function getDietetary(): Collection
    {
        return $this->dietetary;
    }

    public function addDietetary(Dietetary $dietetary): static
    {
        if (!$this->dietetary->contains($dietetary)) {
            $this->dietetary->add($dietetary);
        }

        return $this;
    }

    public function removeDietetary(Dietetary $dietetary): static
    {
        $this->dietetary->removeElement($dietetary);

        return $this;
    }

    /**
     * @return Collection<int, Recipe>
     */
    public function getRecipes(): Collection
    {
        return $this->recipes;
    }

    public function addRecipe(Recipe $recipe): static
    {
        if (!$this->recipes->contains($recipe)) {
            $this->recipes->add($recipe);
        }

        return $this;
    }

    public function removeRecipe(Recipe $recipe): static
    {
        $this->recipes->removeElement($recipe);

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->stock === null || $this->stock >= $this->nb_person_min;
    }

    public function decrementStock(int $quantity = 1): static
    {
        if ($this->stock !== null) {
            $this->stock = max(0, $this->stock - $quantity);
        }

        return $this;
    }
}
