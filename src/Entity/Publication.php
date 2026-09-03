<?php

/*
 * Description générale : Représente une publication publiée par un membre.
 * Rôle : Conserver son contenu, son image, sa visibilité, son propriétaire et les utilisateurs qui l'aiment.
 * Tâches : Appliquer les champs, contraintes et relations de la publication, des fichiers et des likes.
 * Liens avec les autres fichiers : Liée à Utilisateur, UploadFichier, Commentaire, PublicationRepository et aux contrôleurs métier.
 */

namespace App\Entity;

use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[Assert\Length(max: 2000, maxMessage: 'Le contenu ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $contenu = null;

    #[ORM\Column(name: 'nom_image', length: 255, nullable: true)]
    private ?string $nomImage = null;

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
    private ?Utilisateur $utilisateur = null;

    /** @var Collection<int, UploadFichier> */
    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: UploadFichier::class)]
    private Collection $uploadFichiers;

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
        $this->uploadFichiers = new ArrayCollection();
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
     * Rôle : Retourner le nom facultatif du fichier image.
     * Paramètres : Aucun.
     * Retour : Le nom du fichier ou null si aucune image n'est publiée.
     */
    public function getNomImage(): ?string
    {
        return $this->nomImage;
    }

    /**
     * Rôle : Définir le nom facultatif du fichier image.
     * Paramètres : Le nom du fichier ou null.
     * Retour : La publication modifiée.
     */
    public function setNomImage(?string $nomImage): static
    {
        $this->nomImage = $nomImage;

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
     * Paramètres : L'utilisateur propriétaire de la publication.
     * Retour : La publication modifiée.
     */
    public function setUtilisateur(Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    /**
     * Rôle : Retourner les utilisateurs ayant aimé cette publication.
     * Paramètres : Aucun.
     * Retour : La collection des utilisateurs ayant aimé la publication.
     *
     * @return Collection<int, Utilisateur>
     */
    public function getUtilisateursAimant(): Collection
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
        $this->utilisateursAimant->removeElement($utilisateur);

        return $this;
    }

    /**
     * Rôle : Retourner les commentaires rattachés à la publication.
     * Paramètres : Aucun.
     * Retour : La collection des commentaires.
     *
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    /**
     * Rôle : Retourner les fichiers téléversés pour la publication.
     * Paramètres : Aucun.
     * Retour : La collection des images de la publication.
     *
     * @return Collection<int, UploadFichier>
     */
    public function getUploadFichiers(): Collection
    {
        return $this->uploadFichiers;
    }

    /**
     * Rôle : Ajouter un fichier téléversé à la publication.
     * Paramètres : Le fichier à rattacher.
     * Retour : La publication modifiée.
     */
    public function ajouterUploadFichier(UploadFichier $uploadFichier): static
    {
        if (!$this->uploadFichiers->contains($uploadFichier)) {
            $this->uploadFichiers->add($uploadFichier);
            $uploadFichier->setPublication($this);
        }

        return $this;
    }

    /**
     * Rôle : Retirer un fichier téléversé de la publication.
     * Paramètres : Le fichier à détacher.
     * Retour : La publication modifiée.
     */
    public function retirerUploadFichier(UploadFichier $uploadFichier): static
    {
        if ($this->uploadFichiers->removeElement($uploadFichier)) {
            $uploadFichier->setPublication(null);
        }

        return $this;
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
        $this->commentaires->removeElement($commentaire);

        return $this;
    }

    /**
     * Rôle : Vérifier qu'un texte non vide ou une image est présent.
     * Paramètres : Aucun.
     * Retour : Vrai lorsque la publication est valide sur ce point.
     */
    public function aUnContenuOuUneImage(): bool
    {
        if ($this->nomImage !== null) {
            return true;
        }

        if ($this->contenu === null) {
            return false;
        }

        return trim($this->contenu) !== '';
    }
}
