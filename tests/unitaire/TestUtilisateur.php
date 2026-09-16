<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Tests unitaires des rôles de l'utilisateur.
 * Rôle : Garantir le rôle membre obligatoire et la conservation du rôle administrateur.
 * Tâches : Contrôler ROLE_USER, ROLE_ADMIN et l'absence de doublon dans la liste des rôles.
 * Liens avec les autres fichiers : Utilise App\Entity\Utilisateur et tests/LanceurTest.php.
 */

use App\Entity\Utilisateur;

$utilisateurSimple = new Utilisateur();
$lanceurTests->verifierVrai(
    in_array('ROLE_USER', $utilisateurSimple->getRoles(), true),
    'Un nouvel utilisateur possède toujours ROLE_USER'
);

$administrateur = new Utilisateur();
$administrateur->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
$rolesAdministrateur = $administrateur->getRoles();

$lanceurTests->verifierVrai(
    in_array('ROLE_ADMIN', $rolesAdministrateur, true),
    'Le rôle ROLE_ADMIN est conservé'
);
$lanceurTests->verifierEgalite(
    1,
    count(array_keys($rolesAdministrateur, 'ROLE_USER', true)),
    'ROLE_USER ne doit pas être présent en double'
);
