<?php

/*
 * Description générale : Formulaire de création et de modification d'une publication.
 * Rôle : Décrire le contenu, l'image et la visibilité que le membre peut choisir.
 * Tâches : Valider l'image téléversée et conserver le choix de visibilité conforme au cahier des charges.
 * Liens avec les autres fichiers : Utilisé par FeedController et PublicationController avec l'entité Publication.
 */

namespace App\Form;

use App\Entity\Publication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PublicationFormType extends AbstractType
{
    /**
     * Rôle : Construire les champs de saisie d'une publication.
     * Paramètres : Le constructeur du formulaire et ses options.
     * Retour : Aucun.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $visibiliteEtendue = true;
        $libelleVideVisibilite = false;

        if ($options['creation_dans_fil']) {
            $visibiliteEtendue = false;
            $libelleVideVisibilite = 'Publication';
        }

        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'required' => false,
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'L’image doit être au format JPEG, PNG ou WebP.'
                    ),
                ],
            ])
            ->add('visibilite', ChoiceType::class, [
                'label' => 'Visibilité',
                'expanded' => $visibiliteEtendue,
                'placeholder' => $libelleVideVisibilite,
                'choices' => [
                    'Publique' => Publication::VISIBILITE_PUBLIQUE,
                    'Amis' => Publication::VISIBILITE_AMIS,
                ],
            ])
            ->addEventListener(FormEvents::SUBMIT, [$this, 'preparerValidation']);

        if (!$options['creation_dans_fil']) {
            $builder->add('supprimerImage', SubmitType::class, [
                'label' => 'Retirer l’image',
            ]);
        }
    }

    /**
     * Rôle : Signaler la présence temporaire d’une image avant la validation globale de la publication.
     * Paramètres : L’événement contenant la publication et les champs soumis.
     * Retour : Aucun.
     */
    public function preparerValidation(FormEvent $event): void
    {
        $publication = $event->getData();
        $form = $event->getForm();

        if (!$publication instanceof Publication) {
            return;
        }

        $image = $form->get('image')->getData();
        $supprimerImage = false;

        if ($form->has('supprimerImage')) {
            $supprimerImage = $form->get('supprimerImage')->isClicked();
        }

        $publication->setImageEnAttente($image instanceof UploadedFile);

        if ($supprimerImage) {
            $publication->setUploadFichier(null);
        }
    }

    /**
     * Rôle : Associer le formulaire à l'entité publication.
     * Paramètres : Le résolveur d'options Symfony.
     * Retour : Aucun.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Publication::class,
            'creation_dans_fil' => false,
        ]);
        $resolver->setAllowedTypes('creation_dans_fil', 'bool');
    }
}
