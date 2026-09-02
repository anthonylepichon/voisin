<?php

/*
 * Description générale : Provider Symfony des comptes utilisateurs de Voisin.
 * Rôle : Charger un compte à partir de son adresse e-mail ou de son pseudonyme.
 * Tâches : Déléguer la recherche au dépôt Doctrine et actualiser l'utilisateur de session.
 * Liens avec les autres fichiers : Utilise UtilisateurRepository et est déclaré dans config/packages/security.yaml.
 */

namespace App\Security;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UtilisateurProvider implements UserProviderInterface
{
    /**
     * Rôle : Initialiser le provider avec le dépôt des utilisateurs.
     * Paramètres : Le dépôt Doctrine des utilisateurs.
     * Retour : Aucun.
     */
    public function __construct(private UtilisateurRepository $utilisateurRepository)
    {
    }

    /**
     * Rôle : Charger un utilisateur à partir de l'identifiant renseigné à la connexion.
     * Paramètres : L'adresse e-mail ou le pseudonyme fourni par l'utilisateur.
     * Retour : L'utilisateur correspondant.
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $utilisateur = $this->utilisateurRepository->trouverParIdentifiantConnexion($identifier);

        if (null === $utilisateur) {
            $exception = new UserNotFoundException();
            $exception->setUserIdentifier($identifier);

            throw $exception;
        }

        return $utilisateur;
    }

    /**
     * Rôle : Recharger l'utilisateur stocké dans la session Symfony.
     * Paramètres : L'utilisateur issu de la session.
     * Retour : L'utilisateur actualisé depuis la base.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Les instances de "%s" ne sont pas prises en charge.', $user::class));
        }

        $utilisateur = $this->utilisateurRepository->find($user->getId());

        if (null === $utilisateur) {
            $exception = new UserNotFoundException();
            $exception->setUserIdentifier($user->getUserIdentifier());

            throw $exception;
        }

        return $utilisateur;
    }

    /**
     * Rôle : Indiquer si une classe peut être fournie par ce provider.
     * Paramètres : Le nom de classe à contrôler.
     * Retour : Vrai lorsque la classe est Utilisateur.
     */
    public function supportsClass(string $class): bool
    {
        return Utilisateur::class === $class;
    }
}
