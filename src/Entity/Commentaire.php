<?php

/*
 * Description générale : Représente un commentaire déposé sous une publication.
 * Rôle : Conserver son contenu, sa date, son propriétaire et sa publication associée.
 * Tâches : Appliquer les champs, relations et index de la table commentaire du MPD.
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
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'publication_id', nullable: false, onDelete: 'CASCADE')]
    private ?Publication $publication = null;

    /**
     * Rôle : Initialiser la date de création du commentaire.
     * Paramètres : Aucun.
     * Retour : Aucun.
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
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
        $this->dateCreation = $dateCreation;

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
     * Paramètres : L'utilisateur propriétaire du commentaire.
     * Retour : Le commentaire modifié.
     */
    public function setUtilisateur(Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

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
     * Paramètres : La publication associée.
     * Retour : Le commentaire modifié.
     */
    public function setPublication(Publication $publication): static
    {
        $this->publication = $publication;

        return $this;
    }
}
