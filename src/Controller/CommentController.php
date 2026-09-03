<?php

/*
 * Description générale : Contrôleur de consultation et de gestion des commentaires.
 * Rôle : Afficher, créer, modifier et supprimer les commentaires selon les autorisations prévues.
 * Tâches : Contrôler la visibilité, traiter CommentFormType, appliquer les droits de propriété et protéger les suppressions.
 * Liens avec les autres fichiers : Utilise Commentaire, Publication, leurs repositories, CommentFormType, UserActivityService et templates/comment/index.html.twig.
 */

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Entity\Utilisateur;
use App\Form\CommentFormType;
use App\Repository\CommentaireRepository;
use App\Repository\PublicationRepository;
use App\Service\UserActivityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CommentController extends AbstractController
{
    /**
     * Rôle : Afficher une publication visible et ses commentaires dans l'ordre chronologique.
     * Paramètres : L'identifiant, les dépôts et le service d'activité.
     * Retour : La page Twig des commentaires.
     */
    #[Route('/publications/{id}/commentaires', name: 'app_comment_index', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function index(
        int $id,
        PublicationRepository $publicationRepository,
        CommentaireRepository $commentaireRepository,
        UserActivityService $userActivityService
    ): Response {
        $publication = $this->trouverPublication($id, $publicationRepository);
        $utilisateur = $this->getUtilisateurConnecte();
        $this->refuserSiPublicationInvisible($publication, $utilisateur);
        $userActivityService->enregistrerActivite($utilisateur, new \DateTimeImmutable());

        $commentaire = new Commentaire();
        $formulaire = $this->creerFormulaireAjout($commentaire, $publication);

        return $this->afficherPage(
            $publication,
            $commentaireRepository,
            $formulaire,
            null,
            null
        );
    }

    /**
     * Rôle : Ajouter un commentaire à une publication visible.
     * Paramètres : L'identifiant, la requête, les dépôts, Doctrine et le service d'activité.
     * Retour : La page avec erreurs ou une redirection après création.
     */
    #[Route('/publications/{id}/commentaires', name: 'app_comment_new', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function new(
        int $id,
        Request $request,
        PublicationRepository $publicationRepository,
        CommentaireRepository $commentaireRepository,
        EntityManagerInterface $entityManager,
        UserActivityService $userActivityService
    ): Response {
        $publication = $this->trouverPublication($id, $publicationRepository);
        $utilisateur = $this->getUtilisateurConnecte();
        $this->refuserSiPublicationInvisible($publication, $utilisateur);

        $commentaire = new Commentaire();
        $commentaire->setUtilisateur($utilisateur);
        $commentaire->setPublication($publication);
        $formulaire = $this->creerFormulaireAjout($commentaire, $publication);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $entityManager->persist($commentaire);
            $entityManager->flush();
            $userActivityService->enregistrerActivite($utilisateur, new \DateTimeImmutable());
            $this->addFlash('success', 'Ton commentaire a été ajouté.');

            return $this->redirectToRoute('app_comment_index', ['id' => $publication->getId()]);
        }

        $userActivityService->enregistrerActivite($utilisateur, new \DateTimeImmutable());

        return $this->afficherPage(
            $publication,
            $commentaireRepository,
            $formulaire,
            null,
            null
        );
    }

    /**
     * Rôle : Afficher et traiter la modification du commentaire appartenant au membre connecté.
     * Paramètres : L'identifiant, la requête, les dépôts, Doctrine et le service d'activité.
     * Retour : La page en édition ou une redirection après modification.
     */
    #[Route('/commentaires/{id}/modifier', name: 'app_comment_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        CommentaireRepository $commentaireRepository,
        EntityManagerInterface $entityManager,
        UserActivityService $userActivityService
    ): Response {
        $commentaire = $this->trouverCommentaire($id, $commentaireRepository);
        $publication = $commentaire->getPublication();

        if (null === $publication) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        $utilisateur = $this->getUtilisateurConnecte();
        $this->refuserSiPublicationInvisible($publication, $utilisateur);
        $this->refuserSiNonUtilisateur($commentaire, $utilisateur);

        $formulaireEdition = $this->createForm(CommentFormType::class, $commentaire, [
            'action' => $this->generateUrl('app_comment_edit', ['id' => $commentaire->getId()]),
            'method' => 'POST',
        ]);
        $formulaireEdition->handleRequest($request);

        if ($formulaireEdition->isSubmitted() && $formulaireEdition->isValid()) {
            $entityManager->flush();
            $userActivityService->enregistrerActivite($utilisateur, new \DateTimeImmutable());
            $this->addFlash('success', 'Ton commentaire a été modifié.');

            return $this->redirectToRoute('app_comment_index', ['id' => $publication->getId()]);
        }

        $userActivityService->enregistrerActivite($utilisateur, new \DateTimeImmutable());
        $formulaireAjout = $this->creerFormulaireAjout(new Commentaire(), $publication);

        return $this->afficherPage(
            $publication,
            $commentaireRepository,
            $formulaireAjout,
            $commentaire,
            $formulaireEdition
        );
    }

    /**
     * Rôle : Supprimer un commentaire lorsque le membre ou l'administrateur en a le droit.
     * Paramètres : L'identifiant, la requête, le dépôt, Doctrine et le service d'activité.
     * Retour : Une redirection vers les commentaires de la publication.
     */
    #[Route('/commentaires/{id}/supprimer', name: 'app_comment_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        Request $request,
        CommentaireRepository $commentaireRepository,
        EntityManagerInterface $entityManager,
        UserActivityService $userActivityService
    ): Response {
        $commentaire = $this->trouverCommentaire($id, $commentaireRepository);
        $publication = $commentaire->getPublication();

        if (null === $publication) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        $utilisateur = $this->getUtilisateurConnecte();
        $this->refuserSiPublicationInvisible($publication, $utilisateur);
        $this->refuserSiSuppressionInterdite($commentaire, $publication, $utilisateur);

        $jeton = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('supprimer-commentaire-' . $commentaire->getId(), $jeton)) {
            throw $this->createAccessDeniedException('La demande de suppression est invalide.');
        }

        $entityManager->remove($commentaire);
        $entityManager->flush();
        $userActivityService->enregistrerActivite($utilisateur, new \DateTimeImmutable());
        $this->addFlash('success', 'Le commentaire a été supprimé.');

        return $this->redirectToRoute('app_comment_index', ['id' => $publication->getId()]);
    }

    /**
     * Rôle : Construire le formulaire d'ajout avec sa route dédiée.
     * Paramètres : Le commentaire vide et la publication concernée.
     * Retour : Le formulaire Symfony prêt à être affiché.
     */
    private function creerFormulaireAjout(Commentaire $commentaire, Publication $publication): FormInterface
    {
        return $this->createForm(CommentFormType::class, $commentaire, [
            'action' => $this->generateUrl('app_comment_new', ['id' => $publication->getId()]),
            'method' => 'POST',
        ]);
    }

    /**
     * Rôle : Rendre la page commune de consultation, création et édition.
     * Paramètres : La publication, le dépôt, le formulaire d'ajout et l'éventuelle édition.
     * Retour : La réponse Twig contenant tous les commentaires visibles.
     */
    private function afficherPage(
        Publication $publication,
        CommentaireRepository $commentaireRepository,
        FormInterface $formulaireAjout,
        ?Commentaire $commentaireEdite,
        ?FormInterface $formulaireEdition
    ): Response {
        $vueEdition = null;

        if (null !== $formulaireEdition) {
            $vueEdition = $formulaireEdition->createView();
        }

        return $this->render('comment/index.html.twig', [
            'publication' => $publication,
            'commentaires' => $commentaireRepository->trouverParPublicationAncienPremier($publication),
            'commentForm' => $formulaireAjout->createView(),
            'editedComment' => $commentaireEdite,
            'editForm' => $vueEdition,
        ]);
    }

    /**
     * Rôle : Retrouver une publication par son identifiant.
     * Paramètres : L'identifiant demandé et le dépôt des publications.
     * Retour : La publication ou une erreur 404.
     */
    private function trouverPublication(int $id, PublicationRepository $publicationRepository): Publication
    {
        $publication = $publicationRepository->find($id);

        if (!$publication instanceof Publication) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        return $publication;
    }

    /**
     * Rôle : Retrouver un commentaire par son identifiant.
     * Paramètres : L'identifiant demandé et le dépôt des commentaires.
     * Retour : Le commentaire ou une erreur 404.
     */
    private function trouverCommentaire(int $id, CommentaireRepository $commentaireRepository): Commentaire
    {
        $commentaire = $commentaireRepository->find($id);

        if (!$commentaire instanceof Commentaire) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        return $commentaire;
    }

    /**
     * Rôle : Refuser l'accès aux commentaires d'une publication invisible.
     * Paramètres : La publication et le membre connecté.
     * Retour : Aucun.
     */
    private function refuserSiPublicationInvisible(Publication $publication, Utilisateur $utilisateurConnecte): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

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
     * Rôle : Refuser la modification d'un commentaire appartenant à un autre membre.
     * Paramètres : Le commentaire et le membre connecté.
     * Retour : Aucun.
     */
    private function refuserSiNonUtilisateur(Commentaire $commentaire, Utilisateur $utilisateurConnecte): void
    {
        $utilisateurCommentaire = $commentaire->getUtilisateur();

        if (null === $utilisateurCommentaire || $utilisateurCommentaire->getId() !== $utilisateurConnecte->getId()) {
            throw $this->createAccessDeniedException('Tu ne peux modifier que tes propres commentaires.');
        }
    }

    /**
     * Rôle : Vérifier le droit de supprimer un commentaire.
     * Paramètres : Le commentaire, sa publication et le membre connecté.
     * Retour : Aucun.
     */
    private function refuserSiSuppressionInterdite(
        Commentaire $commentaire,
        Publication $publication,
        Utilisateur $utilisateurConnecte
    ): void {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $utilisateurCommentaire = $commentaire->getUtilisateur();

        if (null !== $utilisateurCommentaire && $utilisateurCommentaire->getId() === $utilisateurConnecte->getId()) {
            return;
        }

        $utilisateurPublication = $publication->getUtilisateur();

        if (null !== $utilisateurPublication && $utilisateurPublication->getId() === $utilisateurConnecte->getId()) {
            return;
        }

        throw $this->createAccessDeniedException('Tu ne peux pas supprimer ce commentaire.');
    }

    /**
     * Rôle : Obtenir l'utilisateur connecté avec son type métier.
     * Paramètres : Aucun.
     * Retour : Le membre connecté.
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
