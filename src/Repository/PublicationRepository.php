<?php

/*
 * Description générale : Fournit l'accès Doctrine aux publications.
 * Rôle : Centraliser les recherches de publications et leur visibilité.
 * Tâches : Paginer le fil et les profils, agréger les compteurs des cartes, charger l'accueil public et la modération.
 * Liens avec les autres fichiers : Utilisé par Publication, HomeController, FeedController, ProfileController et AdminController.
 */

namespace App\Repository;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Publication>
 */
class PublicationRepository extends ServiceEntityRepository
{
    public const FILTRE_TOUTES = 'toutes';
    public const FILTRE_PUBLIQUES = 'publiques';
    public const FILTRE_AMIS = 'amis';
    public const PUBLICATIONS_PAR_PAGE = 20;

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
     * Paramètres : Le membre connecté, le filtre demandé et la page à charger.
     * Retour : La liste des publications triées de la plus récente à la plus ancienne.
     *
     * @return Publication[]
     */
    public function trouverPourFil(Utilisateur $utilisateur, string $filtre, int $page): array
    {
        $constructeur = $this->creerConstructeurFil($utilisateur, $filtre)
            ->addSelect('utilisateur')
            ->innerJoin('utilisateur.uploadFichier', 'photoProfil')
            ->addSelect('photoProfil')
            ->leftJoin('publication.uploadFichier', 'uploadFichier')
            ->addSelect('uploadFichier')
            ->orderBy('publication.dateCreation', 'DESC')
            ->addOrderBy('publication.id', 'DESC')
            ->setFirstResult(($page - 1) * self::PUBLICATIONS_PAR_PAGE)
            ->setMaxResults(self::PUBLICATIONS_PAR_PAGE);

        return $constructeur->getQuery()->getResult();
    }

