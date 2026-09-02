<?php

/*
 * Description générale : Distribution sécurisée des photos de profil.
 * Rôle : Servir une image demandée par un membre authentifié.
 * Tâches : Contrôler le nom demandé et répondre avec le fichier autorisé.
 * Liens avec les autres fichiers : Utilise uploads/profils et les pages de profil.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfilePhotoController extends AbstractController
{
    /**
     * Rôle : Servir une photo de profil autorisée.
     * Paramètres : Le nom du fichier stocké.
     * Retour : Le fichier image ou une erreur 404.
     */
    #[Route('/media/profils/{nom}', name: 'app_profile_photo', methods: ['GET'], requirements: ['nom' => '[A-Za-z0-9._-]+'])]
    public function show(string $nom): Response
    {
        $cheminPhoto = $this->getParameter('kernel.project_dir').'/uploads/profils/'.$nom;

        if (!is_file($cheminPhoto)) {
            throw $this->createNotFoundException();
        }

        return new BinaryFileResponse($cheminPhoto);
    }
}
