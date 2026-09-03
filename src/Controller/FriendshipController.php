<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Contrôleur des relations d'amitié entre les membres.
 * Rôle : Envoyer, consulter, accepter ou refuser les demandes et afficher les amis.
 * Tâches : Appliquer les règles de doublon, de destinataire, de CSRF et de relation normalisée.
 * Liens avec les autres fichiers : Utilise DemandeAmitie, les repositories sociaux, UserActivityService et les vues friendship.
 */

namespace App\Controller;

use App\Entity\DemandeAmitie;
use App\Entity\Utilisateur;
use App\Repository\DemandeAmitieRepository;
use App\Repository\UtilisateurRepository;
use App\Service\UserActivityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FriendshipController extends AbstractController
{
    /**
     * Rôle : Envoyer une demande d'amitié à un autre membre.
     * Paramètres : L'identifiant du destinataire, la requête, les repositories, Doctrine et le service d'activité.
     * Retour : Une redirection vers le profil du destinataire.
     */
    #[Route('/demandes-amitie/{id}/envoyer', name: 'app_friendship_send', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function send(
        int $id,
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        DemandeAmitieRepository $demandeAmitieRepository,
        EntityManagerInterface $entityManager,
        UserActivityService $userActivityService,
    ): Response {
        $utilisateurConnecte = $this->getUtilisateurConnecte();
        $userActivityService->enregistrerActivite($utilisateurConnecte, new \DateTimeImmutable());
        $destinataire = $utilisateurRepository->find($id);

        if (null === $destinataire) {
            throw $this->createNotFoundException('Profil introuvable.');
        }

        $jeton = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('envoyer-demande-amitie-'.$destinataire->getId(), $jeton)) {
            throw $this->createAccessDeniedException('La demande d’amitié est invalide.');
        }

        if ($utilisateurConnecte->getId() === $destinataire->getId()) {
            $this->addFlash('warning', 'Tu ne peux pas t’envoyer une demande d’amitié.');

            return $this->redirigerVersProfil($destinataire);
        }

        if ($utilisateurRepository->sontAmis($utilisateurConnecte, $destinataire)) {
            $this->addFlash('info', 'Vous êtes déjà amis.');

            return $this->redirigerVersProfil($destinataire);
        }

        $demandeExistante = $demandeAmitieRepository->trouverEntre($utilisateurConnecte, $destinataire);

        if (null !== $demandeExistante) {
            $this->addFlash('info', 'Une demande d’amitié existe déjà entre vous.');

            return $this->redirigerVersProfil($destinataire);
        }

        $demande = new DemandeAmitie();
        $demande->setExpediteur($utilisateurConnecte);
        $demande->setDestinataire($destinataire);

        $entityManager->persist($demande);
        $entityManager->flush();

        $this->addFlash('success', 'La demande d’amitié a été envoyée.');

        return $this->redirigerVersProfil($destinataire);
    }

    /**
     * Rôle : Afficher les demandes d'amitié reçues par le membre connecté.
     * Paramètres : Le repository des demandes et le service d'activité.
     * Retour : La réponse Twig de la liste des demandes.
     */
    #[Route('/demandes-amitie', name: 'app_friendship_requests', methods: ['GET'])]
    public function requests(
        DemandeAmitieRepository $demandeAmitieRepository,
        UserActivityService $userActivityService,
    ): Response {
        $utilisateurConnecte = $this->getUtilisateurConnecte();
        $userActivityService->enregistrerActivite($utilisateurConnecte, new \DateTimeImmutable());
        $demandes = $demandeAmitieRepository->trouverRecues($utilisateurConnecte);

        return $this->render('friendship/requests.html.twig', [
            'demandes' => $demandes,
            'nombreDemandesAmitieEnAttente' => count($demandes),
        ]);
    }

    /**
     * Rôle : Accepter une demande reçue et créer une relation d'amitié normalisée.
     * Paramètres : L'identifiant de la demande, la requête, les repositories, Doctrine et le service d'activité.
     * Retour : Une redirection vers les demandes reçues.
     */
    #[Route('/demandes-amitie/{id}/accepter', name: 'app_friendship_accept', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function accept(
        int $id,
        Request $request,
        DemandeAmitieRepository $demandeAmitieRepository,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        UserActivityService $userActivityService,
    ): Response {
        $utilisateurConnecte = $this->getUtilisateurConnecte();
        $userActivityService->enregistrerActivite($utilisateurConnecte, new \DateTimeImmutable());
        $demande = $this->trouverDemande($id, $demandeAmitieRepository);
        $this->refuserSiNonDestinataire($demande, $utilisateurConnecte);
        $this->verifierJeton($request, 'accepter-demande-amitie-'.$demande->getId());
        $expediteur = $demande->getExpediteur();

        if (null === $expediteur) {
            throw $this->createNotFoundException('Expéditeur introuvable.');
        }

        if (!$utilisateurRepository->sontAmis($utilisateurConnecte, $expediteur)) {
            $this->ajouterRelationNormalisee($utilisateurConnecte, $expediteur);
        }

        $entityManager->remove($demande);
        $entityManager->flush();

        $this->addFlash('success', 'La demande d’amitié a été acceptée.');

        return $this->redirectToRoute('app_friendship_requests');
    }

    /**
     * Rôle : Refuser et supprimer une demande reçue sans créer de relation.
     * Paramètres : L'identifiant de la demande, la requête, le repository, Doctrine et le service d'activité.
     * Retour : Une redirection vers les demandes reçues.
     */
    #[Route('/demandes-amitie/{id}/refuser', name: 'app_friendship_refuse', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function refuse(
        int $id,
        Request $request,
        DemandeAmitieRepository $demandeAmitieRepository,
        EntityManagerInterface $entityManager,
        UserActivityService $userActivityService,
    ): Response {
        $utilisateurConnecte = $this->getUtilisateurConnecte();
        $userActivityService->enregistrerActivite($utilisateurConnecte, new \DateTimeImmutable());
        $demande = $this->trouverDemande($id, $demandeAmitieRepository);
        $this->refuserSiNonDestinataire($demande, $utilisateurConnecte);
        $this->verifierJeton($request, 'refuser-demande-amitie-'.$demande->getId());

        $entityManager->remove($demande);
        $entityManager->flush();

        $this->addFlash('success', 'La demande d’amitié a été refusée.');

        return $this->redirectToRoute('app_friendship_requests');
    }

    /**
     * Rôle : Afficher les amis du membre connecté et leur état de présence.
     * Paramètres : Les repositories sociaux et le service d'activité.
     * Retour : La réponse Twig de la liste des amis.
     */
    #[Route('/amis', name: 'app_friendship_friends', methods: ['GET'])]
    public function friends(
        UtilisateurRepository $utilisateurRepository,
        DemandeAmitieRepository $demandeAmitieRepository,
        UserActivityService $userActivityService,
    ): Response {
        $utilisateurConnecte = $this->getUtilisateurConnecte();
        $dateCourante = new \DateTimeImmutable();
        $userActivityService->enregistrerActivite($utilisateurConnecte, $dateCourante);
        $amis = $utilisateurRepository->trouverAmis($utilisateurConnecte);
        $statutsEnLigne = [];

        foreach ($amis as $ami) {
            $identifiantAmi = $ami->getId();

            if (null !== $identifiantAmi) {
                $statutsEnLigne[$identifiantAmi] = $userActivityService->estEnLigne($ami, $dateCourante);
            }
        }

        return $this->render('friendship/friends.html.twig', [
            'amis' => $amis,
            'statutsEnLigne' => $statutsEnLigne,
            'nombreDemandesAmitieEnAttente' => $demandeAmitieRepository->compterRecues($utilisateurConnecte),
        ]);
    }

    /**
     * Rôle : Retrouver une demande d'amitié à partir de son identifiant.
     * Paramètres : L'identifiant recherché et le repository des demandes.
     * Retour : La demande trouvée ou une erreur 404.
     */
    private function trouverDemande(int $id, DemandeAmitieRepository $demandeAmitieRepository): DemandeAmitie
    {
        $demande = $demandeAmitieRepository->find($id);

        if (null === $demande) {
            throw $this->createNotFoundException('Demande d’amitié introuvable.');
        }

        return $demande;
    }

    /**
     * Rôle : Refuser le traitement d'une demande par un membre qui n'en est pas destinataire.
     * Paramètres : La demande et le membre connecté.
     * Retour : Aucun.
     */
    private function refuserSiNonDestinataire(DemandeAmitie $demande, Utilisateur $utilisateurConnecte): void
    {
        $destinataire = $demande->getDestinataire();

        if (null === $destinataire || $destinataire->getId() !== $utilisateurConnecte->getId()) {
            throw $this->createAccessDeniedException('Tu ne peux pas traiter cette demande d’amitié.');
        }
    }

    /**
     * Rôle : Vérifier le jeton CSRF d'une action sur une demande d'amitié.
     * Paramètres : La requête HTTP et l'identifiant du jeton attendu.
     * Retour : Aucun.
     */
    private function verifierJeton(Request $request, string $identifiantJeton): void
    {
        $jeton = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid($identifiantJeton, $jeton)) {
            throw $this->createAccessDeniedException('La demande est invalide.');
        }
    }

    /**
     * Rôle : Ajouter une relation d'amitié dans un ordre unique fondé sur les identifiants.
     * Paramètres : Les deux membres qui deviennent amis.
     * Retour : Aucun.
     */
    private function ajouterRelationNormalisee(Utilisateur $premier, Utilisateur $second): void
    {
        $identifiantPremier = $premier->getId();
        $identifiantSecond = $second->getId();

        if (null === $identifiantPremier || null === $identifiantSecond) {
            throw new \LogicException('Les membres doivent être enregistrés avant de devenir amis.');
        }

        if ($identifiantPremier < $identifiantSecond) {
            $premier->ajouterAmi($second);

            return;
        }

        $second->ajouterAmi($premier);
    }

    /**
     * Rôle : Rediriger vers le profil public d'un membre.
     * Paramètres : Le membre dont le profil doit être affiché.
     * Retour : Une redirection HTTP.
     */
    private function redirigerVersProfil(Utilisateur $utilisateur): Response
    {
        return $this->redirectToRoute('app_profile_show', [
            'pseudonyme' => $utilisateur->getPseudonyme(),
        ]);
    }

    /**
     * Rôle : Obtenir le membre connecté avec son type métier.
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
