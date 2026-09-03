<?php

/*
 * Description générale : Centralise les règles métier d'accès aux publications.
 * Rôle : Déterminer si un visiteur ou un membre peut voir, modifier ou supprimer une publication.
 * Tâches : Appliquer la visibilité, la propriété, l'amitié et le rôle administrateur sans dépendre d'une réponse HTTP.
 * Liens avec les autres fichiers : Utilise Publication, Utilisateur et Symfony Security ; appelé par les contrôleurs des publications, commentaires et fichiers.
 */

namespace App\Service;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PublicationAccessService
{
    /**
     * Rôle : Initialiser le service avec le contrôle des rôles Symfony.
     * Paramètres : Le contrôleur d'autorisation de Symfony Security.
     * Retour : Aucun.
     */
    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    /**
     * Rôle : Déterminer si une publication est visible pour le visiteur ou le membre courant.
     * Paramètres : La publication et l'éventuel utilisateur connecté.
     * Retour : Vrai lorsque la consultation est autorisée.
     */
    public function peutVoir(Publication $publication, ?Utilisateur $utilisateur): bool
    {
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if (Publication::VISIBILITE_PUBLIQUE === $publication->getVisibilite()) {
            return true;
        }

        $proprietaire = $publication->getUtilisateur();

        if (null === $utilisateur || null === $proprietaire) {
            return false;
        }

        if ($this->estMemeUtilisateur($proprietaire, $utilisateur)) {
            return true;
        }

        return $utilisateur->getAmis()->contains($proprietaire)
            || $proprietaire->getAmis()->contains($utilisateur);
    }

    /**
     * Rôle : Déterminer si un membre peut modifier une publication.
     * Paramètres : La publication et l'utilisateur connecté.
     * Retour : Vrai uniquement pour le propriétaire de la publication.
     */
    public function peutModifier(Publication $publication, Utilisateur $utilisateur): bool
    {
        $proprietaire = $publication->getUtilisateur();

        if (null === $proprietaire) {
            return false;
        }

        return $this->estMemeUtilisateur($proprietaire, $utilisateur);
    }

    /**
     * Rôle : Déterminer si un membre peut supprimer une publication.
     * Paramètres : La publication et l'utilisateur connecté.
     * Retour : Vrai pour le propriétaire ou un administrateur.
     */
    public function peutSupprimer(Publication $publication, Utilisateur $utilisateur): bool
    {
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return $this->peutModifier($publication, $utilisateur);
    }

    /**
     * Rôle : Comparer deux utilisateurs persistés sans dépendre de leur instance Doctrine.
     * Paramètres : Les deux utilisateurs à comparer.
     * Retour : Vrai lorsqu'ils possèdent le même identifiant non nul.
     */
    private function estMemeUtilisateur(Utilisateur $premier, Utilisateur $second): bool
    {
        $premierId = $premier->getId();
        $secondId = $second->getId();

        return null !== $premierId && $premierId === $secondId;
    }
}
