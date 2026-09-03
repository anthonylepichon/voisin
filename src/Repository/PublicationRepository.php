<?php

/*
 * Description générale : Fournit l'accès Doctrine aux publications.
 * Rôle : Centraliser les recherches de publications et leur visibilité.
 * Tâches : Charger le fil autorisé selon le membre et le filtre sélectionné.
 * Liens avec les autres fichiers : Utilisé par Publication, FeedController et les écrans affichant des publications.
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
            ->innerJoin('publication.auteur', 'auteur')
            ->addSelect('auteur')
            ->leftJoin('publication.utilisateursAimant', 'utilisateurAimant')
            ->addSelect('utilisateurAimant')
            ->leftJoin('publication.commentaires', 'commentaire')
            ->addSelect('commentaire')
            ->orderBy('publication.dateCreation', 'DESC')
            ->addOrderBy('publication.id', 'DESC');

        $conditionAmitie = $constructeur->expr()->orX(
            ':utilisateur MEMBER OF auteur.amis'
        );

        $amisEnregistresParUtilisateur = $utilisateur->getAmis()->toArray();

        if ([] !== $amisEnregistresParUtilisateur) {
            $conditionAmitie->add('auteur IN (:amisEnregistresParUtilisateur)');
        }

        if (self::FILTRE_PUBLIQUES === $filtre) {
            $constructeur
                ->andWhere('publication.visibilite = :visibilitePublique')
                ->setParameter('visibilitePublique', Publication::VISIBILITE_PUBLIQUE);
        } elseif (self::FILTRE_AMIS === $filtre) {
            $constructeur
                ->andWhere('publication.visibilite = :visibiliteAmis')
                ->andWhere('auteur != :utilisateur')
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
                                'auteur = :utilisateur',
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
}
