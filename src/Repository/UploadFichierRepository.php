<?php

/*
 * Description générale : Dépôt Doctrine des fichiers téléversés.
 * Rôle : Retrouver les métadonnées centralisées d'un fichier.
 * Tâches : Fournir les opérations standards Doctrine et la recherche sécurisée par type et nom.
 * Liens avec les autres fichiers : Utilise UploadFichier et est injecté dans UploadFichierController.
 */

namespace App\Repository;

use App\Entity\UploadFichier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<UploadFichier> */
class UploadFichierRepository extends ServiceEntityRepository
{
    /**
     * Rôle : Initialiser le dépôt Doctrine des fichiers téléversés.
     * Paramètres : Le registre des gestionnaires Doctrine.
     * Retour : Aucun.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UploadFichier::class);
    }

    /**
     * Rôle : Retrouver un fichier par son type métier et son nom sécurisé.
     * Paramètres : Le type du fichier et son nom enregistré.
     * Retour : Le fichier ou null lorsqu'il est absent.
     */
    public function trouverParTypeEtNom(string $type, string $nom): ?UploadFichier
    {
        return $this->createQueryBuilder('uploadFichier')
            ->andWhere('uploadFichier.type = :type')
            ->andWhere('uploadFichier.nom = :nom')
            ->setParameter('type', $type)
            ->setParameter('nom', $nom)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