    /**
     * Rôle : Compter les publications visibles dans le fil d'un membre.
     * Paramètres : Le membre connecté et le filtre demandé.
     * Retour : Le nombre total de publications correspondant au filtre.
     */
    public function compterPourFil(Utilisateur $utilisateur, string $filtre): int
    {
        return (int) $this->creerConstructeurFil($utilisateur, $filtre)
            ->select('COUNT(publication.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Rôle : Rechercher les publications visibles sur le profil d'un membre.
     * Paramètres : Le propriétaire du profil, l'autorisation de voir ses publications réservées aux amis et la page.
     * Retour : Les publications du profil triées de la plus récente à la plus ancienne.
     *
     * @return list<Publication>
     */
    public function trouverPourProfil(Utilisateur $profil, bool $inclureReserveesAuxAmis, int $page): array
    {
        $constructeur = $this->createQueryBuilder('publication')
            ->innerJoin('publication.utilisateur', 'utilisateur')
            ->addSelect('utilisateur')
            ->innerJoin('utilisateur.uploadFichier', 'photoProfil')
            ->addSelect('photoProfil')
            ->leftJoin('publication.uploadFichier', 'uploadFichier')
            ->addSelect('uploadFichier')
            ->andWhere('publication.utilisateur = :profil')
            ->setParameter('profil', $profil)
            ->orderBy('publication.dateCreation', 'DESC')
            ->addOrderBy('publication.id', 'DESC')
            ->setFirstResult(($page - 1) * self::PUBLICATIONS_PAR_PAGE)
            ->setMaxResults(self::PUBLICATIONS_PAR_PAGE);

        if (!$inclureReserveesAuxAmis) {
            $constructeur
                ->andWhere('publication.visibilite = :visibilitePublique')
                ->setParameter('visibilitePublique', Publication::VISIBILITE_PUBLIQUE);
        }

        return $constructeur->getQuery()->getResult();
    }

    /**
     * Rôle : Compter les publications visibles sur le profil d'un membre.
     * Paramètres : Le propriétaire du profil et l'autorisation de voir ses publications réservées aux amis.
     * Retour : Le nombre total de publications visibles.
     */
    public function compterPourProfil(Utilisateur $profil, bool $inclureReserveesAuxAmis): int
    {
        $constructeur = $this->createQueryBuilder('publication')
            ->select('COUNT(publication.id)')
            ->andWhere('publication.utilisateur = :profil')
            ->setParameter('profil', $profil);

        if (!$inclureReserveesAuxAmis) {
            $constructeur
                ->andWhere('publication.visibilite = :visibilitePublique')
                ->setParameter('visibilitePublique', Publication::VISIBILITE_PUBLIQUE);
        }

        return (int) $constructeur->getQuery()->getSingleScalarResult();
    }

    /**
     * Rôle : Charger les compteurs et l'état du like utiles aux cartes sans hydrater les collections complètes.
     * Paramètres : Les publications affichées et l'utilisateur connecté éventuel.
     * Retour : Les statistiques indexées par identifiant de publication.
     *
     * @param list<Publication> $publications
     * @return array<int, array{nombreMentionsJaime: int, nombreCommentaires: int, utilisateurConnecteAime: bool}>
     */
    public function trouverStatistiquesCartes(array $publications, ?Utilisateur $utilisateurConnecte): array
    {
        $identifiants = [];
        $statistiques = [];

        foreach ($publications as $publication) {
            $identifiant = $publication->getId();

            if (null === $identifiant) {
                continue;
            }

            $identifiants[] = $identifiant;
            $statistiques[$identifiant] = [
                'nombreMentionsJaime' => 0,
                'nombreCommentaires' => 0,
                'utilisateurConnecteAime' => false,
            ];
        }

        if ([] === $identifiants) {
            return $statistiques;
        }

        $comptagesMentionsJaime = $this->createQueryBuilder('publication')
            ->select('publication.id AS publicationId')
            ->addSelect('COUNT(utilisateurAimant.id) AS nombreMentionsJaime')
            ->leftJoin('publication.utilisateursAimant', 'utilisateurAimant')
            ->andWhere('publication.id IN (:identifiants)')
            ->setParameter('identifiants', $identifiants)
            ->groupBy('publication.id')
            ->getQuery()
            ->getArrayResult();

        foreach ($comptagesMentionsJaime as $comptage) {
            $identifiant = (int) $comptage['publicationId'];
            $statistiques[$identifiant]['nombreMentionsJaime'] = (int) $comptage['nombreMentionsJaime'];
        }

        $comptagesCommentaires = $this->createQueryBuilder('publication')
            ->select('publication.id AS publicationId')
            ->addSelect('COUNT(commentaire.id) AS nombreCommentaires')
            ->leftJoin('publication.commentaires', 'commentaire')
            ->andWhere('publication.id IN (:identifiants)')
            ->setParameter('identifiants', $identifiants)
            ->groupBy('publication.id')
            ->getQuery()
            ->getArrayResult();

        foreach ($comptagesCommentaires as $comptage) {
            $identifiant = (int) $comptage['publicationId'];
            $statistiques[$identifiant]['nombreCommentaires'] = (int) $comptage['nombreCommentaires'];
        }

        if (null === $utilisateurConnecte) {
            return $statistiques;
        }

        $publicationsAimees = $this->createQueryBuilder('publication')
            ->select('publication.id AS publicationId')
            ->innerJoin('publication.utilisateursAimant', 'utilisateurAimant')
            ->andWhere('publication.id IN (:identifiants)')
            ->andWhere('utilisateurAimant = :utilisateurConnecte')
            ->setParameter('identifiants', $identifiants)
            ->setParameter('utilisateurConnecte', $utilisateurConnecte)
            ->getQuery()
            ->getArrayResult();

        foreach ($publicationsAimees as $publicationAimee) {
            $identifiant = (int) $publicationAimee['publicationId'];
            $statistiques[$identifiant]['utilisateurConnecteAime'] = true;
        }

        return $statistiques;
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
            ->innerJoin('utilisateur.uploadFichier', 'photoProfil')
            ->addSelect('photoProfil')
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

    /**
     * Rôle : Construire la partie commune des requêtes de lecture et de comptage du fil.
     * Paramètres : Le membre connecté et le filtre demandé.
     * Retour : Le constructeur de requête contenant les règles de visibilité.
     */
    private function creerConstructeurFil(Utilisateur $utilisateur, string $filtre): QueryBuilder
    {
        $constructeur = $this->createQueryBuilder('publication')
            ->innerJoin('publication.utilisateur', 'utilisateur');

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

            return $constructeur;
        }

        if (self::FILTRE_AMIS === $filtre) {
            $constructeur
                ->andWhere('publication.visibilite = :visibiliteAmis')
                ->andWhere('utilisateur != :utilisateur')
                ->andWhere($conditionAmitie)
                ->setParameter('visibiliteAmis', Publication::VISIBILITE_AMIS)
                ->setParameter('utilisateur', $utilisateur);

            if ([] !== $amisEnregistresParUtilisateur) {
                $constructeur->setParameter('amisEnregistresParUtilisateur', $amisEnregistresParUtilisateur);
            }

            return $constructeur;
        }

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

        return $constructeur;
    }
}
