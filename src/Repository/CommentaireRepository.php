<?php

/*
 * Description générale : Fournit l'accès Doctrine aux commentaires.
 * Rôle : Centraliser les recherches de commentaires par publication.
 * Tâches : Charger les commentaires d'une publication dans leur ordre d'affichage.
 * Liens avec les autres fichiers : Utilisé par l'entité Commentaire et CommentController.
 */

namespace App\Repository;

use App\Entity\Commentaire;
use App\Entity\Publication;
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

    /**
     * Rôle : Rechercher les commentaires d'une publication du plus ancien au plus récent.
     * Paramètres : La publication consultée.
     * Retour : La liste ordonnée des commentaires avec leur utilisateur.
     *
     * @return list<Commentaire>
     */
    public function trouverParPublicationAncienPremier(Publication $publication): array
    {
        return $this->createQueryBuilder('commentaire')
            ->innerJoin('commentaire.utilisateur', 'utilisateur')
            ->addSelect('utilisateur')
            ->andWhere('commentaire.publication = :publication')
            ->setParameter('publication', $publication)
            ->orderBy('commentaire.dateCreation', 'ASC')
            ->addOrderBy('commentaire.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
