<?php

/*
 * Description générale : Formulaire Symfony de saisie d'un commentaire.
 * Rôle : Autoriser uniquement la saisie du contenu textuel d'un commentaire.
 * Tâches : Normaliser le contenu, afficher un champ limité à 500 caractères et conserver la protection CSRF du formulaire.
 * Liens avec les autres fichiers : Utilise Commentaire et est traité par CommentController dans la page des commentaires.
 */

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentFormType extends AbstractType
{
    /**
     * Rôle : Ajouter au formulaire le seul contenu modifiable par le membre.
     * Paramètres : Le constructeur du formulaire et ses options.
     * Retour : Aucun.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('contenu', TextareaType::class, [
            'label' => false,
            'trim' => true,
            'attr' => [
                'maxlength' => 500,
                'placeholder' => 'Écrire un commentaire…',
                'rows' => 2,
            ],
        ]);
    }

    /**
     * Rôle : Associer le formulaire à l'entité Commentaire.
     * Paramètres : Le résolveur des options Symfony.
     * Retour : Aucun.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}
