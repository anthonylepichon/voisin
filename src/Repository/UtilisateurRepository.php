<?php

/*
 * Description générale : Fournit l'accès Doctrine aux comptes utilisateurs.
 * Rôle : Rechercher et mettre à niveau les utilisateurs gérés par Symfony Security.
 * Tâches : Retrouver un compte par e-mail ou pseudonyme et enregistrer un nouveau hachage de mot de passe.
 * Liens avec les autres fichiers : Utilisé par l'entité Utilisateur et le futur authentificateur de connexion.
 */

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /**
     * Rôle : Initialiser le dépôt Doctrine des utilisateurs.
     * Paramètres : Le registre Doctrine de l'application.
     * Retour : Aucun.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * Rôle : Retrouver un utilisateur à partir d'un e-mail ou d'un pseudonyme.
     * Paramètres : La valeur saisie dans le champ de connexion.
     * Retour : L'utilisateur correspondant ou null si aucun compte ne correspond.
     */
    public function trouverParIdentifiantConnexion(string $identifiant): ?Utilisateur
    {
        return $this->createQueryBuilder('utilisateur')
            ->andWhere('utilisateur.adresseEmail = :identifiant OR utilisateur.pseudonyme = :identifiant')
            ->setParameter('identifiant', $identifiant)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Rôle : Remplacer automatiquement un hachage de mot de passe devenu obsolète.
     * Paramètres : L'utilisateur concerné et son nouveau mot de passe haché.
     * Retour : Aucun.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
