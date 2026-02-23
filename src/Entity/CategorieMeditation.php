<?php

namespace App\Entity;

use App\Repository\CategorieMeditationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategorieMeditationRepository::class)]
class CategorieMeditation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $categorie_id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: "Le nom de la catégorie est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le nom doit faire au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ0-9\s\-_',.!?]+$/u",
        message: "Le nom contient des caractères non autorisés"
    )]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        min: 30,
        minMessage: "La description doit contenir au moins {{ limit }} caractères",
        max: 2000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]

    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: "L'URL de l'icône n'est pas valide")]
    #[Assert\Length(
        max: 255,
        maxMessage: "L'URL ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $iconUrl = null;

    #[ORM\OneToMany(targetEntity: SeanceMeditation::class, mappedBy: 'categorie')]
    private Collection $seanceMeditations;

    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'categorieMeditation')]
    private Collection $posts;

    public function __construct()
    {
        $this->seanceMeditations = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->dateCreation = new \DateTimeImmutable();
    }

    // Getters et Setters
    public function getCategorieId(): ?int
    {
        return $this->categorie_id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getIconUrl(): ?string
    {
        return $this->iconUrl;
    }

    public function setIconUrl(?string $iconUrl): static
    {
        $this->iconUrl = $iconUrl;
        return $this;
    }

    /**
     * @return Collection<int, SeanceMeditation>
     */
    public function getSeanceMeditations(): Collection
    {
        return $this->seanceMeditations;
    }

    public function addSeanceMeditation(SeanceMeditation $seanceMeditation): static
    {
        if (!$this->seanceMeditations->contains($seanceMeditation)) {
            $this->seanceMeditations->add($seanceMeditation);
            $seanceMeditation->setCategorie($this);
        }

        return $this;
    }

    public function removeSeanceMeditation(SeanceMeditation $seanceMeditation): static
    {
        if ($this->seanceMeditations->removeElement($seanceMeditation)) {
            // set the owning side to null (unless already changed)
            if ($seanceMeditation->getCategorie() === $this) {
                $seanceMeditation->setCategorie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setCategorieMeditation($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getCategorieMeditation() === $this) {
                $post->setCategorieMeditation(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
