<?php

/*
 * Description générale : Contrôleur de gestion des publications d'un membre.
 * Rôle : Modifier, supprimer et aimer les publications selon les autorisations prévues.
 * Tâches : Gérer la modification, déléguer l'image, contrôler la propriété, sécuriser la suppression et traiter les likes.
 * Liens avec les autres fichiers : Utilise PublicationFormType, PublicationRepository, FileUploadService, UserActivityService et les gabarits publication.
 */

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use App\Form\PublicationFormType;
use App\Repository\PublicationRepository;
use App\Service\FileUploadService;
use App\Service\UserActivityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublicationController extends AbstractController
{
    /**
     * Rôle : Afficher et traiter la modification d'une publication par son propriétaire.
     * Paramètres : L'identifiant, la requête, le dépôt, Doctrine et le service des fichiers.
     * Retour : Une réponse Twig, une redirection ou une erreur HTTP.
     */
    #[Route('/publications/{id}/modifier', name: 'app_publication_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        PublicationRepository $publicationRepository,
        EntityManagerInterface $entityManager,
        FileUploadService $fileUploadService
    ): Response {
        $publication = $this->trouverPublication($id, $publicationRepository);
        $this->refuserSiNonUtilisateur($publication);
        $ancienneImage = $publication->getUploadFichier();
        $form = $this->createForm(PublicationFormType::class, $publication);
        $form->handleRequest($request);

        /** @var UploadedFile|null $image */
        $image = $form->isSubmitted() ? $form->get('image')->getData() : null;
        $supprimerImage = $form->isSubmitted() && $form->get('supprimerImage')->isClicked();

        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $image) {
                $upload = $fileUploadService->televerserImagePublication($image, $publication);

                if (null === $upload) {
                    $publication->setUploadFichier($ancienneImage);
                    $publication->setImageEnAttente(false);
                    $form->get('image')->addError(new FormError('L’image n’a pas pu être enregistrée. Réessaie plus tard.'));

                    return $this->render('publication/edit.html.twig', [
                        'publicationForm' => $form,
                        'publication' => $publication,
                    ]);
                }

            }

            if ((null !== $image || $supprimerImage) && null !== $ancienneImage) {
                $fileUploadService->supprimerImagePublication($publication, $ancienneImage);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Ta publication a été mise à jour.');

            return $this->redirectToRoute('app_publication_edit', ['id' => $publication->getId()]);
        }

        if ($form->isSubmitted()) {
            $publication->setUploadFichier($ancienneImage);
            $publication->setImageEnAttente(false);
        }

        return $this->render('publication/edit.html.twig', [
            'publicationForm' => $form,
            'publication' => $publication,
        ]);
    }

    /**
     * Rôle : Supprimer une publication par son propriétaire ou un administrateur.
     * Paramètres : L'identifiant, la requête, le dépôt, Doctrine et le service des fichiers.
     * Retour : Une redirection vers la liste administrative ou le profil du membre connecté.
     */
    #[Route('/publications/{id}/supprimer', name: 'app_publication_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        Request $request,
        PublicationRepository $publicationRepository,
        EntityManagerInterface $entityManager,
        FileUploadService $fileUploadService
    ): Response {
        $publication = $this->trouverPublication($id, $publicationRepository);
        $this->refuserSiSuppressionInterdite($publication);

        $jeton = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('supprimer-publication-' . $publication->getId(), $jeton)) {
            throw $this->createAccessDeniedException('La demande de suppression est invalide.');
        }

        $image = $publication->getUploadFichier();

        if (null !== $image) {
            $fileUploadService->supprimerImagePublication($publication, $image);
        }

        $entityManager->remove($publication);
        $entityManager->flush();

        $this->addFlash('success', 'La publication a été supprimée.');

        if ($this->isGranted('ROLE_ADMIN') && 'administration' === $request->request->get('_retour')) {
            return $this->redirectToRoute('app_admin_publications');
        }

        return $this->redirectToRoute('app_profile_current');
    }

    /**
     * Rôle : Ajouter le like du membre connecté ou retirer son propre like existant.
     * Paramètres : L'identifiant de la publication, la requête, le dépôt, Doctrine et le service d'activité.
     * Retour : Une redirection vers la page d'origine ou le fil par défaut.
     */
    #[Route('/publications/{id}/aimer', name: 'app_publication_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleLike(
        int $id,
        Request $request,
        PublicationRepository $publicationRepository,
        EntityManagerInterface $entityManager,
        UserActivityService $userActivityService
    ): Response {
        $publication = $this->trouverPublication($id, $publicationRepository);
        $utilisateur = $this->getUtilisateurConnecte();
        $this->refuserSiPublicationInvisible($publication, $utilisateur);

        $jeton = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('aimer-publication-' . $publication->getId(), $jeton)) {
            throw $this->createAccessDeniedException('La demande de like est invalide.');
        }

        if ($publication->getUtilisateursAimant()->contains($utilisateur)) {
            $publication->retirerUtilisateurAimant($utilisateur);
        } else {
            $publication->ajouterUtilisateurAimant($utilisateur);
        }

        $entityManager->flush();
        $userActivityService->enregistrerActivite($utilisateur, new \DateTimeImmutable());

        return $this->redirigerVersOrigine($request);
    }

    /**
     * Rôle : Retrouver une publication par son identifiant technique.
     * Paramètres : L'identifiant demandé et le dépôt des publications.
     * Retour : La publication trouvée ou une erreur 404.
     */
    private function trouverPublication(int $id, PublicationRepository $publicationRepository): Publication
    {
        $publication = $publicationRepository->find($id);

        if (null === $publication) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        return $publication;
    }

    /**
     * Rôle : Refuser la modification d'une publication qui n'appartient pas au membre connecté.
     * Paramètres : La publication demandée.
     * Retour : Aucun.
     */
    private function refuserSiNonUtilisateur(Publication $publication): void
    {
        $utilisateurPublication = $publication->getUtilisateur();

        if (null === $utilisateurPublication || $utilisateurPublication->getId() !== $this->getUtilisateurConnecte()->getId()) {
            throw $this->createAccessDeniedException('Tu ne peux modifier que tes propres publications.');
        }
    }

    /**
     * Rôle : Refuser une suppression qui n'est autorisée ni à l'utilisateur ni à un administrateur.
     * Paramètres : La publication demandée.
     * Retour : Aucun.
     */
    private function refuserSiSuppressionInterdite(Publication $publication): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $this->refuserSiNonUtilisateur($publication);
    }

    /**
     * Rôle : Refuser une interaction avec une publication invisible pour le membre.
     * Paramètres : La publication et le membre qui souhaite interagir.
     * Retour : Aucun.
     */
    private function refuserSiPublicationInvisible(Publication $publication, Utilisateur $utilisateurConnecte): void
    {
        if (Publication::VISIBILITE_PUBLIQUE === $publication->getVisibilite()) {
            return;
        }

        $utilisateurPublication = $publication->getUtilisateur();

        if (null !== $utilisateurPublication && $utilisateurPublication->getId() === $utilisateurConnecte->getId()) {
            return;
        }

        if (null !== $utilisateurPublication && $utilisateurConnecte->getAmis()->contains($utilisateurPublication)) {
            return;
        }

        if (null !== $utilisateurPublication && $utilisateurPublication->getAmis()->contains($utilisateurConnecte)) {
            return;
        }

        throw $this->createAccessDeniedException('Cette publication ne t’est pas accessible.');
    }

    /**
     * Rôle : Rediriger après un like uniquement vers un chemin interne fourni par la page d'origine.
     * Paramètres : La requête contenant le chemin de retour.
     * Retour : La redirection sûre vers l'origine ou vers le fil.
     */
    private function redirigerVersOrigine(Request $request): Response
    {
        $retour = $request->request->get('retour');

        if (!is_string($retour) || '' === $retour) {
            return $this->redirectToRoute('app_feed');
        }

        if (!str_starts_with($retour, '/') || str_starts_with($retour, '//')) {
            return $this->redirectToRoute('app_feed');
        }

        return $this->redirect($retour);
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
