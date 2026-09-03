<?php

/*
 * Description générale : Dépôt Doctrine des fichiers téléversés.
 * Rôle : Retrouver les métadonnées d'un fichier rattaché à un profil ou à une publication.
 * Tâches : Fournir les opérations standards Doctrine utilisées par le service de téléversement.
 * Liens avec les autres fichiers : Utilise UploadFichier et est injecté dans FileUploadService.
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
     * Retour : Le fichier avec ses relations ou null lorsqu'il est absent.
     */
    public function trouverParTypeEtNom(string $type, string $nom): ?UploadFichier
    {
        return $this->createQueryBuilder('uploadFichier')
            ->leftJoin('uploadFichier.utilisateur', 'utilisateur')
            ->addSelect('utilisateur')
            ->leftJoin('uploadFichier.publication', 'publication')
            ->addSelect('publication')
            ->leftJoin('publication.utilisateur', 'utilisateurPublication')
            ->addSelect('utilisateurPublication')
            ->andWhere('uploadFichier.type = :type')
            ->andWhere('uploadFichier.nom = :nom')
            ->setParameter('type', $type)
            ->setParameter('nom', $nom)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
