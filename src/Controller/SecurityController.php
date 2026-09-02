<?php

/*
 * Description générale : Contrôleur des actions d'authentification de Voisin.
 * Rôle : Afficher la connexion et déclarer le point de déconnexion intercepté par Symfony.
 * Tâches : Transmettre la dernière saisie et l'erreur de connexion au gabarit Twig.
 * Liens avec les autres fichiers : Utilise config/packages/security.yaml et templates/security/login.html.twig.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Rôle : Afficher le formulaire de connexion.
     * Paramètres : Le service Symfony des informations d'authentification.
     * Retour : La réponse HTTP contenant le formulaire.
     */
    #[Route(path: '/connexion', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Rôle : Déclarer le point de déconnexion géré par le firewall Symfony.
     * Paramètres : Aucun.
     * Retour : Aucun, Symfony intercepte systématiquement cette route.
     */
    #[Route(path: '/deconnexion', name: 'app_logout', methods: ['POST'])]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
