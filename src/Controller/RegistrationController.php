<?php

/*
 * Description générale : Contrôleur de création des comptes utilisateur.
 * Rôle : Traiter l'inscription, déléguer la photo de profil et enregistrer le mot de passe haché.
 * Tâches : Valider le formulaire, téléverser la photo avec compensation, persister l'utilisateur et ouvrir sa session.
 * Liens avec les autres fichiers : Utilise RegistrationFormType, Utilisateur, FileUploadService, le hasher Symfony et Security.
 */

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    /**
     * Rôle : Afficher et traiter le formulaire d'inscription.
     * Paramètres : La requête HTTP, le hasher, Doctrine, le service des fichiers et Security.
     * Retour : La réponse HTTP du formulaire ou la redirection après inscription.
     */
    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, FileUploadService $fileUploadService, Security $security): Response
    {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            /** @var UploadedFile $photoProfil */
            $photoProfil = $form->get('photoProfil')->getData();
            $upload = $fileUploadService->televerserPhotoProfil($photoProfil, $user);

            if (null === $upload) {
                $this->addFlash('danger', 'La photo de profil n’a pas pu être enregistrée. Réessaie plus tard.');

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            try {
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (\Throwable $exception) {
                $fileUploadService->compenserTeleversement($upload);

                throw $exception;
            }

            $this->addFlash('success', 'Ton compte a été créé avec succès.');

            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
