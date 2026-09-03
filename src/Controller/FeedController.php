<?php

/*
 * Description générale : Contrôleur du fil d'actualité des membres.
 * Rôle : Afficher les publications autorisées selon le filtre choisi.
 * Tâches : Actualiser l'activité, normaliser le filtre et transmettre publications, présence et demandes à Twig.
 * Liens avec les autres fichiers : Utilise les repositories du fil, UserActivityService et templates/feed/index.html.twig.
 */

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\DemandeAmitieRepository;
use App\Repository\PublicationRepository;
use App\Repository\UtilisateurRepository;
use App\Service\UserActivityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FeedController extends AbstractController
{
    /**
     * Rôle : Afficher le fil d'actualité filtré du membre connecté.
     * Paramètres : La requête HTTP, les dépôts utiles et le service d'activité.
     * Retour : La réponse Twig du fil d'actualité.
     */
    #[Route('/fil-actualite', name: 'app_feed', methods: ['GET'])]
    public function index(
        Request $request,
        PublicationRepository $publicationRepository,
        UtilisateurRepository $utilisateurRepository,
        DemandeAmitieRepository $demandeAmitieRepository,
        UserActivityService $userActivityService,
    ): Response
    {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $filtre = $request->query->getString('filtre', PublicationRepository::FILTRE_TOUTES);
        $filtresAutorises = [
            PublicationRepository::FILTRE_TOUTES,
            PublicationRepository::FILTRE_PUBLIQUES,
            PublicationRepository::FILTRE_AMIS,
        ];

        if (!in_array($filtre, $filtresAutorises, true)) {
            $filtre = PublicationRepository::FILTRE_TOUTES;
        }

        $dateCourante = new \DateTimeImmutable();
        $userActivityService->enregistrerActivite($utilisateur, $dateCourante);

        $amisEnLigne = [];
        $statutsEnLigne = [];

        foreach ($utilisateurRepository->trouverAmis($utilisateur) as $ami) {
            $estEnLigne = $userActivityService->estEnLigne($ami, $dateCourante);
            $identifiantAmi = $ami->getId();

            if ($identifiantAmi !== null) {
                $statutsEnLigne[$identifiantAmi] = $estEnLigne;
            }

            if ($estEnLigne) {
                $amisEnLigne[] = $ami;
            }
        }

        return $this->render('feed/index.html.twig', [
            'publications' => $publicationRepository->trouverPourFil($utilisateur, $filtre),
            'filtreActif' => $filtre,
            'amisEnLigne' => $amisEnLigne,
            'statutsEnLigne' => $statutsEnLigne,
            'nombreDemandesAmitieEnAttente' => $demandeAmitieRepository->compterRecues($utilisateur),
        ]);
    }
}
