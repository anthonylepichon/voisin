<?php

/*
 * Description générale : Fournit l'accès Doctrine aux demandes d'amitié.
 * Rôle : Centraliser les futures recherches liées aux demandes en attente.
 * Tâches : Hériter des opérations standard de Doctrine pour DemandeAmitie.
 * Liens avec les autres fichiers : Utilisé par l'entité DemandeAmitie et les futures fonctionnalités d'amitié.
 */

namespace App\Repository;

use App\Entity\DemandeAmitie;
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
}
