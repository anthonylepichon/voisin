<?php

/* Origine du code : Structure générée par Symfony puis modifiée par le développeur. */

/*
 * Description générale : Point d'entrée HTTP de l'application Voisin.
 * Rôle : Démarrer Symfony avec un fuseau interne UTC indépendant de la configuration du serveur.
 * Tâches : Imposer UTC pour les dates internes puis créer le noyau Symfony.
 * Liens avec les autres fichiers : Charge vendor/autoload_runtime.php et instancie App\Kernel.
 */

use App\Kernel;

date_default_timezone_set('UTC');

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
