<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Contrôleur du fil d'actualité des membres.
 * Rôle : Afficher les publications autorisées et traiter leur création directement dans le fil.
 * Tâches : Créer une publication avec compensation du fichier, actualiser l'activité, filtrer et paginer les résultats.
 * Liens avec les autres fichiers : Utilise PublicationFormType, les repositories, FileUploadService, UserActivityService et templates/feed/index.html.twig.
 */

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Publication;
use App\Form\PublicationFormType;
use App\Repository\DemandeAmitieRepository;
use App\Repository\PublicationRepository;
use App\Repository\UtilisateurRepository;
use App\Service\UserActivityService;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FeedController extends AbstractController
{
    /**
     * Rôle : Afficher le fil filtré et traiter son formulaire de création rapide.
     * Paramètres : La requête HTTP avec la page demandée, Doctrine, les dépôts et les services du fil.
     * Retour : La réponse Twig du fil ou une redirection après publication.
     */
    #[Route('/fil-actualite', name: 'app_feed', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        PublicationRepository $publicationRepository,
        UtilisateurRepository $utilisateurRepository,
        DemandeAmitieRepository $demandeAmitieRepository,
        FileUploadService $fileUploadService,
        UserActivityService $userActivityService,
    ): Response
    {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $dateCourante = new \DateTimeImmutable();
        $userActivityService->enregistrerActivite($utilisateur, $dateCourante);

        $publication = new Publication();
        $publication->setUtilisateur($utilisateur);
        $publication->setVisibilite('');
        $formulairePublication = $this->createForm(PublicationFormType::class, $publication, [
            'creation_dans_fil' => true,
        ]);
        $formulairePublication->handleRequest($request);

        /** @var UploadedFile|null $image */
        $image = null;
        $nouveauFichier = null;

        if ($formulairePublication->isSubmitted()) {
            $image = $formulairePublication->get('image')->getData();
        }

        if ($formulairePublication->isSubmitted() && $formulairePublication->isValid()) {
            if (null !== $image) {
                $nouveauFichier = $fileUploadService->televerserImagePublication($image, $publication);

                if (null === $nouveauFichier) {
                    $publication->setUploadFichier(null);
                    $publication->setImageEnAttente(false);
                    $formulairePublication->get('image')->addError(new FormError('L’image n’a pas pu être enregistrée. Réessaie plus tard.'));
                }
            }

            if ($formulairePublication->isValid()) {
                try {
                    $entityManager->persist($publication);
                    $entityManager->flush();
                } catch (\Throwable $exception) {
                    if (null !== $nouveauFichier) {
                        $fileUploadService->compenserTeleversement($nouveauFichier);
                    }

                    throw $exception;
                }

                $this->addFlash('success', 'Ta publication a été créée.');

                return $this->redirectToRoute('app_feed');
            }
        }

        if ($formulairePublication->isSubmitted() && null !== $image) {
            $publication->setImageEnAttente(false);
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

        $page = $request->query->getInt('page', 1);

        if ($page < 1) {
            $page = 1;
        }

        $nombrePublications = $publicationRepository->compterPourFil($utilisateur, $filtre);
        $nombrePages = (int) ceil($nombrePublications / PublicationRepository::PUBLICATIONS_PAR_PAGE);

        if ($nombrePages < 1) {
            $nombrePages = 1;
        }

        if ($page > $nombrePages) {
            $page = $nombrePages;
        }

        $publications = $publicationRepository->trouverPourFil($utilisateur, $filtre, $page);
        $statistiquesPublications = $publicationRepository->trouverStatistiquesCartes($publications, $utilisateur);

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
            'publications' => $publications,
            'statistiquesPublications' => $statistiquesPublications,
            'filtreActif' => $filtre,
            'pageActuelle' => $page,
            'nombrePages' => $nombrePages,
            'amisEnLigne' => $amisEnLigne,
            'statutsEnLigne' => $statutsEnLigne,
            'nombreDemandesAmitieEnAttente' => $demandeAmitieRepository->compterRecues($utilisateur),
            'publicationForm' => $formulairePublication,
        ]);
    }
}
