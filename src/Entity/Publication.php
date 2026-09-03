<?php

/*
 * Description générale : Représente une publication publiée par un membre.
 * Rôle : Conserver son contenu, son image, sa visibilité, son propriétaire et les utilisateurs qui l'aiment.
 * Tâches : Appliquer les champs et contraintes, puis synchroniser les relations avec l'utilisateur, les commentaires et les likes.
 * Liens avec les autres fichiers : Liée à Utilisateur, UploadFichier, Commentaire, PublicationRepository et aux contrôleurs métier.
 */

namespace App\Entity;

use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\Table(name: 'publication')]
#[ORM\Index(name: 'idx_publication_utilisateur', fields: ['utilisateur'])]
#[ORM\Index(name: 'idx_publication_visibilite_creation', fields: ['visibilite', 'dateCreation'])]
#[Assert\Expression(
    expression: 'this.aUnContenuOuUneImage()',
    message: 'Une publication doit contenir un texte ou une image.'
)]
class Publication
{
    public const VISIBILITE_PUBLIQUE = 'publique';
    public const VISIBILITE_AMIS = 'amis';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'contenu', type: 'text', nullable: true)]
    #[Assert\Length(max: 2000, normalizer: 'trim', maxMessage: 'Le contenu ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $contenu = null;

    #[ORM\OneToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'upload_fichier_id', referencedColumnName: 'id', nullable: true, unique: true, onDelete: 'RESTRICT')]
    private ?UploadFichier $uploadFichier = null;

    private bool $imageEnAttente = false;

    #[ORM\Column(name: 'visibilite', length: 20)]
    #[Assert\NotBlank(message: 'La visibilité est obligatoire.')]
    #[Assert\Choice(
        choices: [self::VISIBILITE_PUBLIQUE, self::VISIBILITE_AMIS],
        message: 'La visibilité choisie est invalide.'
    )]
    private ?string $visibilite = self::VISIBILITE_PUBLIQUE;

    #[ORM\Column(name: 'date_creation')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\ManyToOne(inversedBy: 'publications')]
    #[ORM\JoinColumn(name: 'utilisateur_id', nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull(message: 'L’utilisateur de la publication est obligatoire.')]
    private ?Utilisateur $utilisateur = null;

    /**
     * @var Collection<int, Utilisateur>
     */
    #[ORM\ManyToMany(targetEntity: Utilisateur::class, inversedBy: 'publicationsAimees')]
    #[ORM\JoinTable(name: 'asso_utilisateur_publication')]
    #[ORM\JoinColumn(name: 'publication_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    private Collection $utilisateursAimant;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: Commentaire::class, orphanRemoval: true)]
    private Collection $commentaires;

    /**
     * Rôle : Initialiser la date de création et la collection des likes.
     * Paramètres : Aucun.
     * Retour : Aucun.
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
        $this->utilisateursAimant = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
    }

    /**
     * Rôle : Retourner l'identifiant technique de la publication.
     * Paramètres : Aucun.
     * Retour : L'identifiant ou null avant la persistance.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Rôle : Retourner le texte facultatif de la publication.
     * Paramètres : Aucun.
     * Retour : Le texte ou null si seule une image est publiée.
     */
    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    /**
     * Rôle : Définir le texte facultatif de la publication.
     * Paramètres : Le texte ou null.
     * Retour : La publication modifiée.
     */
    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    /**
     * Rôle : Retourner le fichier image facultatif de la publication.
     * Paramètres : Aucun.
     * Retour : Le fichier ou null si aucune image n'est publiée.
     */
    public function getUploadFichier(): ?UploadFichier
    {
        return $this->uploadFichier;
    }

    /**
     * Rôle : Définir le fichier image facultatif de la publication.
     * Paramètres : Le fichier à rattacher ou null.
     * Retour : La publication modifiée.
     */
    public function setUploadFichier(?UploadFichier $uploadFichier): static
    {
        $this->uploadFichier = $uploadFichier;

        return $this;
    }

    /**
     * Rôle : Signaler temporairement qu'une nouvelle image valide est présente dans le formulaire.
     * Paramètres : Vrai lorsqu'une image attend son téléversement.
     * Retour : La publication modifiée.
     */
    public function setImageEnAttente(bool $imageEnAttente): static
    {
        $this->imageEnAttente = $imageEnAttente;

        return $this;
    }

    /**
     * Rôle : Retourner la visibilité de la publication.
     * Paramètres : Aucun.
     * Retour : La valeur de visibilité ou null avant sa définition.
     */
    public function getVisibilite(): ?string
    {
        return $this->visibilite;
    }

    /**
     * Rôle : Définir la visibilité de la publication.
     * Paramètres : La visibilité publique ou réservée aux amis.
     * Retour : La publication modifiée.
     */
    public function setVisibilite(string $visibilite): static
    {
        $this->visibilite = $visibilite;

        return $this;
    }

    /**
     * Rôle : Retourner la date de création de la publication.
     * Paramètres : Aucun.
     * Retour : La date de création.
     */
    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    /**
     * Rôle : Définir la date de création de la publication.
     * Paramètres : La date à enregistrer.
     * Retour : La publication modifiée.
     */
    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Rôle : Retourner l'utilisateur de la publication.
     * Paramètres : Aucun.
     * Retour : L'utilisateur ou null avant sa définition.
     */
    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    /**
     * Rôle : Définir l'utilisateur de la publication.
     * Paramètres : L'utilisateur propriétaire de la publication ou null pendant une dissociation contrôlée.
     * Retour : La publication modifiée.
     */
    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        if ($this->utilisateur === $utilisateur) {
            return $this;
        }

        $ancienUtilisateur = $this->utilisateur;
        $this->utilisateur = $utilisateur;

        if (null !== $ancienUtilisateur) {
            $ancienUtilisateur->retirerPublication($this);
        }

        if (null !== $utilisateur) {
            $utilisateur->ajouterPublication($this);
        }

        return $this;
    }

    /**
     * Rôle : Retourner les utilisateurs ayant aimé cette publication.
     * Paramètres : Aucun.
     * Retour : La collection consultable des utilisateurs ayant aimé la publication.
     *
     * @return ReadableCollection<int, Utilisateur>
     */
    public function getUtilisateursAimant(): ReadableCollection
    {
        return $this->utilisateursAimant;
    }

    /**
     * Rôle : Ajouter un utilisateur aux likes de la publication.
     * Paramètres : L'utilisateur qui aime la publication.
     * Retour : La publication modifiée.
     */
    public function ajouterUtilisateurAimant(Utilisateur $utilisateur): static
    {
        if (!$this->utilisateursAimant->contains($utilisateur)) {
            $this->utilisateursAimant->add($utilisateur);
            $utilisateur->ajouterPublicationAimee($this);
        }

        return $this;
    }

    /**
     * Rôle : Retirer un utilisateur des likes de la publication.
     * Paramètres : L'utilisateur qui ne souhaite plus aimer la publication.
     * Retour : La publication modifiée.
     */
    public function retirerUtilisateurAimant(Utilisateur $utilisateur): static
    {
        if ($this->utilisateursAimant->removeElement($utilisateur)) {
            $utilisateur->retirerPublicationAimee($this);
        }

        return $this;
    }

    /**
     * Rôle : Retourner les commentaires rattachés à la publication.
     * Paramètres : Aucun.
     * Retour : La collection consultable des commentaires.
     *
     * @return ReadableCollection<int, Commentaire>
     */
    public function getCommentaires(): ReadableCollection
    {
        return $this->commentaires;
    }

    /**
     * Rôle : Retrouver le nom de l’image associée en privilégiant la table centralisée des fichiers.
     * Paramètres : Aucun.
     * Retour : Le nom du fichier image ou null lorsque la publication ne contient aucune image.
     */
    public function getImageAffichee(): ?string
    {
        if (null !== $this->uploadFichier) {
            return $this->uploadFichier->getNom();
        }

        return null;
    }

    /**
     * Rôle : Ajouter un commentaire à la publication.
     * Paramètres : Le commentaire à associer.
     * Retour : La publication modifiée.
     */
    public function ajouterCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setPublication($this);
        }

        return $this;
    }

    /**
     * Rôle : Retirer un commentaire de la publication.
     * Paramètres : Le commentaire à retirer.
     * Retour : La publication modifiée.
     */
    public function retirerCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire) && $commentaire->getPublication() === $this) {
            $commentaire->setPublication(null);
        }

        return $this;
    }

    /**
     * Rôle : Vérifier qu'un texte non vide ou une image est présent.
     * Paramètres : Aucun.
     * Retour : Vrai lorsque la publication est valide sur ce point.
     */
    public function aUnContenuOuUneImage(): bool
    {
        if ($this->getImageAffichee() !== null || $this->imageEnAttente) {
            return true;
        }

        return $this->aUnContenuTexte();
    }

    /**
     * Rôle : Indiquer si la publication possède un texte réellement renseigné.
     * Paramètres : Aucun.
     * Retour : Vrai lorsque le contenu contient au moins un caractère non blanc.
     */
    public function aUnContenuTexte(): bool
    {
        if ($this->contenu === null) {
            return false;
        }

        return trim($this->contenu) !== '';
    }
}
