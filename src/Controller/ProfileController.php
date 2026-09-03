<?php

/*
 * Description générale : Contrôleur des profils membres.
 * Rôle : Afficher un profil avec son état d'amitié et permettre au propriétaire de modifier ses données.
 * Tâches : Contrôler l'identité, charger les relations et publications visibles, puis déléguer la gestion de la photo.
 * Liens avec les autres fichiers : Utilise les repositories sociaux, ProfileFormType, FileUploadService, UserActivityService et les gabarits profile.
 */

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\ProfileFormType;
use App\Repository\DemandeAmitieRepository;
use App\Repository\PublicationRepository;
use App\Repository\UtilisateurRepository;
use App\Service\FileUploadService;
use App\Service\UserActivityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    /**
     * Rôle : Rediriger le membre vers son propre profil.
     * Paramètres : Aucun.
     * Retour : Une redirection HTTP.
     */
    #[Route('/profil', name: 'app_profile_current', methods: ['GET'])]
    public function current(): Response
    {
        $utilisateur = $this->getUtilisateurConnecte();

        return $this->redirectToRoute('app_profile_show', [
            'pseudonyme' => $utilisateur->getPseudonyme(),
        ]);
    }

    /**
     * Rôle : Afficher le formulaire de modification du profil connecté.
     * Paramètres : La requête, Doctrine et le service central des fichiers.
     * Retour : Une réponse Twig ou une redirection après enregistrement.
     */
    #[Route('/profil/modifier', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploadService $fileUploadService
    ): Response {
        $utilisateur = $this->getUtilisateurConnecte();
        $anciennePhoto = $utilisateur->getUploadFichier();
        $form = $this->createForm(ProfileFormType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $photo */
            $photo = $form->get('photoProfil')->getData();

            if (null !== $photo) {
                $upload = $fileUploadService->televerserPhotoProfil($photo, $utilisateur);

                if (null === $upload) {
                    $form->get('photoProfil')->addError(new FormError('La photo de profil n’a pas pu être enregistrée. Réessaie plus tard.'));

                    return $this->render('profile/edit.html.twig', [
                        'profileForm' => $form,
                        'utilisateur' => $utilisateur,
                    ]);
                }

                if (null !== $anciennePhoto) {
                    $fileUploadService->supprimerPhotoProfil($utilisateur, $anciennePhoto);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Ton profil a été mis à jour.');

            return $this->redirectToRoute('app_profile_current');
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $form,
            'utilisateur' => $utilisateur,
        ]);
    }

    /**
     * Rôle : Afficher un profil membre.
     * Paramètres : Le pseudonyme, les repositories nécessaires et le service d'activité.
     * Retour : Une réponse Twig ou une erreur 404.
     */
    #[Route('/profil/{pseudonyme}', name: 'app_profile_show', methods: ['GET'])]
    public function show(
        string $pseudonyme,
        UtilisateurRepository $utilisateurRepository,
        DemandeAmitieRepository $demandeAmitieRepository,
        PublicationRepository $publicationRepository,
        UserActivityService $userActivityService,
    ): Response {
        $utilisateur = $utilisateurRepository->findOneBy(['pseudonyme' => $pseudonyme]);

        if (null === $utilisateur) {
            throw $this->createNotFoundException('Profil introuvable.');
        }

        $utilisateurConnecte = $this->getUtilisateurConnecte();
        $userActivityService->enregistrerActivite($utilisateurConnecte, new \DateTimeImmutable());
        $estProprietaire = $utilisateurConnecte->getId() === $utilisateur->getId();
        $statutAmitie = 'aucune';
        $demandeAmitie = null;

        if ($estProprietaire) {
            $statutAmitie = 'proprietaire';
        } elseif ($utilisateurRepository->sontAmis($utilisateurConnecte, $utilisateur)) {
            $statutAmitie = 'ami';
        } else {
            $demandeAmitie = $demandeAmitieRepository->trouverEntre($utilisateurConnecte, $utilisateur);

            if (null !== $demandeAmitie) {
                $expediteur = $demandeAmitie->getExpediteur();

                if (null !== $expediteur && $expediteur->getId() === $utilisateurConnecte->getId()) {
                    $statutAmitie = 'envoyee';
                } else {
                    $statutAmitie = 'recue';
                }
            }
        }

        $inclureReserveesAuxAmis = $estProprietaire || 'ami' === $statutAmitie;
        $amis = $utilisateurRepository->trouverAmis($utilisateur);
        $publications = $publicationRepository->trouverPourProfil($utilisateur, $inclureReserveesAuxAmis);

        return $this->render('profile/show.html.twig', [
            'utilisateur' => $utilisateur,
            'estProprietaire' => $estProprietaire,
            'statutAmitie' => $statutAmitie,
            'demandeAmitie' => $demandeAmitie,
            'amis' => $amis,
            'publications' => $publications,
            'nombreDemandesAmitieEnAttente' => $demandeAmitieRepository->compterRecues($utilisateurConnecte),
        ]);
    }

    /**
     * Rôle : Obtenir l'utilisateur connecté avec son type métier.
     * Paramètres : Aucun.
     * Retour : L'utilisateur connecté.
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
