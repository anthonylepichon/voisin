<?php

/*
 * Description générale : Fournit l'accès Doctrine aux demandes d'amitié.
 * Rôle : Centraliser les recherches liées aux demandes d'amitié en attente.
 * Tâches : Rechercher, compter et comparer les demandes d'amitié encore en attente.
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

    /**
     * Rôle : Retourner les demandes reçues par un utilisateur de la plus récente à la plus ancienne.
     * Paramètres : L'utilisateur destinataire des demandes.
     * Retour : La liste des demandes reçues avec leur expéditeur.
     *
     * @return list<DemandeAmitie>
     */
    public function trouverRecues(Utilisateur $destinataire): array
    {
        return $this->createQueryBuilder('demande')
            ->innerJoin('demande.expediteur', 'expediteur')
            ->addSelect('expediteur')
            ->andWhere('demande.destinataire = :destinataire')
            ->setParameter('destinataire', $destinataire)
            ->orderBy('demande.dateCreation', 'DESC')
            ->addOrderBy('demande.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Rôle : Rechercher une demande existante entre deux utilisateurs dans les deux sens.
     * Paramètres : Les deux utilisateurs concernés par la recherche.
     * Retour : La demande trouvée ou null lorsqu'aucune demande n'existe.
     */
    public function trouverEntre(Utilisateur $premier, Utilisateur $second): ?DemandeAmitie
    {
        return $this->createQueryBuilder('demande')
            ->andWhere(
                '(demande.expediteur = :premier AND demande.destinataire = :second)'
                .' OR (demande.expediteur = :second AND demande.destinataire = :premier)'
            )
            ->setParameter('premier', $premier)
            ->setParameter('second', $second)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
