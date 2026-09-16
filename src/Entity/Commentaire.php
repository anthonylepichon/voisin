<?php

/* Origine du code : Structure générée par Symfony puis modifiée par le développeur. */

/*
 * Description générale : Représente un commentaire déposé sous une publication.
 * Rôle : Conserver son contenu, sa date, son propriétaire et sa publication associée.
 * Tâches : Appliquer les champs et index du MPD, normaliser la date en UTC puis synchroniser les relations avec l'utilisateur et la publication.
 * Liens avec les autres fichiers : Lié à Utilisateur, Publication, CommentaireRepository, CommentFormType et CommentController.
 */

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[ORM\Table(name: 'commentaire')]
#[ORM\Index(name: 'idx_commentaire_utilisateur', fields: ['utilisateur'])]
#[ORM\Index(name: 'idx_commentaire_publication', fields: ['publication'])]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'contenu', type: 'text')]
    #[Assert\NotBlank(message: 'Le commentaire ne peut pas être vide.', normalizer: 'trim')]
    #[Assert\Length(max: 500, normalizer: 'trim', maxMessage: 'Le commentaire ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $contenu = null;

    #[ORM\Column(name: 'date_creation')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'utilisateur_id', nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull(message: 'L’utilisateur du commentaire est obligatoire.')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'publication_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La publication du commentaire est obligatoire.')]
    private ?Publication $publication = null;

    /**
     * Rôle : Initialiser la date de création du commentaire.
     * Paramètres : Aucun.
     * Retour : Aucun.
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    /**
     * Rôle : Retourner l'identifiant technique du commentaire.
     * Paramètres : Aucun.
     * Retour : L'identifiant ou null avant la persistance.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Rôle : Retourner le contenu du commentaire.
     * Paramètres : Aucun.
     * Retour : Le contenu ou null avant sa définition.
     */
    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    /**
     * Rôle : Définir le contenu du commentaire.
     * Paramètres : Le contenu à enregistrer.
     * Retour : Le commentaire modifié.
     */
    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    /**
     * Rôle : Retourner la date de création du commentaire.
     * Paramètres : Aucun.
     * Retour : La date de création.
     */
    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    /**
     * Rôle : Définir la date de création du commentaire.
     * Paramètres : La date à enregistrer.
     * Retour : Le commentaire modifié.
     */
    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation->setTimezone(new \DateTimeZone('UTC'));

        return $this;
    }

    /**
     * Rôle : Retourner l'utilisateur du commentaire.
     * Paramètres : Aucun.
     * Retour : L'utilisateur ou null avant sa définition.
     */
    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    /**
     * Rôle : Définir l'utilisateur du commentaire.
     * Paramètres : L'utilisateur propriétaire du commentaire ou null pendant une dissociation contrôlée.
     * Retour : Le commentaire modifié.
     */
    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        if ($this->utilisateur === $utilisateur) {
            return $this;
        }

        $ancienUtilisateur = $this->utilisateur;
        $this->utilisateur = $utilisateur;

        if (null !== $ancienUtilisateur) {
            $ancienUtilisateur->retirerCommentaire($this);
        }

        if (null !== $utilisateur) {
            $utilisateur->ajouterCommentaire($this);
        }

        return $this;
    }

    /**
     * Rôle : Retourner la publication commentée.
     * Paramètres : Aucun.
     * Retour : La publication ou null avant sa définition.
     */
    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    /**
     * Rôle : Définir la publication commentée.
     * Paramètres : La publication associée ou null pendant une dissociation contrôlée.
     * Retour : Le commentaire modifié.
     */
    public function setPublication(?Publication $publication): static
    {
        if ($this->publication === $publication) {
            return $this;
        }

        $anciennePublication = $this->publication;
        $this->publication = $publication;

        if (null !== $anciennePublication) {
            $anciennePublication->retirerCommentaire($this);
        }

        if (null !== $publication) {
            $publication->ajouterCommentaire($this);
        }

        return $this;
    }
}
