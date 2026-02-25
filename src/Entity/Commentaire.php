<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'commentaire_id')]
    private ?int $commentaire_id = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "Le commentaire est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 1000,
        minMessage: "Le commentaire doit faire au moins {{ limit }} caractères",
        maxMessage: "Le commentaire ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $contenu = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(name: 'is_anonyme')]
    private ?bool $is_anonyme = false;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'post_id', nullable: false)]
    private ?Post $post = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updated_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->commentaire_id;
    }

    public function getCommentaireId(): ?int
    {
        return $this->commentaire_id;
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


    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;
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

    public function getAuteurDisplayName(): string
    {
        if ($this->is_anonyme) {
            return 'Anonyme';
        }

        return $this->user?->getFullName() ?? 'Utilisateur';
    }

    public function __toString(): string
    {
        return substr($this->contenu ?? '', 0, 50) . '...';
    }
}
