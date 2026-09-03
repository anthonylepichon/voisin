<?php

/*
 * Description générale : Contrôleur du tableau de bord et de la modération administratifs.
 * Rôle : Réserver aux administrateurs la consultation des membres et de toutes les publications.
 * Tâches : Charger les listes administratives, enregistrer l'activité et afficher les actions de modération autorisées.
 * Liens avec les autres fichiers : Utilise UtilisateurRepository, PublicationRepository, UserActivityService et les vues admin.
 */

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\PublicationRepository;
use App\Repository\UtilisateurRepository;
use App\Service\UserActivityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/administration', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    /**
     * Rôle : Afficher le tableau de bord et la liste des membres sans action sur leurs comptes.
     * Paramètres : Le dépôt des utilisateurs et le service d'activité.
     * Retour : La page Twig du tableau de bord.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        UtilisateurRepository $utilisateurRepository,
        UserActivityService $userActivityService
    ): Response {
        $utilisateur = $this->getUtilisateurConnecte();
        $userActivityService->enregistrerActivite($utilisateur, new \DateTimeImmutable());

        return $this->render('admin/index.html.twig', [
            'membres' => $utilisateurRepository->trouverTousPourAdministration(),
        ]);
    }

    /**
     * Rôle : Afficher toutes les publications et les actions de modération autorisées.
     * Paramètres : Le dépôt des publications et le service d'activité.
     * Retour : La page Twig de modération des publications.
     */
    #[Route('/publications', name: 'publications', methods: ['GET'])]
    public function publications(
        PublicationRepository $publicationRepository,
        UserActivityService $userActivityService
    ): Response {
        $utilisateur = $this->getUtilisateurConnecte();
        $userActivityService->enregistrerActivite($utilisateur, new \DateTimeImmutable());

        return $this->render('admin/publications.html.twig', [
            'publications' => $publicationRepository->trouverToutesPourModeration(),
        ]);
    }

    /**
     * Rôle : Obtenir l'administrateur connecté avec son type métier.
     * Paramètres : Aucun.
     * Retour : L'utilisateur administrateur connecté.
     */
    private function getUtilisateurConnecte(): Utilisateur
    {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        return $utilisateur;
    }
}
