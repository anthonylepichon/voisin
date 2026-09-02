<?php

/*
 * Description générale : Fournit l'accès Doctrine aux commentaires.
 * Rôle : Centraliser les futures recherches de commentaires par publication.
 * Tâches : Hériter des opérations standard de Doctrine pour Commentaire.
 * Liens avec les autres fichiers : Utilisé par l'entité Commentaire et les futurs contrôleurs de commentaires.
 */

namespace App\Repository;

use App\Entity\Commentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    /**
     * Rôle : Initialiser le dépôt Doctrine des commentaires.
     * Paramètres : Le registre Doctrine de l'application.
     * Retour : Aucun.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }
}
