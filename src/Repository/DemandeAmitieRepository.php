<?php

/*
 * Description générale : Fournit l'accès Doctrine aux demandes d'amitié.
 * Rôle : Centraliser les recherches liées aux demandes d'amitié en attente.
 * Tâches : Compter les demandes reçues et fournir les opérations standard de Doctrine pour DemandeAmitie.
 * Liens avec les autres fichiers : Utilisé par l'entité DemandeAmitie et les fonctionnalités d'amitié.
 */

namespace App\Repository;

use App\Entity\DemandeAmitie;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DemandeAmitie>
 */
class DemandeAmitieRepository extends ServiceEntityRepository
{
    /**
     * Rôle : Initialiser le dépôt Doctrine des demandes d'amitié.
     * Paramètres : Le registre Doctrine de l'application.
     * Retour : Aucun.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeAmitie::class);
    }

    /**
     * Rôle : Compter les demandes d'amitié en attente reçues par un utilisateur.
     * Paramètres : L'utilisateur destinataire des demandes.
     * Retour : Le nombre de demandes reçues.
     */
    public function compterRecues(Utilisateur $destinataire): int
    {
        return (int) $this->createQueryBuilder('demande')
            ->select('COUNT(demande.id)')
            ->andWhere('demande.destinataire = :destinataire')
            ->setParameter('destinataire', $destinataire)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
