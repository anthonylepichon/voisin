<?php

/*
 * Description générale : Contrôleur de la page d'accueil publique temporaire.
 * Rôle : Rendre visible le socle Twig commun avant le développement des fonctionnalités métier.
 * Tâches : Déclarer la route d'accueil et transmettre la réponse Twig associée.
 * Liens avec les autres fichiers : Utilise templates/home/index.html.twig et templates/base.html.twig.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    /**
     * Rôle : Afficher la page publique minimale destinée au contrôle du socle Twig.
     * Paramètres : Aucun.
     * Retour : La réponse HTTP contenant le gabarit Twig de l'accueil.
     */
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }
}
