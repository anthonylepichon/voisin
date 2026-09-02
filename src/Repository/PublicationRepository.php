<?php

/*
 * Description générale : Fournit l'accès Doctrine aux publications.
 * Rôle : Centraliser les futures recherches du fil, des profils et de l'accueil public.
 * Tâches : Hériter des opérations standard de Doctrine pour Publication.
 * Liens avec les autres fichiers : Utilisé par l'entité Publication et les futurs contrôleurs de publications.
 */

namespace App\Repository;

use App\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Publication>
 */
class PublicationRepository extends ServiceEntityRepository
{
    /**
     * Rôle : Initialiser le dépôt Doctrine des publications.
     * Paramètres : Le registre Doctrine de l'application.
     * Retour : Aucun.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }
}
