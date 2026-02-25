<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $post_id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 5,
        max: 200,
        minMessage: "Le titre doit faire au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "Le contenu est obligatoire")]
    #[Assert\Length(
        min: 10,
        minMessage: "Le contenu doit faire au moins {{ limit }} caractères"
    )]
    private ?string $contenu = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $is_anonyme = false;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'post', cascade: ['remove'])]
    private Collection $commentaires;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(name: 'categorie_id', referencedColumnName: 'categorie_id')]
    private ?CategorieMeditation $categorieMeditation = null;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->created_at = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updated_at = new \DateTime();
    }

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->post_id;
    }

    public function getPostId(): ?int
    {
        return $this->post_id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function isAnonyme(): bool
    {
        return $this->is_anonyme ?? false;
    }

    public function setAnonyme(bool $is_anonyme): static
    {
        $this->is_anonyme = $is_anonyme;
        return $this;
    }

    public function getIsAnonyme(): bool
    {
        return $this->is_anonyme ?? false;
    }

    public function setIsAnonyme(bool $is_anonyme): static
    {
        $this->is_anonyme = $is_anonyme;
        return $this;
    }


    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setPost($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getPost() === $this) {
                $commentaire->setPost(null);
            }
        }

        return $this;
    }

    public function getCategorieMeditation(): ?CategorieMeditation
    {
        return $this->categorieMeditation;
    }

    public function setCategorieMeditation(?CategorieMeditation $categorieMeditation): static
    {
        $this->categorieMeditation = $categorieMeditation;
        return $this;
    }

    public function getAuteurDisplayName(): string
    {
        if ($this->isAnonyme()) {
            return 'Anonyme';
        }

        return $this->user?->getFullName() ?? 'Utilisateur';
    }

    public function __toString(): string
    {
        return $this->titre ?? '';
    }
}
