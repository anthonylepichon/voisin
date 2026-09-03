<?php

/*
 * Description générale : Contrôleur de la page d'accueil publique de Voisin.
 * Rôle : Présenter l'application et les cinq publications publiques les plus récentes.
 * Tâches : Charger les publications autorisées et transmettre les données à la vue d'accueil.
 * Liens avec les autres fichiers : Utilise PublicationRepository et templates/home/index.html.twig.
 */

namespace App\Controller;

use App\Repository\PublicationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    /**
     * Rôle : Afficher la page publique et ses cinq publications publiques les plus récentes.
     * Paramètres : Le dépôt Doctrine des publications.
     * Retour : La réponse HTTP contenant le gabarit Twig de l'accueil.
     */
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(PublicationRepository $publicationRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'publications' => $publicationRepository->trouverCinqPubliquesRecentes(),
        ]);
    }
}
