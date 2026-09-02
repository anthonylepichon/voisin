<?php

/*
 * Description générale : Représente une demande d'amitié envoyée entre deux utilisateurs.
 * Rôle : Conserver l'émetteur, le destinataire et la date de création d'une demande en attente.
 * Tâches : Appliquer les relations et contraintes prévues par la table demande_amitie du MPD.
 * Liens avec les autres fichiers : Liée à Utilisateur et utilisée par DemandeAmitieRepository ainsi que les futures fonctionnalités d'amitié.
 */

namespace App\Entity;

use App\Repository\DemandeAmitieRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DemandeAmitieRepository::class)]
#[ORM\Table(name: 'demande_amitie')]
#[ORM\UniqueConstraint(name: 'uq_demande_expediteur_destinataire', fields: ['expediteur', 'destinataire'])]
#[ORM\Index(name: 'idx_demande_destinataire', fields: ['destinataire'])]
class DemandeAmitie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'date_creation')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'expediteur_id', nullable: false, onDelete: 'RESTRICT')]
    private ?Utilisateur $expediteur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'destinataire_id', nullable: false, onDelete: 'RESTRICT')]
    private ?Utilisateur $destinataire = null;

    /**
     * Rôle : Initialiser la date de création de la demande.
     * Paramètres : Aucun.
     * Retour : Aucun.
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

    /**
     * Rôle : Retourner l'identifiant technique de la demande.
     * Paramètres : Aucun.
     * Retour : L'identifiant ou null avant la persistance.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Rôle : Retourner la date de création de la demande.
     * Paramètres : Aucun.
     * Retour : La date de création.
     */
    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    /**
     * Rôle : Définir la date de création de la demande.
     * Paramètres : La date à enregistrer.
     * Retour : La demande modifiée.
     */
    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Rôle : Retourner l'utilisateur qui a envoyé la demande.
     * Paramètres : Aucun.
     * Retour : L'expéditeur ou null avant sa définition.
     */
    public function getExpediteur(): ?Utilisateur
    {
        return $this->expediteur;
    }

    /**
     * Rôle : Définir l'utilisateur qui envoie la demande.
     * Paramètres : L'utilisateur expéditeur.
     * Retour : La demande modifiée.
     */
    public function setExpediteur(Utilisateur $expediteur): static
    {
        $this->expediteur = $expediteur;

        return $this;
    }

    /**
     * Rôle : Retourner l'utilisateur qui reçoit la demande.
     * Paramètres : Aucun.
     * Retour : Le destinataire ou null avant sa définition.
     */
    public function getDestinataire(): ?Utilisateur
    {
        return $this->destinataire;
    }

    /**
     * Rôle : Définir l'utilisateur qui reçoit la demande.
     * Paramètres : L'utilisateur destinataire.
     * Retour : La demande modifiée.
     */
    public function setDestinataire(Utilisateur $destinataire): static
    {
        $this->destinataire = $destinataire;

        return $this;
    }
}
