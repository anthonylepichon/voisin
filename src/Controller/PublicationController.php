<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Contrôleur de gestion des publications d'un membre.
 * Rôle : Modifier, supprimer et aimer les publications selon les autorisations prévues.
 * Tâches : Gérer la modification et la suppression compensatoires des images, contrôler les accès et traiter les likes.
 * Liens avec les autres fichiers : Utilise PublicationFormType, PublicationRepository, PublicationAccessService, FileUploadService, UserActivityService et les gabarits publication.
 */

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use App\Form\PublicationFormType;
use App\Repository\PublicationRepository;
use App\Service\FileUploadService;
use App\Service\PublicationAccessService;
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
     * Rôle : Initialiser le contrôleur avec les règles centralisées d'accès aux publications.
     * Paramètres : Le service d'autorisation des publications.
     * Retour : Aucun.
     */
    public function __construct(private readonly PublicationAccessService $publicationAccessService)
    {
    }

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
        $utilisateur = $this->getUtilisateurConnecte();

        if (!$this->publicationAccessService->peutModifier($publication, $utilisateur)) {
            throw $this->createAccessDeniedException('Tu ne peux modifier que tes propres publications.');
        }
        $ancienneImage = $publication->getUploadFichier();
        $form = $this->createForm(PublicationFormType::class, $publication);
        $form->handleRequest($request);

        /** @var UploadedFile|null $image */
        $image = null;
        $nouvelleImage = null;

        if ($form->isSubmitted()) {
            $image = $form->get('image')->getData();
        }

        $supprimerImage = $form->isSubmitted() && $form->get('supprimerImage')->isClicked();

        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $image) {
                $nouvelleImage = $fileUploadService->televerserImagePublication($image, $publication);

                if (null === $nouvelleImage) {
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
                $fileUploadService->preparerSuppressionImagePublication($publication, $ancienneImage);
            }

            try {
                $entityManager->flush();
            } catch (\Throwable $exception) {
                if (null !== $nouvelleImage) {
                    $fileUploadService->compenserTeleversement($nouvelleImage);
                }

                throw $exception;
            }

            if ((null !== $image || $supprimerImage) && null !== $ancienneImage) {
                $fileUploadService->supprimerFichierPhysique($ancienneImage);
            }

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
        $utilisateur = $this->getUtilisateurConnecte();

        if (!$this->publicationAccessService->peutSupprimer($publication, $utilisateur)) {
            throw $this->createAccessDeniedException('Tu ne peux pas supprimer cette publication.');
        }

        $jeton = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('supprimer-publication-' . $publication->getId(), $jeton)) {
            throw $this->createAccessDeniedException('La demande de suppression est invalide.');
        }

        $image = $publication->getUploadFichier();

        if (null !== $image) {
            $fileUploadService->preparerSuppressionImagePublication($publication, $image);
        }

        $entityManager->remove($publication);
        $entityManager->flush();

        if (null !== $image) {
            $fileUploadService->supprimerFichierPhysique($image);
        }

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
        if (!$this->publicationAccessService->peutVoir($publication, $utilisateur)) {
            throw $this->createAccessDeniedException('Cette publication ne t’est pas accessible.');
        }

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
        $userActivityService->enregistrerActivite(
            $utilisateur,
            new \DateTimeImmutable('now', new \DateTimeZone('UTC'))
        );

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
