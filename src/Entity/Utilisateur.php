<?php

/*
 * Description générale : Représente un compte utilisateur de l'application Voisin.
 * Rôle : Porter les données de sécurité, de profil et d'activité d'un utilisateur.
 * Tâches : Garantir les contraintes Doctrine, les caractères autorisés du pseudonyme, la photo du profil et l'encapsulation des relations inverses.
 * Liens avec les autres fichiers : Utilisée par UtilisateurRepository, UploadFichier, Security, les contrôleurs et les autres entités métier.
 */

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
#[ORM\UniqueConstraint(name: 'uq_utilisateur_pseudonyme', fields: ['pseudonyme'])]
#[ORM\UniqueConstraint(name: 'uq_utilisateur_adresse_email', fields: ['adresseEmail'])]
#[UniqueEntity(fields: ['pseudonyme'], message: 'Ce pseudonyme est déjà utilisé.')]
#[UniqueEntity(fields: ['adresseEmail'], message: 'Cette adresse e-mail est déjà utilisée.')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'pseudonyme', length: 50)]
    #[Assert\NotBlank(message: 'Le pseudonyme est obligatoire.', normalizer: 'trim')]
    #[Assert\Length(min: 3, max: 50, normalizer: 'trim', minMessage: 'Le pseudonyme doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le pseudonyme ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(pattern: '/^[\p{L}\p{N}_-]+$/u', message: 'Le pseudonyme peut contenir uniquement des lettres, des chiffres, des tirets et des tirets bas.')]
    private ?string $pseudonyme = null;

    #[ORM\Column(name: 'adresse_email', length: 180)]
    #[Assert\NotBlank(message: 'L’adresse e-mail est obligatoire.', normalizer: 'trim')]
    #[Assert\Email(message: 'L’adresse e-mail doit être valide.', normalizer: 'trim')]
    private ?string $adresseEmail = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(name: 'mot_de_passe')]
    private ?string $motDePasse = null;

    #[ORM\OneToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'upload_fichier_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'RESTRICT')]
    private ?UploadFichier $uploadFichier = null;

    #[ORM\Column(name: 'biographie', length: 500, nullable: true)]
    #[Assert\Length(max: 500, normalizer: 'trim', maxMessage: 'La biographie ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $biographie = null;

    #[ORM\Column(name: 'date_inscription')]
    private \DateTimeImmutable $dateInscription;

    #[ORM\Column(name: 'date_derniere_activite', nullable: true)]
    private ?\DateTimeImmutable $dateDerniereActivite = null;

    /** @var Collection<int, Publication> */
    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Publication::class)]
    private Collection $publications;

    /** @var Collection<int, Commentaire> */
    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Commentaire::class)]
    private Collection $commentaires;

    /** @var Collection<int, Publication> */
    #[ORM\ManyToMany(targetEntity: Publication::class, mappedBy: 'utilisateursAimant')]
    private Collection $publicationsAimees;

    /** @var Collection<int, Utilisateur> */
    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\JoinTable(name: 'asso_utilisateur_utilisateur')]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    #[ORM\InverseJoinColumn(name: 'ami_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    private Collection $amis;

    /**
     * Rôle : Initialiser la date de création du compte.
     * Paramètres : Aucun.
     * Retour : Aucun.
     */
    public function __construct()
    {
        $this->dateInscription = new \DateTimeImmutable();
        $this->publications = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->publicationsAimees = new ArrayCollection();
        $this->amis = new ArrayCollection();
    }

    /**
     * Rôle : Retourner l'identifiant technique de l'utilisateur.
     * Paramètres : Aucun.
     * Retour : L'identifiant ou null avant la persistance.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Rôle : Retourner le pseudonyme public de l'utilisateur.
     * Paramètres : Aucun.
     * Retour : Le pseudonyme ou null avant sa définition.
     */
    public function getPseudonyme(): ?string
    {
        return $this->pseudonyme;
    }

    /**
     * Rôle : Définir le pseudonyme public de l'utilisateur.
     * Paramètres : Le pseudonyme à enregistrer.
     * Retour : L'utilisateur modifié.
     */
    public function setPseudonyme(string $pseudonyme): static
    {
        $this->pseudonyme = $pseudonyme;

        return $this;
    }

    /**
     * Rôle : Retourner l'adresse e-mail de l'utilisateur.
     * Paramètres : Aucun.
     * Retour : L'adresse e-mail ou null avant sa définition.
     */
    public function getAdresseEmail(): ?string
    {
        return $this->adresseEmail;
    }

    /**
     * Rôle : Définir l'adresse e-mail de l'utilisateur.
     * Paramètres : L'adresse e-mail à enregistrer.
     * Retour : L'utilisateur modifié.
     */
    public function setAdresseEmail(string $adresseEmail): static
    {
        $this->adresseEmail = $adresseEmail;

        return $this;
    }

    /**
     * Rôle : Retourner l'identifiant de sécurité conservé dans la session.
     * Paramètres : Aucun.
     * Retour : L'adresse e-mail unique de l'utilisateur.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->adresseEmail;
    }

    /**
     * Rôle : Retourner les rôles de sécurité de l'utilisateur.
     * Paramètres : Aucun.
     * Retour : La liste des rôles, incluant toujours ROLE_USER.
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Rôle : Définir les rôles de sécurité de l'utilisateur.
     * Paramètres : La liste des rôles à enregistrer.
     * Retour : L'utilisateur modifié.
     *
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Rôle : Retourner le mot de passe haché de l'utilisateur.
     * Paramètres : Aucun.
     * Retour : Le mot de passe haché ou null avant sa définition.
     */
    public function getPassword(): ?string
    {
        return $this->motDePasse;
    }

    /**
     * Rôle : Définir le mot de passe déjà haché de l'utilisateur.
     * Paramètres : Le mot de passe haché à enregistrer.
     * Retour : L'utilisateur modifié.
     */
    public function setPassword(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }

    /**
     * Rôle : Retourner le fichier utilisé comme photo de profil.
     * Paramètres : Aucun.
     * Retour : Le fichier ou null avant sa définition.
     */
    public function getUploadFichier(): ?UploadFichier
    {
        return $this->uploadFichier;
    }

    /**
     * Rôle : Définir le fichier utilisé comme photo de profil.
     * Paramètres : Le fichier à rattacher ou null.
     * Retour : L'utilisateur modifié.
     */
    public function setUploadFichier(?UploadFichier $uploadFichier): static
    {
        $this->uploadFichier = $uploadFichier;

        return $this;
    }

    /**
     * Rôle : Retourner la biographie facultative de l'utilisateur.
     * Paramètres : Aucun.
     * Retour : La biographie ou null si elle n'est pas renseignée.
     */
    public function getBiographie(): ?string
    {
        return $this->biographie;
    }

    /**
     * Rôle : Définir la biographie facultative de l'utilisateur.
     * Paramètres : La biographie ou null.
     * Retour : L'utilisateur modifié.
     */
    public function setBiographie(?string $biographie): static
    {
        $this->biographie = $biographie;

        return $this;
    }

    /**
     * Rôle : Retourner la date d'inscription de l'utilisateur.
     * Paramètres : Aucun.
     * Retour : La date d'inscription.
     */
    public function getDateInscription(): \DateTimeImmutable
    {
        return $this->dateInscription;
    }

    /**
     * Rôle : Définir la date d'inscription de l'utilisateur.
     * Paramètres : La date d'inscription à enregistrer.
     * Retour : L'utilisateur modifié.
     */
    public function setDateInscription(\DateTimeImmutable $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }

    /**
     * Rôle : Retourner la date de dernière activité de l'utilisateur.
     * Paramètres : Aucun.
     * Retour : La date ou null si aucune activité n'est enregistrée.
     */
    public function getDateDerniereActivite(): ?\DateTimeImmutable
    {
        return $this->dateDerniereActivite;
    }

    /**
     * Rôle : Définir la date de dernière activité de l'utilisateur.
     * Paramètres : La date ou null.
     * Retour : L'utilisateur modifié.
     */
    public function setDateDerniereActivite(?\DateTimeImmutable $dateDerniereActivite): static
    {
        $this->dateDerniereActivite = $dateDerniereActivite;

        return $this;
    }

    /**
     * Rôle : Retourner les publications écrites par l'utilisateur.
     * Paramètres : Aucun.
     * Retour : La collection consultable de publications.
     *
     * @return ReadableCollection<int, Publication>
     */
    public function getPublications(): ReadableCollection
    {
        return $this->publications;
    }

    /**
     * Rôle : Ajouter une publication écrite par l'utilisateur et synchroniser son propriétaire.
     * Paramètres : La publication à associer.
     * Retour : L'utilisateur modifié.
     */
    public function ajouterPublication(Publication $publication): static
    {
        if (!$this->publications->contains($publication)) {
            $this->publications->add($publication);
            $publication->setUtilisateur($this);
        }

        return $this;
    }

    /**
     * Rôle : Retirer une publication de l'utilisateur et synchroniser son propriétaire.
     * Paramètres : La publication à dissocier.
     * Retour : L'utilisateur modifié.
     */
    public function retirerPublication(Publication $publication): static
    {
        if ($this->publications->removeElement($publication) && $publication->getUtilisateur() === $this) {
            $publication->setUtilisateur(null);
        }

        return $this;
    }

    /**
     * Rôle : Retourner les commentaires écrits par l'utilisateur.
     * Paramètres : Aucun.
     * Retour : La collection consultable de commentaires.
     *
     * @return ReadableCollection<int, Commentaire>
     */
    public function getCommentaires(): ReadableCollection
    {
        return $this->commentaires;
    }

    /**
     * Rôle : Ajouter un commentaire écrit par l'utilisateur et synchroniser son propriétaire.
     * Paramètres : Le commentaire à associer.
     * Retour : L'utilisateur modifié.
     */
    public function ajouterCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setUtilisateur($this);
        }

        return $this;
    }

    /**
     * Rôle : Retirer un commentaire de l'utilisateur et synchroniser son propriétaire.
     * Paramètres : Le commentaire à dissocier.
     * Retour : L'utilisateur modifié.
     */
    public function retirerCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire) && $commentaire->getUtilisateur() === $this) {
            $commentaire->setUtilisateur(null);
        }

        return $this;
    }

    /**
     * Rôle : Retourner les publications aimées par l'utilisateur.
     * Paramètres : Aucun.
     * Retour : La collection consultable de publications aimées.
     *
     * @return ReadableCollection<int, Publication>
     */
    public function getPublicationsAimees(): ReadableCollection
    {
        return $this->publicationsAimees;
    }

    /**
     * Rôle : Ajouter une publication aux mentions J'aime de l'utilisateur et synchroniser la relation propriétaire.
     * Paramètres : La publication aimée.
     * Retour : L'utilisateur modifié.
     */
    public function ajouterPublicationAimee(Publication $publication): static
    {
        if (!$this->publicationsAimees->contains($publication)) {
            $this->publicationsAimees->add($publication);
            $publication->ajouterUtilisateurAimant($this);
        }

        return $this;
    }

    /**
     * Rôle : Retirer une publication des mentions J'aime de l'utilisateur et synchroniser la relation propriétaire.
     * Paramètres : La publication qui n'est plus aimée.
     * Retour : L'utilisateur modifié.
     */
    public function retirerPublicationAimee(Publication $publication): static
    {
        if ($this->publicationsAimees->removeElement($publication)) {
            $publication->retirerUtilisateurAimant($this);
        }

        return $this;
    }

    /**
     * Rôle : Retourner les amis enregistrés du côté propriétaire de la relation normalisée.
     * Paramètres : Aucun.
     * Retour : La collection consultable d'amis concernés.
     *
     * @return ReadableCollection<int, Utilisateur>
     */
    public function getAmis(): ReadableCollection
    {
        return $this->amis;
    }

    /**
     * Rôle : Ajouter un ami du côté propriétaire de la relation normalisée.
     * Paramètres : L'ami à associer.
     * Retour : L'utilisateur modifié.
     */
    public function ajouterAmi(Utilisateur $ami): static
    {
        if (!$this->amis->contains($ami)) {
            $this->amis->add($ami);
        }

        return $this;
    }

    /**
     * Rôle : Retirer un ami du côté propriétaire de la relation normalisée.
     * Paramètres : L'ami à dissocier.
     * Retour : L'utilisateur modifié.
     */
    public function retirerAmi(Utilisateur $ami): static
    {
        $this->amis->removeElement($ami);

        return $this;
    }

    /**
     * Rôle : Protéger le hachage du mot de passe lors de son stockage en session.
     * Paramètres : Aucun.
     * Retour : Les données sérialisées de l'utilisateur.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0motDePasse"] = hash('crc32c', $this->motDePasse);

        return $data;
    }

    /**
     * Rôle : Effacer les informations sensibles temporaires après authentification.
     * Paramètres : Aucun.
     * Retour : Aucun.
     */
    #[\Deprecated]
    public function eraseCredentials(): void {}
}
