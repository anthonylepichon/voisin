<?php

/*
 * Description générale : Distribution centralisée des fichiers téléversés.
 * Rôle : Servir une photo de profil ou une image de publication selon les autorisations applicables.
 * Tâches : Retrouver le fichier, préserver la compatibilité historique, contrôler la visibilité et retourner l'image.
 * Liens avec les autres fichiers : Utilise UploadFichierRepository, PublicationRepository, FileUploadService, Publication et Utilisateur.
 */

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\UploadFichier;
use App\Entity\Utilisateur;
use App\Repository\PublicationRepository;
use App\Repository\UploadFichierRepository;
use App\Service\FileUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UploadFichierController extends AbstractController
{
    /**
     * Rôle : Distribuer un fichier existant en appliquant les autorisations de son type.
     * Paramètres : Le type, le nom, les dépôts Doctrine et le service central des fichiers.
     * Retour : Le fichier image ou une erreur 404.
     */
    #[Route(
        '/media/{type}/{nom}',
        name: 'app_upload_fichier',
        methods: ['GET'],
        requirements: [
            'type' => 'profil|publication',
            'nom' => '[A-Za-z0-9._-]+',
        ]
    )]
    public function afficher(
        string $type,
        string $nom,
        UploadFichierRepository $uploadFichierRepository,
        PublicationRepository $publicationRepository,
        FileUploadService $fileUploadService
    ): Response {
        $uploadFichier = $uploadFichierRepository->trouverParTypeEtNom($type, $nom);

        if (UploadFichier::TYPE_PUBLICATION === $type) {
            $publication = $this->obtenirPublication($nom, $uploadFichier, $publicationRepository);

            if (null === $publication || !$this->peutVoirPublication($publication)) {
                throw $this->createNotFoundException();
            }
        }

        $cheminEnregistre = null;

        if (null !== $uploadFichier) {
            $cheminEnregistre = $uploadFichier->getChemin();
        }

        $chemin = $fileUploadService->obtenirCheminFichier($type, $nom, $cheminEnregistre);

        if (!is_file($chemin)) {
            throw $this->createNotFoundException();
        }

        return new BinaryFileResponse($chemin);
    }

    /**
     * Rôle : Retrouver la publication d'une image centralisée ou d'un ancien fichier.
     * Paramètres : Le nom, les métadonnées éventuelles et le dépôt des publications.
     * Retour : La publication associée ou null lorsqu'elle est absente.
     */
    private function obtenirPublication(
        string $nom,
        ?UploadFichier $uploadFichier,
        PublicationRepository $publicationRepository
    ): ?Publication {
        if (null !== $uploadFichier) {
            return $uploadFichier->getPublication();
        }

        return $publicationRepository->findOneBy(['nomImage' => $nom]);
    }

    /**
     * Rôle : Vérifier si le visiteur peut consulter une publication donnée.
     * Paramètres : La publication dont la visibilité doit être contrôlée.
     * Retour : Vrai lorsque la publication est visible.
     */
    private function peutVoirPublication(Publication $publication): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if (Publication::VISIBILITE_PUBLIQUE === $publication->getVisibilite()) {
            return true;
        }

        $utilisateurConnecte = $this->getUser();
        $utilisateurPublication = $publication->getUtilisateur();

        if (!$utilisateurConnecte instanceof Utilisateur || null === $utilisateurPublication) {
            return false;
        }

        if ($utilisateurConnecte->getId() === $utilisateurPublication->getId()) {
            return true;
        }

        return $utilisateurConnecte->getAmis()->contains($utilisateurPublication)
            || $utilisateurPublication->getAmis()->contains($utilisateurConnecte);
    }
}
