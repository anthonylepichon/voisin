<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Point d'entrée de la campagne de tests automatisés de Voisin.
 * Rôle : Exécuter les tests unitaires puis les tests d'intégration dans un environnement isolé.
 * Tâches : Charger Symfony, créer une base SQLite en mémoire, exécuter chaque fichier et retourner un code d'échec à la CI.
 * Liens avec les autres fichiers : Utilise tests/LanceurTest.php, les tests unitaires, les tests d'intégration, App\Kernel et Doctrine ORM.
 */

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Validator\Validator\ValidatorInterface;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/LanceurTest.php';

$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = '0';
$_ENV['APP_DEBUG'] = '0';
$_SERVER['APP_SECRET'] = 'test-only-secret-without-production-value';
$_ENV['APP_SECRET'] = 'test-only-secret-without-production-value';
$_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';
$_ENV['DATABASE_URL'] = 'sqlite:///:memory:';
$_SERVER['MESSENGER_TRANSPORT_DSN'] = 'in-memory://';
$_ENV['MESSENGER_TRANSPORT_DSN'] = 'in-memory://';

$lanceurTests = new LanceurTest();

echo '========================================' . PHP_EOL;
echo '          TESTS AUTOMATISÉS VOISIN' . PHP_EOL;
echo '========================================' . PHP_EOL;

$testsUnitaires = [
    'Rôles utilisateur' => __DIR__ . '/unitaire/TestUtilisateur.php',
    'Publication et relations' => __DIR__ . '/unitaire/TestPublication.php',
    'Accès aux publications' => __DIR__ . '/unitaire/TestPublicationAccessService.php',
];

foreach ($testsUnitaires as $nomTest => $cheminTest) {
    $lanceurTests->commencerItem($nomTest);

    try {
        require $cheminTest;
    } catch (Throwable $exception) {
        $lanceurTests->enregistrerException($exception);
    }
}

$kernel = new Kernel('test', false);
$kernel->boot();
$conteneurTest = $kernel->getContainer()->get('test.service_container');

/** @var EntityManagerInterface $entityManager */
$entityManager = $conteneurTest->get('doctrine')->getManager();
$outilSchema = new SchemaTool($entityManager);
$metadonnees = $entityManager->getMetadataFactory()->getAllMetadata();
$outilSchema->createSchema($metadonnees);

/** @var ValidatorInterface $validateur */
$validateur = $conteneurTest->get('validator');

$testsIntegration = [
    'Base Doctrine isolée' => __DIR__ . '/integration/TestDatabase.php',
    'Repositories Doctrine' => __DIR__ . '/integration/TestRepositories.php',
    'Validation des données' => __DIR__ . '/integration/TestValidation.php',
];

foreach ($testsIntegration as $nomTest => $cheminTest) {
    $lanceurTests->commencerItem($nomTest);

    try {
        require $cheminTest;
    } catch (Throwable $exception) {
        $lanceurTests->enregistrerException($exception);
    }
}

$kernel->shutdown();
$lanceurTests->afficherResume();

exit($lanceurTests->obtenirCodeSortie());
