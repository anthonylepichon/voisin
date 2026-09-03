<?php

/*
 * Description générale : Formulaire Symfony de création d'un compte utilisateur.
 * Rôle : Collecter et valider les données nécessaires à l'inscription.
 * Tâches : Contrôler le pseudonyme, l'e-mail, le mot de passe et la photo de profil obligatoire.
 * Liens avec les autres fichiers : Utilisé par RegistrationController et associé à l'entité Utilisateur.
 */

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    /**
     * Rôle : Construire les champs et contraintes du formulaire d'inscription.
     * Paramètres : Le constructeur du formulaire et ses options.
     * Retour : Aucun.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudonyme', null, [
                'label' => 'Pseudonyme',
            ])
            ->add('adresseEmail', null, [
                'label' => 'Adresse e-mail',
            ])
            ->add('photoProfil', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(
                        message: 'La photo de profil est obligatoire.',
                    ),
                    new File(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'La photo doit être au format JPEG, PNG ou WebP.',
                    ),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'label' => 'Mot de passe',
                'mapped' => false,
                'invalid_message' => 'Les deux mots de passe doivent être identiques.',
                'first_options' => ['attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['attr' => ['autocomplete' => 'new-password']],
                'constraints' => [
                    new NotBlank(
                        message: 'Le mot de passe est obligatoire.',
                    ),
                    new Length(
                        min: 8,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                        max: 4096,
                    ),
                ],
            ])
        ;
    }

    /**
     * Rôle : Définir l'entité alimentée par le formulaire.
     * Paramètres : Le résolveur des options Symfony.
     * Retour : Aucun.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
