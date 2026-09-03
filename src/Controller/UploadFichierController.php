<?php

/*
 * Description générale : Distribution centralisée des fichiers téléversés.
 * Rôle : Servir une photo de profil ou une image de publication selon les autorisations applicables.
 * Tâches : Retrouver le fichier, identifier sa publication, consulter les règles d'accès centralisées et résoudre son chemin depuis son type.
 * Liens avec les autres fichiers : Utilise UploadFichierRepository, PublicationRepository, PublicationAccessService, FileUploadService et Publication.
 */

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\UploadFichier;
use App\Entity\Utilisateur;
use App\Repository\PublicationRepository;
use App\Repository\UploadFichierRepository;
use App\Service\FileUploadService;
use App\Service\PublicationAccessService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UploadFichierController extends AbstractController
{
    /**
     * Rôle : Distribuer un fichier existant en appliquant les autorisations de son type.
     * Paramètres : Le type, le nom, les dépôts Doctrine et les services d'accès et de fichiers.
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
        FileUploadService $fileUploadService,
        PublicationAccessService $publicationAccessService
    ): Response {
        $uploadFichier = $uploadFichierRepository->trouverParTypeEtNom($type, $nom);

        if (UploadFichier::TYPE_PUBLICATION === $type) {
            $publication = $this->obtenirPublication($uploadFichier, $publicationRepository);

            $utilisateur = $this->getUser();

            if (!$utilisateur instanceof Utilisateur) {
                $utilisateur = null;
            }

            if (null === $publication || !$publicationAccessService->peutVoir($publication, $utilisateur)) {
                throw $this->createNotFoundException();
            }
        }

        $chemin = $fileUploadService->obtenirCheminFichier($type, $nom);

        if (!is_file($chemin)) {
            throw $this->createNotFoundException();
        }

        return new BinaryFileResponse($chemin);
    }

    /**
     * Rôle : Retrouver la publication qui référence une image centralisée.
     * Paramètres : Les métadonnées éventuelles et le dépôt des publications.
     * Retour : La publication associée ou null lorsqu'elle est absente.
     */
    private function obtenirPublication(
        ?UploadFichier $uploadFichier,
        PublicationRepository $publicationRepository
    ): ?Publication {
        if (null === $uploadFichier) {
            return null;
        }

        return $publicationRepository->findOneBy(['uploadFichier' => $uploadFichier]);
    }

}
