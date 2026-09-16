<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Tests d'intégration des contraintes Symfony Validator.
 * Rôle : Vérifier que les données invalides sont refusées avant leur enregistrement.
 * Tâches : Valider un utilisateur incomplet et une publication vide puis rechercher les violations attendues.
 * Liens avec les autres fichiers : Utilise Validator, Utilisateur, Publication et le lanceur de tests.
 */

use App\Entity\Publication;
use App\Entity\Utilisateur;

$utilisateurInvalide = new Utilisateur();
$utilisateurInvalide->setPseudonyme('a');
$utilisateurInvalide->setAdresseEmail('adresse-invalide');
$violationsUtilisateur = $validateur->validate($utilisateurInvalide);

$proprietesInvalides = [];

foreach ($violationsUtilisateur as $violationUtilisateur) {
    $proprietesInvalides[] = $violationUtilisateur->getPropertyPath();
}

$lanceurTests->verifierVrai(
    in_array('pseudonyme', $proprietesInvalides, true),
    'Un pseudonyme trop court est refusé'
);
$lanceurTests->verifierVrai(
    in_array('adresseEmail', $proprietesInvalides, true),
    'Une adresse e-mail invalide est refusée'
);

$publicationInvalide = new Publication();
$violationsPublication = $validateur->validate($publicationInvalide);
$messagesPublication = [];

foreach ($violationsPublication as $violationPublication) {
    $messagesPublication[] = $violationPublication->getMessage();
}

$lanceurTests->verifierVrai(
    in_array('Une publication doit contenir un texte ou une image.', $messagesPublication, true),
    'Une publication sans texte ni image est refusée par Validator'
);
