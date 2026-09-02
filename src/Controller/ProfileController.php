<?php

/*
 * Description générale : Contrôleur des profils membres.
 * Rôle : Afficher un profil et permettre à son propriétaire de modifier les données autorisées.
 * Tâches : Contrôler l'identité connectée, valider les modifications et gérer le remplacement de photo.
 * Liens avec les autres fichiers : Utilise ProfileFormType, UtilisateurRepository et les gabarits profile.
 */

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\ProfileFormType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

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
     * Paramètres : La requête, le gestionnaire d'entités et le slugger.
     * Retour : Une réponse Twig ou une redirection après enregistrement.
     */
    #[Route('/profil/modifier', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $utilisateur = $this->getUtilisateurConnecte();
        $anciennePhoto = $utilisateur->getNomPhotoProfil();
        $form = $this->createForm(ProfileFormType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $photo */
            $photo = $form->get('photoProfil')->getData();

            if (null !== $photo) {
                $nomNouvellePhoto = $this->enregistrerNouvellePhoto($photo, $utilisateur, $slugger);

                if (null === $nomNouvellePhoto) {
                    $form->get('photoProfil')->addError(new FormError('La photo de profil n’a pas pu être enregistrée. Réessaie plus tard.'));

                    return $this->render('profile/edit.html.twig', [
                        'profileForm' => $form,
                        'utilisateur' => $utilisateur,
                    ]);
                }

                $utilisateur->setNomPhotoProfil($nomNouvellePhoto);
            }

            $entityManager->flush();

            if (null !== $photo && null !== $anciennePhoto) {
                $this->supprimerAnciennePhotoInutilisee($anciennePhoto, $entityManager);
            }

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
     * Paramètres : Le pseudonyme demandé et le dépôt des utilisateurs.
     * Retour : Une réponse Twig ou une erreur 404.
     */
    #[Route('/profil/{pseudonyme}', name: 'app_profile_show', methods: ['GET'])]
    public function show(string $pseudonyme, UtilisateurRepository $utilisateurRepository): Response
    {
        $utilisateur = $utilisateurRepository->findOneBy(['pseudonyme' => $pseudonyme]);

        if (null === $utilisateur) {
            throw $this->createNotFoundException('Profil introuvable.');
        }

        $utilisateurConnecte = $this->getUser();
        $estProprietaire = $utilisateurConnecte instanceof Utilisateur
            && $utilisateurConnecte->getId() === $utilisateur->getId();

        return $this->render('profile/show.html.twig', [
            'utilisateur' => $utilisateur,
            'estProprietaire' => $estProprietaire,
        ]);
    }

    /**
     * Rôle : Enregistrer une nouvelle photo dans le répertoire privé des profils.
     * Paramètres : Le fichier téléversé, son propriétaire et le slugger.
     * Retour : Le nom enregistré ou null en cas d'échec.
     */
    private function enregistrerNouvellePhoto(
        UploadedFile $photo,
        Utilisateur $utilisateur,
        SluggerInterface $slugger
    ): ?string {
        $extension = $photo->guessExtension();

        if (null === $extension) {
            return null;
        }

        $nomFichier = sprintf(
            '%s-profil-%s.%s',
            uniqid(),
            $slugger->slug((string) $utilisateur->getPseudonyme())->lower(),
            $extension
        );

        try {
            $photo->move($this->getParameter('kernel.project_dir').'/uploads/profils', $nomFichier);
        } catch (FileException) {
            return null;
        }

        return $nomFichier;
    }

    /**
     * Rôle : Effacer une ancienne photo uniquement lorsqu'aucun compte ne l'utilise.
     * Paramètres : Le nom de l'ancienne photo et le gestionnaire d'entités.
     * Retour : Aucun.
     */
    private function supprimerAnciennePhotoInutilisee(
        string $nomPhoto,
        EntityManagerInterface $entityManager
    ): void {
        $nombreUtilisateurs = $entityManager->getRepository(Utilisateur::class)->count([
            'nomPhotoProfil' => $nomPhoto,
        ]);

        if (0 !== $nombreUtilisateurs) {
            return;
        }

        $cheminPhoto = $this->getParameter('kernel.project_dir').'/uploads/profils/'.$nomPhoto;

        if (is_file($cheminPhoto)) {
            unlink($cheminPhoto);
        }
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
