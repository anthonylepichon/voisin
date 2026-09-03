<?php

/*
 * Description générale : Fournit l'accès Doctrine aux publications.
 * Rôle : Centraliser les recherches de publications et leur visibilité.
 * Tâches : Charger l'accueil public, le fil autorisé, les publications d'un profil et la modération.
 * Liens avec les autres fichiers : Utilisé par Publication, HomeController, FeedController, ProfileController et AdminController.
 */

namespace App\Repository;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Publication>
 */
class PublicationRepository extends ServiceEntityRepository
{
    public const FILTRE_TOUTES = 'toutes';
    public const FILTRE_PUBLIQUES = 'publiques';
    public const FILTRE_AMIS = 'amis';

    /**
     * Rôle : Initialiser le dépôt Doctrine des publications.
     * Paramètres : Le registre Doctrine de l'application.
     * Retour : Aucun.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    /**
     * Rôle : Rechercher les publications visibles dans le fil d'un membre.
     * Paramètres : Le membre connecté et le filtre demandé.
     * Retour : La liste des publications triées de la plus récente à la plus ancienne.
     *
     * @return Publication[]
     */
    public function trouverPourFil(Utilisateur $utilisateur, string $filtre): array
    {
        $constructeur = $this->createQueryBuilder('publication')
            ->innerJoin('publication.utilisateur', 'utilisateur')
            ->addSelect('utilisateur')
            ->leftJoin('publication.utilisateursAimant', 'utilisateurAimant')
            ->addSelect('utilisateurAimant')
            ->leftJoin('publication.commentaires', 'commentaire')
            ->addSelect('commentaire')
            ->leftJoin('publication.uploadFichier', 'uploadFichier')
            ->addSelect('uploadFichier')
            ->orderBy('publication.dateCreation', 'DESC')
            ->addOrderBy('publication.id', 'DESC');

        $conditionAmitie = $constructeur->expr()->orX(
            ':utilisateur MEMBER OF utilisateur.amis'
        );

        $amisEnregistresParUtilisateur = $utilisateur->getAmis()->toArray();

        if ([] !== $amisEnregistresParUtilisateur) {
            $conditionAmitie->add('utilisateur IN (:amisEnregistresParUtilisateur)');
        }

        if (self::FILTRE_PUBLIQUES === $filtre) {
            $constructeur
                ->andWhere('publication.visibilite = :visibilitePublique')
                ->setParameter('visibilitePublique', Publication::VISIBILITE_PUBLIQUE);
        } elseif (self::FILTRE_AMIS === $filtre) {
            $constructeur
                ->andWhere('publication.visibilite = :visibiliteAmis')
                ->andWhere('utilisateur != :utilisateur')
                ->andWhere($conditionAmitie)
                ->setParameter('visibiliteAmis', Publication::VISIBILITE_AMIS)
                ->setParameter('utilisateur', $utilisateur);

            if ([] !== $amisEnregistresParUtilisateur) {
                $constructeur->setParameter('amisEnregistresParUtilisateur', $amisEnregistresParUtilisateur);
            }
        } else {
            $constructeur
                ->andWhere(
                    $constructeur->expr()->orX(
                        'publication.visibilite = :visibilitePublique',
                        $constructeur->expr()->andX(
                            'publication.visibilite = :visibiliteAmis',
                            $constructeur->expr()->orX(
                                'utilisateur = :utilisateur',
                                $conditionAmitie
                            )
                        )
                    )
                )
                ->setParameter('visibilitePublique', Publication::VISIBILITE_PUBLIQUE)
                ->setParameter('visibiliteAmis', Publication::VISIBILITE_AMIS)
                ->setParameter('utilisateur', $utilisateur);

            if ([] !== $amisEnregistresParUtilisateur) {
                $constructeur->setParameter('amisEnregistresParUtilisateur', $amisEnregistresParUtilisateur);
            }
        }

        return $constructeur->getQuery()->getResult();
    }

    /**
     * Rôle : Rechercher les publications visibles sur le profil d'un membre.
     * Paramètres : Le propriétaire du profil et l'autorisation de voir ses publications réservées aux amis.
     * Retour : Les publications du profil triées de la plus récente à la plus ancienne.
     *
     * @return list<Publication>
     */
    public function trouverPourProfil(Utilisateur $profil, bool $inclureReserveesAuxAmis): array
    {
        $constructeur = $this->createQueryBuilder('publication')
            ->innerJoin('publication.utilisateur', 'utilisateur')
            ->addSelect('utilisateur')
            ->leftJoin('publication.utilisateursAimant', 'utilisateurAimant')
            ->addSelect('utilisateurAimant')
            ->leftJoin('publication.commentaires', 'commentaire')
            ->addSelect('commentaire')
            ->leftJoin('publication.uploadFichier', 'uploadFichier')
            ->addSelect('uploadFichier')
            ->andWhere('publication.utilisateur = :profil')
            ->setParameter('profil', $profil)
            ->orderBy('publication.dateCreation', 'DESC')
            ->addOrderBy('publication.id', 'DESC');

        if (!$inclureReserveesAuxAmis) {
            $constructeur
                ->andWhere('publication.visibilite = :visibilitePublique')
                ->setParameter('visibilitePublique', Publication::VISIBILITE_PUBLIQUE);
        }

        return $constructeur->getQuery()->getResult();
    }

    /**
     * Rôle : Charger les cinq publications publiques les plus récentes pour l'accueil.
     * Paramètres : Aucun.
     * Retour : Au maximum cinq publications publiques avec leur utilisateur.
     *
     * @return list<Publication>
     */
    public function trouverCinqPubliquesRecentes(): array
    {
        return $this->createQueryBuilder('publication')
            ->innerJoin('publication.utilisateur', 'utilisateur')
            ->addSelect('utilisateur')
            ->leftJoin('publication.uploadFichier', 'uploadFichier')
            ->addSelect('uploadFichier')
            ->andWhere('publication.visibilite = :visibilitePublique')
            ->setParameter('visibilitePublique', Publication::VISIBILITE_PUBLIQUE)
            ->orderBy('publication.dateCreation', 'DESC')
            ->addOrderBy('publication.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    /**
     * Rôle : Charger toutes les publications pour le tableau de modération.
     * Paramètres : Aucun.
     * Retour : Les publications avec leur utilisateur et leurs commentaires, de la plus récente à la plus ancienne.
     *
     * @return list<Publication>
     */
    public function trouverToutesPourModeration(): array
    {
        return $this->createQueryBuilder('publication')
            ->innerJoin('publication.utilisateur', 'utilisateur')
            ->addSelect('utilisateur')
            ->leftJoin('publication.commentaires', 'commentaire')
            ->addSelect('commentaire')
            ->leftJoin('publication.uploadFichier', 'uploadFichier')
            ->addSelect('uploadFichier')
            ->orderBy('publication.dateCreation', 'DESC')
            ->addOrderBy('publication.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
