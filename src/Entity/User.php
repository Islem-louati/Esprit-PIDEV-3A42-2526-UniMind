<?php

namespace App\Entity;

use App\Enum\RoleType;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $user_id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $nom;

    #[ORM\Column(type: 'string', length: 255)]
    private string $prenom;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', enumType: RoleType::class)]
    private RoleType $role;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', length: 50)]
    private string $statut;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'boolean', name: 'is_verified')]
    private bool $isVerified;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $cin = null;

    #[ORM\Column(type: 'string', nullable: true, name: 'verification_token')]
    private ?string $verificationToken = null;

    #[ORM\Column(type: 'datetime', nullable: true, name: 'token_expires_at')]
    private ?\DateTimeInterface $tokenExpiresAt = null;

    #[ORM\Column(type: 'string', nullable: true, name: 'reset_token')]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true, name: 'reset_token_expires_at')]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $identifiant = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, name: 'nom_etablissement')]
    private ?string $nomEtablissement = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $specialite = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $poste = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $etablissement = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Profil::class, cascade: ['persist', 'remove'])]
    private ?Profil $profil = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: DisponibilitePsy::class, orphanRemoval: true)]
    private Collection $disponibilites;

    #[ORM\OneToMany(mappedBy: 'etudiant', targetEntity: RendezVous::class)]
    private Collection $rendezVousEtudiant;

    #[ORM\OneToMany(mappedBy: 'psy', targetEntity: RendezVous::class)]
    private Collection $rendezVousPsy;

    #[ORM\OneToMany(mappedBy: 'psy', targetEntity: Consultation::class)]
    private Collection $consultationsPsy;

    #[ORM\OneToMany(mappedBy: 'etudiant', targetEntity: Consultation::class)]
    private Collection $consultationsEtudiant;

    #[ORM\OneToMany(mappedBy: 'admin', targetEntity: Questionnaire::class)]
    private Collection $questionnairesAdmin;

    #[ORM\OneToMany(mappedBy: 'etudiant', targetEntity: ReponseQuestionnaire::class)]
    private Collection $reponsesQuestionnaires;

    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'user')]
    private Collection $posts;

    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'user')]
    private Collection $commentaires;

    #[ORM\OneToMany(mappedBy: 'psychologue', targetEntity: Traitement::class)]
    private Collection $traitementsPsychologue;

    #[ORM\OneToMany(mappedBy: 'etudiant', targetEntity: Traitement::class)]
    private Collection $traitementsEtudiant;

    #[ORM\OneToMany(mappedBy: 'organisateur', targetEntity: Evenement::class)]
    private Collection $evenementsOrganises;

    #[ORM\OneToMany(mappedBy: 'etudiant', targetEntity: Participation::class)]
    private Collection $participations;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->isActive = true;
        $this->isVerified = false;
        $this->statut = 'actif';
        $this->role = RoleType::ETUDIANT;
        $this->disponibilites = new ArrayCollection();
        $this->rendezVousEtudiant = new ArrayCollection();
        $this->rendezVousPsy = new ArrayCollection();
        $this->consultationsPsy = new ArrayCollection();
        $this->consultationsEtudiant = new ArrayCollection();
        $this->questionnairesAdmin = new ArrayCollection();
        $this->reponsesQuestionnaires = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->traitementsPsychologue = new ArrayCollection();
        $this->traitementsEtudiant = new ArrayCollection();
        $this->evenementsOrganises = new ArrayCollection();
        $this->participations = new ArrayCollection();
    }

    // Ajoutez ces méthodes pour les questionnaires
    public function getQuestionnairesAdmin(): Collection
    {
        return $this->questionnairesAdmin;
    }

    public function addQuestionnairesAdmin(Questionnaire $questionnairesAdmin): self
    {
        if (!$this->questionnairesAdmin->contains($questionnairesAdmin)) {
            $this->questionnairesAdmin[] = $questionnairesAdmin;
            $questionnairesAdmin->setAdmin($this);
        }

        return $this;
    }

    public function removeQuestionnairesAdmin(Questionnaire $questionnairesAdmin): self
    {
        if ($this->questionnairesAdmin->removeElement($questionnairesAdmin)) {
            // set the owning side to null (unless already changed)
            if ($questionnairesAdmin->getAdmin() === $this) {
                $questionnairesAdmin->setAdmin(null);
            }
        }

        return $this;
    }

    // Méthodes pour les réponses aux questionnaires
    public function getReponsesQuestionnaires(): Collection
    {
        return $this->reponsesQuestionnaires;
    }

    public function addReponsesQuestionnaire(ReponseQuestionnaire $reponsesQuestionnaire): self
    {
        if (!$this->reponsesQuestionnaires->contains($reponsesQuestionnaire)) {
            $this->reponsesQuestionnaires[] = $reponsesQuestionnaire;
            $reponsesQuestionnaire->setEtudiant($this);
        }

        return $this;
    }

    public function removeReponsesQuestionnaire(ReponseQuestionnaire $reponsesQuestionnaire): self
    {
        if ($this->reponsesQuestionnaires->removeElement($reponsesQuestionnaire)) {
            // set the owning side to null (unless already changed)
            if ($reponsesQuestionnaire->getEtudiant() === $this) {
                $reponsesQuestionnaire->setEtudiant(null);
            }
        }

        return $this;
    }

    // Méthode pour vérifier si l'utilisateur a répondu à un questionnaire
    public function hasReponduQuestionnaire(Questionnaire $questionnaire): bool
    {
        foreach ($this->reponsesQuestionnaires as $reponse) {
            if ($reponse->getQuestionnaire() === $questionnaire) {
                return true;
            }
        }

        return false;
    }

    // Méthode pour obtenir la dernière réponse à un questionnaire
    public function getDerniereReponseQuestionnaire(Questionnaire $questionnaire): ?ReponseQuestionnaire
    {
        $derniereReponse = null;
        foreach ($this->reponsesQuestionnaires as $reponse) {
            if ($reponse->getQuestionnaire() === $questionnaire) {
                if (!$derniereReponse || $reponse->getCreatedAt() > $derniereReponse->getCreatedAt()) {
                    $derniereReponse = $reponse;
                }
            }
        }

        return $derniereReponse;
    }

    // Méthode pour obtenir toutes les réponses à un questionnaire
    public function getReponsesPourQuestionnaire(Questionnaire $questionnaire): array
    {
        $reponses = [];
        foreach ($this->reponsesQuestionnaires as $reponse) {
            if ($reponse->getQuestionnaire() === $questionnaire) {
                $reponses[] = $reponse;
            }
        }

        // Trier par date (plus récent d'abord)
        usort($reponses, function ($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        return $reponses;
    }

    // Méthode pour obtenir le score moyen pour un type de questionnaire
    public function getScoreMoyenParType(string $typeQuestionnaire): ?float
    {
        $total = 0;
        $count = 0;

        foreach ($this->reponsesQuestionnaires as $reponse) {
            $questionnaire = $reponse->getQuestionnaire();
            if ($questionnaire && $questionnaire->getType() === $typeQuestionnaire) {
                $total += $reponse->getScoreTotale();
                $count++;
            }
        }

        if ($count === 0) {
            return null;
        }

        return $total / $count;
    }

    // Méthode pour vérifier si l'utilisateur a besoin d'un psy selon ses réponses
    public function aBesoinPsy(): bool
    {
        foreach ($this->reponsesQuestionnaires as $reponse) {
            if ($reponse->isABesoinPsy()) {
                return true;
            }
        }

        return false;
    }

    // Méthodes pour les consultations en tant que psychologue
    public function getConsultationsPsy(): Collection
    {
        return $this->consultationsPsy;
    }

    public function addConsultationsPsy(Consultation $consultationsPsy): self
    {
        if (!$this->consultationsPsy->contains($consultationsPsy)) {
            $this->consultationsPsy[] = $consultationsPsy;
            $consultationsPsy->setPsy($this);
        }

        return $this;
    }

    public function removeConsultationsPsy(Consultation $consultationsPsy): self
    {
        if ($this->consultationsPsy->removeElement($consultationsPsy)) {
            // set the owning side to null (unless already changed)
            if ($consultationsPsy->getPsy() === $this) {
                $consultationsPsy->setPsy(null);
            }
        }

        return $this;
    }

    // Méthodes pour les consultations en tant qu'étudiant
    public function getConsultationsEtudiant(): Collection
    {
        return $this->consultationsEtudiant;
    }

    public function addConsultationsEtudiant(Consultation $consultationsEtudiant): self
    {
        if (!$this->consultationsEtudiant->contains($consultationsEtudiant)) {
            $this->consultationsEtudiant[] = $consultationsEtudiant;
            $consultationsEtudiant->setEtudiant($this);
        }

        return $this;
    }

    public function removeConsultationsEtudiant(Consultation $consultationsEtudiant): self
    {
        if ($this->consultationsEtudiant->removeElement($consultationsEtudiant)) {
            // set the owning side to null (unless already changed)
            if ($consultationsEtudiant->getEtudiant() === $this) {
                $consultationsEtudiant->setEtudiant(null);
            }
        }

        return $this;
    }

    // Méthode pour obtenir toutes les consultations (en tant que psy ou étudiant)
    public function getAllConsultations(): Collection
    {
        return new ArrayCollection(
            array_merge(
                $this->consultationsPsy->toArray(),
                $this->consultationsEtudiant->toArray()
            )
        );
    }

    // Méthodes pour les rendez-vous étudiant
    public function getRendezVousEtudiant(): Collection
    {
        return $this->rendezVousEtudiant;
    }

    public function addRendezVousEtudiant(RendezVous $rendezVousEtudiant): self
    {
        if (!$this->rendezVousEtudiant->contains($rendezVousEtudiant)) {
            $this->rendezVousEtudiant[] = $rendezVousEtudiant;
            $rendezVousEtudiant->setEtudiant($this);
        }

        return $this;
    }

    public function removeRendezVousEtudiant(RendezVous $rendezVousEtudiant): self
    {
        if ($this->rendezVousEtudiant->removeElement($rendezVousEtudiant)) {
            // set the owning side to null (unless already changed)
            if ($rendezVousEtudiant->getEtudiant() === $this) {
                $rendezVousEtudiant->setEtudiant(null);
            }
        }

        return $this;
    }

    // Méthodes pour les rendez-vous psychologue
    public function getRendezVousPsy(): Collection
    {
        return $this->rendezVousPsy;
    }

    public function addRendezVousPsy(RendezVous $rendezVousPsy): self
    {
        if (!$this->rendezVousPsy->contains($rendezVousPsy)) {
            $this->rendezVousPsy[] = $rendezVousPsy;
            $rendezVousPsy->setPsy($this);
        }

        return $this;
    }

    public function removeRendezVousPsy(RendezVous $rendezVousPsy): self
    {
        if ($this->rendezVousPsy->removeElement($rendezVousPsy)) {
            // set the owning side to null (unless already changed)
            if ($rendezVousPsy->getPsy() === $this) {
                $rendezVousPsy->setPsy(null);
            }
        }

        return $this;
    }

    // Méthodes utilitaires pour les rendez-vous
    public function getRendezVousFuturs(): Collection
    {
        return $this->rendezVousEtudiant->filter(function (RendezVous $rdv) {
            return !$rdv->isPassed() && !$rdv->isAnnule();
        });
    }

    public function getRendezVousEnCours(): Collection
    {
        return $this->rendezVousEtudiant->filter(function (RendezVous $rdv) {
            return $rdv->isEnCours();
        });
    }

    public function getRendezVousConfirme(): Collection
    {
        return $this->rendezVousEtudiant->filter(function (RendezVous $rdv) {
            return $rdv->isConfirme();
        });
    }

    // Getters et setters
    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = [$this->role->value];

        // Ajouter ROLE_ préfix pour Symfony Security
        $symfonyRoles = [];
        foreach ($roles as $role) {
            $symfonyRoles[] = 'ROLE_' . strtoupper(str_replace(' ', '_', $role));
        }

        // Ajouter ROLE_USER par défaut
        $symfonyRoles[] = 'ROLE_USER';

        return array_unique($symfonyRoles);
    }

    public function getRole(): RoleType
    {
        return $this->role;
    }

    public function setRole(RoleType $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function addRole(RoleType $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function removeRole(RoleType $role): self
    {
        if ($this->role === $role) {
            $this->role = RoleType::ETUDIANT;
        }
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function isIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isIsVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function setCin(?string $cin): self
    {
        $this->cin = $cin;
        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): self
    {
        $this->verificationToken = $verificationToken;
        return $this;
    }

    public function getTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->tokenExpiresAt;
    }

    public function setTokenExpiresAt(?\DateTimeInterface $tokenExpiresAt): self
    {
        $this->tokenExpiresAt = $tokenExpiresAt;
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): self
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }

    public function getIdentifiant(): ?string
    {
        return $this->identifiant;
    }

    public function setIdentifiant(?string $identifiant): self
    {
        $this->identifiant = $identifiant;
        return $this;
    }

    public function getNomEtablissement(): ?string
    {
        return $this->nomEtablissement;
    }

    public function setNomEtablissement(?string $nomEtablissement): self
    {
        $this->nomEtablissement = $nomEtablissement;
        return $this;
    }

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(?string $specialite): self
    {
        $this->specialite = $specialite;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getPoste(): ?string
    {
        return $this->poste;
    }

    public function setPoste(?string $poste): self
    {
        $this->poste = $poste;
        return $this;
    }

    public function getEtablissement(): ?string
    {
        return $this->etablissement;
    }

    public function setEtablissement(?string $etablissement): self
    {
        $this->etablissement = $etablissement;
        return $this;
    }

    public function getProfil(): ?Profil
    {
        return $this->profil;
    }

    public function setProfil(?Profil $profil): self
    {
        // unset the owning side of the relation if necessary
        if ($profil === null && $this->profil !== null) {
            $this->profil->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($profil !== null && $profil->getUser() !== $this) {
            $profil->setUser($this);
        }

        $this->profil = $profil;
        return $this;
    }

    // Méthodes pour les disponibilités
    public function getDisponibilites(): Collection
    {
        return $this->disponibilites;
    }

    public function addDisponibilite(DisponibilitePsy $disponibilite): self
    {
        if (!$this->disponibilites->contains($disponibilite)) {
            $this->disponibilites[] = $disponibilite;
            $disponibilite->setUser($this);
        }

        return $this;
    }

    public function removeDisponibilite(DisponibilitePsy $disponibilite): self
    {
        if ($this->disponibilites->removeElement($disponibilite)) {
            // set the owning side to null (unless already changed)
            if ($disponibilite->getUser() === $this) {
                $disponibilite->setUser(null);
            }
        }

        return $this;
    }

    // Méthode pour obtenir les disponibilités futures
    public function getDisponibilitesFutures(): Collection
    {
        return $this->disponibilites->filter(function (DisponibilitePsy $dispo) {
            return $dispo->getDateTimeFin() > new \DateTime();
        });
    }

    // Méthode pour obtenir les disponibilités disponibles
    public function getDisponibilitesDisponibles(): Collection
    {
        return $this->disponibilites->filter(function (DisponibilitePsy $dispo) {
            return $dispo->isDisponible() && $dispo->getDateTimeFin() > new \DateTime();
        });
    }

    // Méthodes de l'interface UserInterface
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    // Méthodes utilitaires
    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function __toString(): string
    {
        return $this->getFullName() . ' (' . $this->email . ')';
    }

    // Méthodes pour vérifier les tokens
    public function isVerificationTokenValid(): bool
    {
        if (!$this->verificationToken || !$this->tokenExpiresAt) {
            return false;
        }

        return $this->tokenExpiresAt > new \DateTime();
    }

    public function isResetTokenValid(): bool
    {
        if (!$this->resetToken || !$this->resetTokenExpiresAt) {
            return false;
        }

        return $this->resetTokenExpiresAt > new \DateTime();
    }

    // Méthodes pour générer des tokens
    public function generateVerificationToken(): void
    {
        $this->verificationToken = bin2hex(random_bytes(32));
        $this->tokenExpiresAt = (new \DateTime())->modify('+24 hours');
    }

    public function generateResetToken(): void
    {
        $this->resetToken = bin2hex(random_bytes(32));
        $this->resetTokenExpiresAt = (new \DateTime())->modify('+1 hour');
    }

    // Méthodes pour nettoyer les tokens
    public function clearVerificationToken(): void
    {
        $this->verificationToken = null;
        $this->tokenExpiresAt = null;
    }

    public function clearResetToken(): void
    {
        $this->resetToken = null;
        $this->resetTokenExpiresAt = null;
    }

    // Méthodes pour les rôles spécifiques
    public function isEtudiant(): bool
    {
        return $this->hasRole('ROLE_ETUDIANT');
    }

    public function isPsychologue(): bool
    {
        return $this->hasRole('ROLE_PSYCHOLOGUE');
    }

    public function isResponsableEtudiant(): bool
    {
        return $this->hasRole('ROLE_RESPONSABLE_ETUDIANT');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleType::ADMIN->value);
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
            $post->setUser($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getUser() === $this) {
                $post->setUser(null);
            }
        }

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
            $commentaire->setUser($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getUser() === $this) {
                $commentaire->setUser(null);
            }
        }

        return $this;
    }

    // Méthodes pour les traitements en tant que psychologue
    public function getTraitementsPsychologue(): Collection
    {
        return $this->traitementsPsychologue;
    }

    public function addTraitementsPsychologue(Traitement $traitementsPsychologue): self
    {
        if (!$this->traitementsPsychologue->contains($traitementsPsychologue)) {
            $this->traitementsPsychologue[] = $traitementsPsychologue;
            $traitementsPsychologue->setPsychologue($this);
        }

        return $this;
    }

    public function removeTraitementsPsychologue(Traitement $traitementsPsychologue): self
    {
        if ($this->traitementsPsychologue->removeElement($traitementsPsychologue)) {
            // set the owning side to null (unless already changed)
            if ($traitementsPsychologue->getPsychologue() === $this) {
                $traitementsPsychologue->setPsychologue(null);
            }
        }

        return $this;
    }

    // Méthodes pour les traitements en tant qu'étudiant
    public function getTraitementsEtudiant(): Collection
    {
        return $this->traitementsEtudiant;
    }

    public function addTraitementsEtudiant(Traitement $traitementsEtudiant): self
    {
        if (!$this->traitementsEtudiant->contains($traitementsEtudiant)) {
            $this->traitementsEtudiant[] = $traitementsEtudiant;
            $traitementsEtudiant->setEtudiant($this);
        }

        return $this;
    }

    public function removeTraitementsEtudiant(Traitement $traitementsEtudiant): self
    {
        if ($this->traitementsEtudiant->removeElement($traitementsEtudiant)) {
            // set the owning side to null (unless already changed)
            if ($traitementsEtudiant->getEtudiant() === $this) {
                $traitementsEtudiant->setEtudiant(null);
            }
        }

        return $this;
    }

    // Méthodes utilitaires pour les traitements
    public function getTraitementsActifs(): array
    {
        $traitementsActifs = [];

        foreach ($this->traitementsEtudiant as $traitement) {
            if ($traitement->isEnCours()) {
                $traitementsActifs[] = $traitement;
            }
        }

        return $traitementsActifs;
    }

    public function getNombreTraitementsActifs(): int
    {
        return count($this->getTraitementsActifs());
    }

    public function getTraitementsTermines(): array
    {
        $traitementsTermines = [];

        foreach ($this->traitementsEtudiant as $traitement) {
            if ($traitement->isTermine()) {
                $traitementsTermines[] = $traitement;
            }
        }

        return $traitementsTermines;
    }

    public function getTraitementsPrioritaires(): array
    {
        $traitementsPrioritaires = [];

        foreach ($this->traitementsEtudiant as $traitement) {
            if ($traitement->isPrioriteHaute() && $traitement->isEnCours()) {
                $traitementsPrioritaires[] = $traitement;
            }
        }

        return $traitementsPrioritaires;
    }

    // Méthode pour obtenir les traitements en retard
    public function getTraitementsEnRetard(): array
    {
        $traitementsEnRetard = [];

        foreach ($this->traitementsEtudiant as $traitement) {
            if ($traitement->isEnRetard()) {
                $traitementsEnRetard[] = $traitement;
            }
        }

        return $traitementsEnRetard;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsOrganises(): Collection
    {
        return $this->evenementsOrganises;
    }

    public function addEvenementsOrganise(Evenement $evenementsOrganise): self
    {
        if (!$this->evenementsOrganises->contains($evenementsOrganise)) {
            $this->evenementsOrganises->add($evenementsOrganise);
            $evenementsOrganise->setOrganisateur($this);
        }

        return $this;
    }

    public function removeEvenementsOrganise(Evenement $evenementsOrganise): self
    {
        if ($this->evenementsOrganises->removeElement($evenementsOrganise)) {
            // set the owning side to null (unless already changed)
            if ($evenementsOrganise->getOrganisateur() === $this) {
                $evenementsOrganise->setOrganisateur(null);
            }
        }

        return $this;
    }

    // Méthode pour vérifier si l'utilisateur peut organiser des événements
    public function peutOrganiserEvenements(): bool
    {
        return $this->isResponsableEtudiant() || $this->isAdmin();
    }

    // Méthode pour obtenir le nombre d'événements organisés
    public function getNombreEvenementsOrganises(): int
    {
        return $this->evenementsOrganises->count();
    }

    // Méthode pour obtenir les événements à venir organisés
    public function getEvenementsOrganisesAVenir(): Collection
    {
        return $this->evenementsOrganises->filter(function (Evenement $evenement) {
            return $evenement->getStatut() === 'a_venir';
        });
    }

    // Méthode pour obtenir les événements en cours organisés
    public function getEvenementsOrganisesEnCours(): Collection
    {
        return $this->evenementsOrganises->filter(function (Evenement $evenement) {
            return $evenement->getStatut() === 'en_cours';
        });
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): self
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setEtudiant($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): self
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getEtudiant() === $this) {
                $participation->setEtudiant(null);
            }
        }

        return $this;
    }

    // Méthode pour obtenir les participations confirmées
    public function getParticipationsConfirmees(): Collection
    {
        return $this->participations->filter(function (Participation $participation) {
            return $participation->isConfirme();
        });
    }

    // Méthode pour obtenir les participations en attente
    public function getParticipationsEnAttente(): Collection
    {
        return $this->participations->filter(function (Participation $participation) {
            return $participation->isAttente();
        });
    }

    // Méthode pour obtenir le nombre d'événements auquel l'utilisateur participe
    public function getNombreParticipations(): int
    {
        return $this->participations->count();
    }

    // Méthode pour vérifier si l'utilisateur est inscrit à un événement
    public function isInscritEvenement(Evenement $evenement): bool
    {
        foreach ($this->participations as $participation) {
            if ($participation->getEvenement() === $evenement && $participation->isConfirme()) {
                return true;
            }
        }

        return false;
    }
    public function getId(): ?int
    {
    return $this->user_id;
    }
}