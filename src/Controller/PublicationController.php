<?php

/*
 * Description générale : Contrôleur de gestion des publications d'un membre.
 * Rôle : Créer, modifier et supprimer les publications selon les autorisations prévues.
 * Tâches : Gérer le formulaire, l'image associée, les contrôles de propriété et la suppression sécurisée.
 * Liens avec les autres fichiers : Utilise PublicationFormType, PublicationRepository, Utilisateur et les gabarits publication.
 */

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use App\Form\PublicationFormType;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class PublicationController extends AbstractController
{
    /**
     * Rôle : Afficher et traiter la création d'une publication.
     * Paramètres : La requête, le gestionnaire d'entités et le slugger.
     * Retour : Une réponse Twig ou une redirection après création.
     */
    #[Route('/publications/nouvelle', name: 'app_publication_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $publication = new Publication();
        $publication->setAuteur($this->getUtilisateurConnecte());
        $form = $this->createForm(PublicationFormType::class, $publication);
        $form->handleRequest($request);

        /** @var UploadedFile|null $image */
        $image = $form->isSubmitted() ? $form->get('image')->getData() : null;

        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $image) {
                $nomImage = $this->enregistrerImage($image, $slugger);

                if (null === $nomImage) {
                    $publication->setNomImage(null);
                    $form->get('image')->addError(new FormError('L’image n’a pas pu être enregistrée. Réessaie plus tard.'));

                    return $this->render('publication/create.html.twig', [
                        'publicationForm' => $form,
                    ]);
                }

                $publication->setNomImage($nomImage);
            }

            $entityManager->persist($publication);
            $entityManager->flush();
            $this->addFlash('success', 'Ta publication a été créée.');

            return $this->redirectToRoute('app_publication_edit', ['id' => $publication->getId()]);
        }

        if ($form->isSubmitted() && null !== $image) {
            $publication->setNomImage(null);
        }

        return $this->render('publication/create.html.twig', [
            'publicationForm' => $form,
        ]);
    }

    /**
     * Rôle : Afficher et traiter la modification d'une publication de son auteur.
     * Paramètres : L'identifiant, la requête, le dépôt, le gestionnaire d'entités et le slugger.
     * Retour : Une réponse Twig, une redirection ou une erreur HTTP.
     */
    #[Route('/publications/{id}/modifier', name: 'app_publication_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        PublicationRepository $publicationRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $publication = $this->trouverPublication($id, $publicationRepository);
        $this->refuserSiNonAuteur($publication);
        $ancienneImage = $publication->getNomImage();
        $form = $this->createForm(PublicationFormType::class, $publication);
        $form->handleRequest($request);

        /** @var UploadedFile|null $image */
        $image = $form->isSubmitted() ? $form->get('image')->getData() : null;
        $supprimerImage = $form->isSubmitted() && $form->get('supprimerImage')->isClicked();

        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $image) {
                $nomImage = $this->enregistrerImage($image, $slugger);

                if (null === $nomImage) {
                    $publication->setNomImage($ancienneImage);
                    $form->get('image')->addError(new FormError('L’image n’a pas pu être enregistrée. Réessaie plus tard.'));

                    return $this->render('publication/edit.html.twig', [
                        'publicationForm' => $form,
                        'publication' => $publication,
                    ]);
                }

                $publication->setNomImage($nomImage);
            } elseif ($supprimerImage) {
                $publication->setNomImage(null);
            }

            $entityManager->flush();

            if ((null !== $image || $supprimerImage) && null !== $ancienneImage) {
                $this->supprimerImage($ancienneImage);
            }

            $this->addFlash('success', 'Ta publication a été mise à jour.');

            return $this->redirectToRoute('app_publication_edit', ['id' => $publication->getId()]);
        }

        if ($form->isSubmitted()) {
            $publication->setNomImage($ancienneImage);
        }

        return $this->render('publication/edit.html.twig', [
            'publicationForm' => $form,
            'publication' => $publication,
        ]);
    }

    /**
     * Rôle : Supprimer une publication de son auteur ou d'un administrateur.
     * Paramètres : L'identifiant, la requête, le dépôt et le gestionnaire d'entités.
     * Retour : Une redirection vers le profil du membre connecté.
     */
    #[Route('/publications/{id}/supprimer', name: 'app_publication_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        Request $request,
        PublicationRepository $publicationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $publication = $this->trouverPublication($id, $publicationRepository);
        $this->refuserSiSuppressionInterdite($publication);

        $jeton = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('supprimer-publication-'.$publication->getId(), $jeton)) {
            throw $this->createAccessDeniedException('La demande de suppression est invalide.');
        }

        $nomImage = $publication->getNomImage();
        $entityManager->remove($publication);
        $entityManager->flush();

        if (null !== $nomImage) {
            $this->supprimerImage($nomImage);
        }

        $this->addFlash('success', 'La publication a été supprimée.');

        return $this->redirectToRoute('app_profile_current');
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
    private function refuserSiNonAuteur(Publication $publication): void
    {
        $auteur = $publication->getAuteur();

        if (null === $auteur || $auteur->getId() !== $this->getUtilisateurConnecte()->getId()) {
            throw $this->createAccessDeniedException('Tu ne peux modifier que tes propres publications.');
        }
    }

    /**
     * Rôle : Refuser une suppression qui n'est autorisée ni à l'auteur ni à un administrateur.
     * Paramètres : La publication demandée.
     * Retour : Aucun.
     */
    private function refuserSiSuppressionInterdite(Publication $publication): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $this->refuserSiNonAuteur($publication);
    }

    /**
     * Rôle : Enregistrer une image téléversée dans le répertoire privé des publications.
     * Paramètres : Le fichier image et le slugger Symfony.
     * Retour : Le nom enregistré ou null en cas d'échec.
     */
    private function enregistrerImage(UploadedFile $image, SluggerInterface $slugger): ?string
    {
        $extension = $image->guessExtension();

        if (null === $extension) {
            return null;
        }

        $nomOriginal = $image->getClientOriginalName();
        $nomFichier = sprintf('%s-%s.%s', uniqid(), $slugger->slug(pathinfo($nomOriginal, PATHINFO_FILENAME))->lower(), $extension);

        try {
            $image->move($this->getParameter('kernel.project_dir').'/uploads/publications', $nomFichier);
        } catch (FileException) {
            return null;
        }

        return $nomFichier;
    }

    /**
     * Rôle : Supprimer le fichier image qui n'est plus rattaché à une publication.
     * Paramètres : Le nom de fichier à retirer.
     * Retour : Aucun.
     */
    private function supprimerImage(string $nomImage): void
    {
        $cheminImage = $this->getParameter('kernel.project_dir').'/uploads/publications/'.$nomImage;

        if (is_file($cheminImage)) {
            unlink($cheminImage);
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
