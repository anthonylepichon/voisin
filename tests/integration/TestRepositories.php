<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Tests d'intégration des repositories Doctrine principaux.
 * Rôle : Vérifier la persistance d'un membre et la lecture d'une publication publique.
 * Tâches : Créer des données temporaires, les relire par les repositories puis contrôler les résultats.
 * Liens avec les autres fichiers : Utilise UtilisateurRepository, PublicationRepository, les entités et la base en mémoire du lanceur.
 */

use App\Entity\Publication;
use App\Entity\UploadFichier;
use App\Entity\Utilisateur;
use App\Repository\PublicationRepository;
use App\Repository\UtilisateurRepository;

$photoProfilTest = new UploadFichier();
$photoProfilTest->setType(UploadFichier::TYPE_PROFIL);
$photoProfilTest->setNom('profil-test.webp');
$photoProfilTest->setChemin('profils');

$utilisateurRepositoryTest = new Utilisateur();
$utilisateurRepositoryTest->setPseudonyme('membre_test');
$utilisateurRepositoryTest->setAdresseEmail('membre-test@example.test');
$utilisateurRepositoryTest->setPassword('hachage-inutilise-pendant-ce-test');
$utilisateurRepositoryTest->setUploadFichier($photoProfilTest);

$publicationRepositoryTest = new Publication();
$publicationRepositoryTest->setContenu('Publication publique de test');
$publicationRepositoryTest->setVisibilite(Publication::VISIBILITE_PUBLIQUE);
$publicationRepositoryTest->setUtilisateur($utilisateurRepositoryTest);

$entityManager->persist($utilisateurRepositoryTest);
$entityManager->persist($publicationRepositoryTest);
$entityManager->flush();

/** @var UtilisateurRepository $repositoryUtilisateur */
$repositoryUtilisateur = $entityManager->getRepository(Utilisateur::class);
$utilisateurRelu = $repositoryUtilisateur->trouverParIdentifiantConnexion('membre_test');

$lanceurTests->verifierEgalite(
    $utilisateurRepositoryTest->getId(),
    $utilisateurRelu?->getId(),
    'Le repository retrouve un membre par son pseudonyme'
);

$utilisateurReluParEmail = $repositoryUtilisateur->trouverParIdentifiantConnexion('membre-test@example.test');
$lanceurTests->verifierEgalite(
    $utilisateurRepositoryTest->getId(),
    $utilisateurReluParEmail?->getId(),
    'Le repository retrouve un membre par son adresse e-mail'
);

/** @var PublicationRepository $repositoryPublication */
$repositoryPublication = $entityManager->getRepository(Publication::class);
$publicationsPubliques = $repositoryPublication->trouverCinqPubliquesRecentes();

$lanceurTests->verifierEgalite(
    1,
    count($publicationsPubliques),
    'Le repository retourne la publication publique créée dans la base isolée'
);
$lanceurTests->verifierEgalite(
    $publicationRepositoryTest->getId(),
    $publicationsPubliques[0]->getId(),
    'La publication publique retournée est celle qui vient d être enregistrée'
);
