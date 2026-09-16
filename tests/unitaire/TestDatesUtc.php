<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Tests unitaires de la normalisation des dates internes en UTC.
 * Rôle : Garantir que la configuration Europe/Paris du serveur ne modifie pas les dates enregistrées.
 * Tâches : Simuler un serveur français, contrôler les constructeurs et vérifier la normalisation des setters.
 * Liens avec les autres fichiers : Utilise les entités datées de src/Entity et tests/LanceurTest.php.
 */

use App\Entity\Commentaire;
use App\Entity\DemandeAmitie;
use App\Entity\Publication;
use App\Entity\Utilisateur;

$fuseauAvantTest = date_default_timezone_get();
date_default_timezone_set('Europe/Paris');

$utilisateurDateTest = new Utilisateur();
$publicationDateTest = new Publication();
$commentaireDateTest = new Commentaire();
$demandeDateTest = new DemandeAmitie();

$lanceurTests->verifierEgalite(
    'UTC',
    $utilisateurDateTest->getDateInscription()->getTimezone()->getName(),
    'La date d inscription est créée en UTC sur un serveur Europe/Paris'
);
$lanceurTests->verifierEgalite(
    'UTC',
    $publicationDateTest->getDateCreation()->getTimezone()->getName(),
    'La date d une publication est créée en UTC sur un serveur Europe/Paris'
);
$lanceurTests->verifierEgalite(
    'UTC',
    $commentaireDateTest->getDateCreation()->getTimezone()->getName(),
    'La date d un commentaire est créée en UTC sur un serveur Europe/Paris'
);
$lanceurTests->verifierEgalite(
    'UTC',
    $demandeDateTest->getDateCreation()->getTimezone()->getName(),
    'La date d une demande d amitié est créée en UTC sur un serveur Europe/Paris'
);

$dateParis = new DateTimeImmutable('2026-07-15 14:00:00', new DateTimeZone('Europe/Paris'));
$publicationDateTest->setDateCreation($dateParis);

$lanceurTests->verifierEgalite(
    '2026-07-15 12:00:00 UTC',
    $publicationDateTest->getDateCreation()->format('Y-m-d H:i:s T'),
    'Une date d été Europe/Paris est normalisée en UTC sans changer l instant'
);

$utilisateurDateTest->setDateDerniereActivite($dateParis);
$lanceurTests->verifierEgalite(
    'UTC',
    $utilisateurDateTest->getDateDerniereActivite()?->getTimezone()->getName(),
    'La dernière activité reçue par un setter est normalisée en UTC'
);

date_default_timezone_set($fuseauAvantTest);
