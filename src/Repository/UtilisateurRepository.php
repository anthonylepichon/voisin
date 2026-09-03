<?php

/*
 * Description générale : Fournit l'accès Doctrine aux comptes utilisateurs.
 * Rôle : Rechercher les utilisateurs, leurs amis et mettre à niveau leurs accès de sécurité.
 * Tâches : Retrouver un compte, réunir ses amis, préparer la liste administrative et mettre à niveau un mot de passe.
 * Liens avec les autres fichiers : Utilisé par Utilisateur, Symfony Security, les fonctionnalités sociales et AdminController.
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
     * Rôle : Retourner tous les amis d'un utilisateur quelle que soit l'orientation de la relation enregistrée.
     * Paramètres : L'utilisateur dont les amis doivent être recherchés.
     * Retour : La liste des amis sans doublon.
     *
     * @return list<Utilisateur>
     */
    public function trouverAmis(Utilisateur $utilisateur): array
    {
        $amis = array_values($utilisateur->getAmis()->toArray());

        $amisRelationsInverses = $this->createQueryBuilder('ami')
            ->innerJoin('ami.amis', 'utilisateurLie')
            ->andWhere('utilisateurLie = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->getQuery()
            ->getResult();

        foreach ($amisRelationsInverses as $ami) {
            if (!in_array($ami, $amis, true)) {
                $amis[] = $ami;
            }
        }

        return $amis;
    }

    /**
     * Rôle : Indiquer si deux utilisateurs sont déjà amis quelle que soit l'orientation enregistrée.
     * Paramètres : Les deux utilisateurs à comparer.
     * Retour : Vrai lorsque la relation existe, faux sinon.
     */
    public function sontAmis(Utilisateur $premier, Utilisateur $second): bool
    {
        return in_array($second, $this->trouverAmis($premier), true);
    }

    /**
     * Rôle : Retourner tous les comptes destinés à la consultation administrative.
     * Paramètres : Aucun.
     * Retour : La liste des utilisateurs triée par pseudonyme.
     *
     * @return list<Utilisateur>
     */
    public function trouverTousPourAdministration(): array
    {
        return $this->createQueryBuilder('utilisateur')
            ->orderBy('utilisateur.pseudonyme', 'ASC')
            ->addOrderBy('utilisateur.id', 'ASC')
            ->getQuery()
            ->getResult();
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
