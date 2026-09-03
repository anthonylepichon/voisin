<?php

/*
 * Description générale : Distribution contrôlée des images de publications.
 * Rôle : Servir une image seulement lorsqu'elle est visible par le visiteur courant.
 * Tâches : Retrouver la publication, appliquer sa visibilité et répondre avec le fichier autorisé.
 * Liens avec les autres fichiers : Utilise PublicationRepository, Utilisateur et uploads/publications.
 */

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use App\Repository\PublicationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublicationImageController extends AbstractController
{
    /**
     * Rôle : Distribuer une image de publication visible par le visiteur.
     * Paramètres : Le nom du fichier et le dépôt des publications.
     * Retour : Le fichier image ou une erreur 404.
     */
    #[Route('/media/publications/{nom}', name: 'app_publication_image', methods: ['GET'], requirements: ['nom' => '[A-Za-z0-9._-]+'])]
    public function show(string $nom, PublicationRepository $publicationRepository): Response
    {
        $publication = $publicationRepository->findOneBy(['nomImage' => $nom]);

        if (null === $publication || !$this->peutVoirPublication($publication)) {
            throw $this->createNotFoundException();
        }

        $cheminImage = $this->getParameter('kernel.project_dir').'/uploads/publications/'.$nom;

        if (!is_file($cheminImage)) {
            throw $this->createNotFoundException();
        }

        return new BinaryFileResponse($cheminImage);
    }

    /**
     * Rôle : Vérifier si le visiteur peut consulter une publication donnée.
     * Paramètres : La publication dont la visibilité doit être contrôlée.
     * Retour : Vrai lorsque la publication est visible.
     */
    private function peutVoirPublication(Publication $publication): bool
    {
        if (Publication::VISIBILITE_PUBLIQUE === $publication->getVisibilite()) {
            return true;
        }

        $utilisateur = $this->getUser();
        $auteur = $publication->getAuteur();

        if (!$utilisateur instanceof Utilisateur || null === $auteur) {
            return false;
        }

        if ($utilisateur->getId() === $auteur->getId()) {
            return true;
        }

        return $utilisateur->getAmis()->contains($auteur)
            || $auteur->getAmis()->contains($utilisateur);
    }
}
