<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Contrôle de l'environnement Doctrine réservé aux tests.
 * Rôle : Garantir que les tests d'intégration utilisent SQLite en mémoire et que le schéma complet est disponible.
 * Tâches : Examiner la connexion Doctrine et compter les métadonnées des entités.
 * Liens avec les autres fichiers : Utilise l'EntityManager préparé par tests/Lancer.php et les entités de src/Entity.
 */

$parametresConnexion = $entityManager->getConnection()->getParams();
$piloteConnexion = $parametresConnexion['driver'] ?? null;
$memoireUniquement = $parametresConnexion['memory'] ?? false;

$lanceurTests->verifierEgalite(
    'pdo_sqlite',
    $piloteConnexion,
    'La campagne utilise le pilote SQLite isolé'
);
$lanceurTests->verifierVrai(
    true === $memoireUniquement,
    'La base de test existe uniquement en mémoire'
);
$lanceurTests->verifierVrai(
    count($entityManager->getMetadataFactory()->getAllMetadata()) >= 5,
    'Doctrine charge toutes les entités du projet'
);
