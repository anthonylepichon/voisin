<?php

/*
 * Description générale : Contrôleur de création des comptes utilisateur.
 * Rôle : Traiter l'inscription, sécuriser la photo de profil et enregistrer le mot de passe haché.
 * Tâches : Valider le formulaire, déplacer l'image contrôlée, persister l'utilisateur et rediriger l'utilisateur connecté.
 * Liens avec les autres fichiers : Utilise RegistrationFormType, Utilisateur, le hasher Symfony et le provider Security.
 */

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class RegistrationController extends AbstractController
{
    /**
     * Rôle : Afficher et traiter le formulaire d'inscription.
     * Paramètres : La requête HTTP, le hasher, l'EntityManager, le slugger et le service Security.
     * Retour : La réponse HTTP du formulaire ou la redirection après inscription.
     */
    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, SluggerInterface $slugger, Security $security): Response
    {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            /** @var UploadedFile $photoProfil */
            $photoProfil = $form->get('photoProfil')->getData();
            $extension = $photoProfil->guessExtension();

            if (null === $extension) {
                $form->get('photoProfil')->addError(new FormError('Le format de la photo est invalide.'));

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            $nomFichier = sprintf('profil-%s.%s', $slugger->slug((string) $user->getPseudonyme())->lower(), $extension);
            $nomFichier = sprintf('%s-%s', uniqid(), $nomFichier);

            try {
                $photoProfil->move($this->getParameter('kernel.project_dir').'/uploads/profils', $nomFichier);
            } catch (FileException) {
                $this->addFlash('danger', 'La photo de profil n’a pas pu être enregistrée. Réessaie plus tard.');

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            $user->setNomPhotoProfil($nomFichier);
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Ton compte a été créé avec succès.');

            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
