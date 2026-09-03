<?php

/*
 * Description générale : Représente un fichier téléversé dans l'application Voisin.
 * Rôle : Centraliser les métadonnées et le rattachement des photos de profil et des images de publication.
 * Tâches : Conserver le type, le nom, le chemin et l'unique propriétaire métier du fichier.
 * Liens avec les autres fichiers : Liée à Utilisateur, Publication, UploadFichierRepository et FileUploadService.
 */

namespace App\Entity;

use App\Repository\UploadFichierRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UploadFichierRepository::class)]
#[ORM\Table(name: 'upload_fichier')]
#[ORM\Index(name: 'idx_upload_utilisateur', fields: ['utilisateur'])]
#[ORM\Index(name: 'idx_upload_publication', fields: ['publication'])]
#[Assert\Expression(
    expression: '(this.getUtilisateur() != null and this.getPublication() == null) or (this.getUtilisateur() == null and this.getPublication() != null)',
    message: 'Le fichier doit être rattaché soit à un utilisateur, soit à une publication.'
)]
class UploadFichier
{
    public const TYPE_PROFIL = 'profil';
    public const TYPE_PUBLICATION = 'publication';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type du fichier est obligatoire.')]
    #[Assert\Choice(choices: [self::TYPE_PROFIL, self::TYPE_PUBLICATION], message: 'Le type du fichier est invalide.')]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du fichier est obligatoire.')]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le chemin du fichier est obligatoire.')]
    private ?string $chemin = null;

    #[ORM\ManyToOne(inversedBy: 'uploadFichiers')]
    #[ORM\JoinColumn(name: 'utilisateur_id', nullable: true, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'uploadFichiers')]
    #[ORM\JoinColumn(name: 'publication_id', nullable: true, onDelete: 'CASCADE')]
    private ?Publication $publication = null;

    /**
     * Rôle : Retourner l'identifiant technique du fichier.
     * Paramètres : Aucun.
     * Retour : L'identifiant ou null avant la persistance.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Rôle : Retourner le type métier du fichier.
     * Paramètres : Aucun.
     * Retour : Le type ou null avant sa définition.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Rôle : Définir le type métier du fichier.
     * Paramètres : Le type profil ou publication.
     * Retour : Le fichier modifié.
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Rôle : Retourner le nom sécurisé du fichier.
     * Paramètres : Aucun.
     * Retour : Le nom ou null avant sa définition.
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * Rôle : Définir le nom sécurisé du fichier.
     * Paramètres : Le nom à enregistrer.
     * Retour : Le fichier modifié.
     */
    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Rôle : Retourner le répertoire relatif de stockage.
     * Paramètres : Aucun.
     * Retour : Le chemin ou null avant sa définition.
     */
    public function getChemin(): ?string
    {
        return $this->chemin;
    }

    /**
     * Rôle : Définir le répertoire relatif de stockage.
     * Paramètres : Le chemin à enregistrer.
     * Retour : Le fichier modifié.
     */
    public function setChemin(string $chemin): static
    {
        $this->chemin = $chemin;

        return $this;
    }

    /**
     * Rôle : Retourner l'utilisateur propriétaire d'une photo de profil.
     * Paramètres : Aucun.
     * Retour : L'utilisateur ou null pour une image de publication.
     */
    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    /**
     * Rôle : Rattacher le fichier à un utilisateur.
     * Paramètres : L'utilisateur ou null.
     * Retour : Le fichier modifié.
     */
    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    /**
     * Rôle : Retourner la publication propriétaire d'une image.
     * Paramètres : Aucun.
     * Retour : La publication ou null pour une photo de profil.
     */
    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    /**
     * Rôle : Rattacher le fichier à une publication.
     * Paramètres : La publication ou null.
     * Retour : Le fichier modifié.
     */
    public function setPublication(?Publication $publication): static
    {
        $this->publication = $publication;

        return $this;
    }
}
