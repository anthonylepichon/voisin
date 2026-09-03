<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Formulaire de modification du profil membre.
 * Rôle : Limiter les informations modifiables au pseudonyme, à la biographie et à la photo.
 * Tâches : Normaliser les textes, valider les champs autorisés et contrôler une éventuelle nouvelle image.
 * Liens avec les autres fichiers : Utilisé par ProfileController avec l'entité Utilisateur.
 */

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfileFormType extends AbstractType
{
    /**
     * Rôle : Construire les champs que le membre peut modifier.
     * Paramètres : Le constructeur du formulaire et ses options.
     * Retour : Aucun.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudonyme', TextType::class, [
                'label' => 'Pseudonyme',
                'trim' => true,
            ])
            ->add('biographie', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'trim' => true,
                'empty_data' => null,
            ])
            ->add('photoProfil', FileType::class, [
                'label' => 'Nouvelle photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'La photo doit être au format JPEG, PNG ou WebP.'
                    ),
                ],
            ]);
    }

    /**
     * Rôle : Associer le formulaire à l'entité utilisateur.
     * Paramètres : Le résolveur d'options Symfony.
     * Retour : Aucun.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Utilisateur::class]);
    }
}
