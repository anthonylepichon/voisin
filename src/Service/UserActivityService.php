<?php

/*
 * Description générale : Gère l'activité récente des utilisateurs de l'application Voisin.
 * Rôle : Enregistrer une activité et déterminer si un utilisateur est actuellement en ligne.
 * Tâches : Mettre à jour la date de dernière activité et appliquer le délai de présence de cinq minutes.
 * Liens avec les autres fichiers : Utilise l'entité Utilisateur et Doctrine pour conserver l'activité affichée dans le fil.
 */

namespace App\Service;

use App\Entity\Utilisateur;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class UserActivityService
{
    /**
     * Rôle : Initialiser le service de suivi de l'activité utilisateur.
     * Paramètres : Le gestionnaire d'entités Doctrine.
     * Retour : Aucun.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Rôle : Enregistrer la date de dernière activité d'un utilisateur.
     * Paramètres : L'utilisateur concerné et la date d'activité à enregistrer.
     * Retour : Aucun.
     */
    public function enregistrerActivite(Utilisateur $utilisateur, DateTimeImmutable $dateActivite): void
    {
        $utilisateur->setDateDerniereActivite($dateActivite);

        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();
    }

    /**
     * Rôle : Indiquer si l'utilisateur a été actif depuis strictement moins de cinq minutes.
     * Paramètres : L'utilisateur concerné et la date utilisée comme référence.
     * Retour : Vrai si l'utilisateur est en ligne, faux dans les autres cas.
     */
    public function estEnLigne(Utilisateur $utilisateur, DateTimeImmutable $dateReference): bool
    {
        $dateDerniereActivite = $utilisateur->getDateDerniereActivite();

        if (null === $dateDerniereActivite) {
            return false;
        }

        if ($dateDerniereActivite > $dateReference) {
            return false;
        }

        $limitePresence = $dateReference->modify('-5 minutes');

        return $dateDerniereActivite > $limitePresence;
    }
}
