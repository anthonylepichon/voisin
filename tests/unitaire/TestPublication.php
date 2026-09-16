<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Tests unitaires du contenu et des relations d'une publication.
 * Rôle : Vérifier les règles simples sans démarrer Symfony ni utiliser une base de données.
 * Tâches : Contrôler le contenu obligatoire, la synchronisation du propriétaire et la mention J'aime.
 * Liens avec les autres fichiers : Utilise Publication, Utilisateur, UploadFichier et tests/LanceurTest.php.
 */

use App\Entity\Publication;
use App\Entity\UploadFichier;
use App\Entity\Utilisateur;

$publicationVide = new Publication();
$lanceurTests->verifierFaux(
    $publicationVide->aUnContenuOuUneImage(),
    'Une publication vide est refusée'
);

$publicationTexte = new Publication();
$publicationTexte->setContenu('Une information utile pour le voisinage.');
$lanceurTests->verifierVrai(
    $publicationTexte->aUnContenuOuUneImage(),
    'Une publication contenant du texte est acceptée'
);

$publicationImage = new Publication();
$image = new UploadFichier();
$image->setType(UploadFichier::TYPE_PUBLICATION);
$image->setNom('image-test.webp');
$image->setChemin('publications');
$publicationImage->setUploadFichier($image);
$lanceurTests->verifierVrai(
    $publicationImage->aUnContenuOuUneImage(),
    'Une publication contenant une image est acceptée'
);

$proprietaire = new Utilisateur();
$publicationTexte->setUtilisateur($proprietaire);
$lanceurTests->verifierVrai(
    $proprietaire->getPublications()->contains($publicationTexte),
    'L affectation du propriétaire synchronise sa collection de publications'
);

$membre = new Utilisateur();
$publicationTexte->ajouterUtilisateurAimant($membre);
$lanceurTests->verifierVrai(
    $membre->getPublicationsAimees()->contains($publicationTexte),
    'L ajout d une mention J aime synchronise les deux entités'
);
