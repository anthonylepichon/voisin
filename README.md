<!--
Origine du code : Code créé par le développeur.
Description générale : Présente l'application Voisin, sa construction avec Symfony, son installation, sa conception et son déploiement.
Rôle : Servir de point d'entrée technique et fonctionnel pour découvrir, installer et évaluer le projet.
Tâches : Décrire les fonctionnalités, les outils Symfony, les prérequis, la configuration locale, les livrables, les données de démonstration et le processus DevOps.
Liens avec les autres fichiers : S'appuie sur composer.json, package.json, migrations/, documents/conceptualisation/, documents/readme/ et .github/workflows/.
-->

# Voisin

Voisin est un réseau social de proximité développé avec Symfony. L'application permet aux habitants d'un même quartier de publier des informations, d'échanger avec leurs amis et de participer à une communauté locale.

Le projet est accessible en production à l'adresse suivante : [voisin.creativconsulting.fr](https://voisin.creativconsulting.fr/).

## Fonctionnalités

- inscription, connexion et déconnexion sécurisées ;
- consultation d'une page d'accueil publique et d'un fil d'actualité membre ;
- création, modification et suppression de publications ;
- ajout d'une image à une publication ;
- choix de la visibilité d'une publication : publique ou réservée aux amis ;
- ajout, modification et suppression de commentaires selon les autorisations ;
- ajout et retrait d'une mention « J'aime » ;
- envoi, acceptation et refus de demandes d'amitié ;
- consultation de la liste des amis et des profils membres ;
- modification du profil et de sa photo ;
- administration des membres et modération des publications avec `ROLE_ADMIN`.

## Technologies utilisées

| Domaine | Technologies |
|---|---|
| Back-end | PHP 8.2 ou supérieur, Symfony 7.4 |
| Données | Doctrine ORM, migrations Doctrine, MySQL |
| Sécurité | Symfony Security, CSRF, contrôle des rôles et des autorisations |
| Dates | Stockage et traitements en UTC, affichage Twig en `Europe/Paris` |
| Interface | Twig, HTML, Sass, CSS, JavaScript classique |
| Ressources | Symfony AssetMapper |
| Dépendances | Composer, npm |
| Intégration | Git, GitHub Actions |
| Production | Hébergement mutualisé OVH, déploiement par SSH |

## Organisation du projet

| Dossier | Rôle principal |
|---|---|
| `src/Controller/` | Contrôleurs et routes de l'application |
| `src/Entity/` | Entités Doctrine |
| `src/Repository/` | Accès aux données avec Doctrine ORM |
| `src/Form/` | Formulaires Symfony |
| `src/Security/` | Chargement des utilisateurs pour l'authentification |
| `src/Service/` | Services applicatifs partagés |
| `templates/` | Vues Twig |
| `assets/` | CSS compilé, JavaScript et images sources servis par AssetMapper |
| `resources/scss/` | Fichiers Sass sources |
| `migrations/` | Évolution versionnée du schéma de base de données |
| `tests/` | Tests PHP unitaires et d'intégration exécutés sans bibliothèque externe |
| `documents/` | Conception et ressources de démonstration |

Le dossier généré `public/assets/` n'est pas versionné. AssetMapper le construit pour la production à partir des fichiers suivis dans `assets/`.

## Construction de l'application avec Symfony

Le projet suit l'architecture MVC de Symfony. Les outils du framework ont servi à construire le socle technique, puis le code généré a été complété pour appliquer les règles métier de Voisin.

### Outils Symfony utilisés

| Outil | Rôle dans Symfony | Utilisation dans Voisin |
|---|---|---|
| Composer | Installe les bibliothèques PHP définies dans `composer.json` et verrouille leurs versions dans `composer.lock`. | Installation de Symfony 7.4, Doctrine, Twig, Security, Validator, AssetMapper et des autres composants du projet. |
| Symfony Flex | Automatise la configuration initiale des composants installés avec Composer grâce aux recettes Symfony. | Création et mise à jour des fichiers de configuration dans `config/` lors de l'installation des composants. |
| Symfony MakerBundle | Fournit des commandes `make:*` qui génèrent une structure de départ cohérente avec Symfony. | Génération initiale de plusieurs entités, contrôleurs, formulaires et éléments de sécurité, ensuite adaptés au fonctionnement de Voisin. |
| Console Symfony | Exécute les commandes du framework depuis `php bin/console`. | Création des migrations, contrôle du schéma, inspection des routes, nettoyage du cache et vérification de la configuration. |
| Doctrine ORM | Associe les objets PHP aux tables MySQL et permet de manipuler les données avec les entités et repositories. | Gestion des utilisateurs, publications, commentaires, mentions J'aime, amitiés, demandes d'amitié et fichiers téléversés. |
| Doctrine Migrations | Versionne les changements de structure de la base de données. | Création et évolution reproductible des tables, colonnes, index et contraintes de Voisin. |
| Routing et contrôleurs | Relient une URL à une méthode PHP et injectent les services nécessaires. | Routes déclarées par attributs dans `src/Controller/`, réponses Twig, redirections et traitements des formulaires. |
| Form et Validator | Construisent les formulaires et contrôlent les données reçues avant leur traitement. | Inscription, profil, publications, commentaires et téléversement des images avec contraintes de validation. |
| Symfony Security | Gère l'authentification, le hachage des mots de passe, les rôles, le pare-feu et les contrôles d'accès. | Authentification des membres, protection CSRF et séparation des droits `ROLE_USER` et `ROLE_ADMIN`. |
| Twig | Produit les pages HTML à partir de templates et des données fournies par les contrôleurs. | Pages publiques, fil d'actualité, profils, formulaires et administration, avec affichage des dates en `Europe/Paris`. |
| AssetMapper | Référence et publie les fichiers CSS, JavaScript et images sans bundler JavaScript supplémentaire. | Utilisation des sources versionnées dans `assets/`, puis compilation de la carte des ressources dans `public/assets/` en production. |
| Web Profiler et DebugBundle | Affichent en développement les routes, requêtes, services, erreurs et performances d'une page. | Diagnostic local uniquement ; ces outils ne sont pas installés en production grâce à `composer install --no-dev`. |

### Étapes de construction du code

1. **Installation du socle** : Composer et Symfony Flex ont installé les composants nécessaires et préparé leur configuration.
2. **Modélisation avec Doctrine** : les entités et leurs relations ont traduit le modèle de données en objets PHP. Les repositories centralisent les recherches en base.
3. **Versionnement de la base** : les différences entre les entités et le schéma ont été transformées en migrations, puis appliquées avec `doctrine:migrations:migrate`.
4. **Création des parcours** : les contrôleurs et leurs routes ont été construits pour l'inscription, l'authentification, le fil, les profils, les publications, les interactions et l'administration.
5. **Traitement des entrées** : les FormTypes et les contraintes Validator vérifient les données avant l'écriture en base. Les contrôleurs appliquent ensuite les autorisations et les règles métier.
6. **Sécurisation** : Symfony Security protège les zones membres et administrateur. Les actions sensibles contrôlent également les rôles, la propriété des ressources et les jetons CSRF.
7. **Création des vues** : Twig affiche les données transmises par les contrôleurs. Les composants visuels communs sont partagés entre les templates.
8. **Intégration des ressources** : Sass compile `resources/scss/app.scss` vers `assets/styles/app.css`. AssetMapper sert ensuite le CSS, le JavaScript classique et les images.
9. **Contrôle du projet** : les commandes `lint:*`, le lanceur de tests PHP et la CI GitHub vérifient la syntaxe, le conteneur de services, les templates, la configuration et les règles métier.

Les principales commandes Symfony correspondant à cette construction sont :

```bash
php bin/console make:entity
php bin/console make:controller
php bin/console make:form
php bin/console make:migration
php bin/console doctrine:migrations:migrate
php bin/console debug:router
php bin/console cache:clear
php bin/console asset-map:compile
```

MakerBundle accélère donc la création des structures répétitives, mais il ne remplace pas le développement. Les règles de visibilité, d'amitié, de propriété, de modération et de téléversement ont été écrites et adaptées dans les contrôleurs, repositories, formulaires et services du projet.

## Prérequis locaux

- PHP 8.2 ou supérieur avec les extensions détaillées ci-dessous ;
- Composer ;
- MySQL ;
- Node.js et npm ;
- Laragon avec Apache configuré pour utiliser `public/` comme racine Web.

### Extensions PHP

| Extension | Utilisation dans Voisin |
|---|---|
| `ctype` | Traitement de caractères utilisé par les composants Symfony |
| `iconv` | Conversion et traitement des chaînes de caractères |
| `PDO` | Couche d'accès aux bases de données utilisée par Doctrine |
| `pdo_mysql` | Connexion de Doctrine à MySQL en local et sur OVH |
| `fileinfo` | Détection du type réel des images téléversées |
| `intl` | Fonctions d'internationalisation utilisées par Symfony |

Sous PowerShell, leur présence peut être contrôlée avec :

```powershell
php -m | Select-String '^(ctype|iconv|PDO|pdo_mysql|fileinfo|intl)$'
```

Dans le terminal SSH du serveur OVH :

```bash
php -m | grep -E '^(ctype|iconv|PDO|pdo_mysql|fileinfo|intl)$'
```

Une extension peut être disponible sur le serveur sans être explicitement déclarée dans `composer.json`. Sa déclaration reste recommandée afin que Composer puisse détecter un environnement incomplet avant l'installation.

## Installation locale

### 1. Récupérer le projet

```bash
git clone https://github.com/anthonylepichon/voisin.git
cd voisin
```

Pour travailler sur la version de développement :

```bash
git switch develop
```

### 2. Installer les dépendances

```bash
composer install
npm install
```

### 3. Configurer l'environnement local

Créer un fichier `.env.local` à la racine du projet. Ce fichier est ignoré par Git et ne doit jamais être publié.

```dotenv
APP_ENV=dev
APP_SECRET=remplacer_par_un_secret_local_aleatoire
DATABASE_URL="mysql://utilisateur:mot_de_passe@127.0.0.1:3306/voisin?serverVersion=8.0&charset=utf8mb4"
DEFAULT_URI=http://voisin.test
```

Adapter l'utilisateur, le mot de passe, le port et `serverVersion` à l'installation MySQL locale. Le mot de passe doit être encodé dans l'URL lorsqu'il contient des caractères réservés.

Une valeur aléatoire peut être générée pour `APP_SECRET` avec PHP :

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

### 4. Créer la base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

Vérifier ensuite la cohérence du schéma :

```bash
php bin/console doctrine:schema:validate
```

### 5. Compiler les styles

Compilation ponctuelle :

```bash
npm run sass:build
```

Compilation automatique pendant le développement :

```bash
npm run sass:watch
```

### 6. Ouvrir l'application

Avec le projet placé dans `C:\laragon\www\voisin` et la racine Web configurée sur `public/`, l'application peut être ouverte à l'adresse :

[http://voisin.test](http://voisin.test)

## Données de démonstration

Un jeu de données fictives permet de tester localement les profils, publications, commentaires, mentions « J'aime », amitiés et demandes d'amitié.

- [Consulter le guide complet et la requête SQL des données de démonstration](documents/readme/requete-donnees-demo.md)

Ce document associé regroupe dans un seul fichier :

- les comptes fictifs disponibles ;
- le mot de passe commun réservé au développement local ;
- le nombre d'enregistrements créés dans chaque table ;
- les précautions d'importation ;
- la requête SQL complète et versionnée avec le projet.

Le script doit être importé uniquement après l'exécution de toutes les migrations, dans une base locale vide. Il contient exclusivement des insertions et ne doit être exécuté qu'une seule fois.

Pour l'importer avec le client MySQL :

```bash
mysql -u utilisateur -p voisin
```

Puis, dans le terminal MySQL, copier et exécuter le bloc intitulé « Requête SQL complète » du [guide des données de démonstration](documents/readme/requete-donnees-demo.md).

Les huit comptes fictifs utilisent le mot de passe local indiqué dans le [guide des données de démonstration](documents/readme/requete-donnees-demo.md). Ils possèdent uniquement `ROLE_USER` : aucun compte administrateur n'est créé par ce script.

### Compte administrateur de démonstration

| Champ | Valeur |
|---|---|
| Pseudonyme | `admin` |
| Adresse e-mail | `admin@gmail.com` |
| Mot de passe | `VoisinDemo2026!` |

Ce compte est exclusivement destiné aux démonstrations. Ces identifiants étant publiés dans le dépôt GitHub, ils ne doivent pas être réutilisés pour un compte de production ou pour un autre service.

> Le jeu de démonstration est strictement réservé au développement local. Il ne doit jamais être importé dans la base de production.

## Conception

### Tableau des spécifications

Le classeur de spécifications relie les choix de conception aux fichiers réellement développés. Il comporte trois feuilles consacrées aux contrôleurs Symfony, aux templates Twig et au JavaScript. Chaque ligne précise notamment le traitement du fichier, son rôle, les dépendances utilisées, les droits nécessaires et les règles métier ou de sécurité associées.

- [Télécharger le tableau Excel des spécifications de Voisin](<documents/conceptualisation/Spécifications/Specifications-VOISIN.xlsx>)

### Schéma ergonomique

Le schéma ergonomique présente l'enchaînement des écrans et les parcours principaux de l'application.

![Schéma ergonomique de l'application Voisin](<documents/conceptualisation/Schéma Ergonomique/Schéma ergonomique.jpg>)

### Modèle conceptuel de données

Le MCD décrit les données métier et leurs relations indépendamment de leur implémentation technique.

![Modèle conceptuel de données de l'application Voisin](<documents/conceptualisation/Modèles de données/MCD.jpg>)

### Modèle physique de données

Le MPD traduit le modèle conceptuel dans la structure relationnelle utilisée par la base de données.

![Modèle physique de données de l'application Voisin](<documents/conceptualisation/Modèles de données/MPD.jpg>)

## Sécurité

- les mots de passe sont hachés avec le mécanisme de Symfony ;
- les formulaires sensibles sont protégés contre les attaques CSRF ;
- les pages membres nécessitent `ROLE_USER` ;
- les pages d'administration nécessitent `ROLE_ADMIN` ;
- les actions sur les publications et commentaires vérifient les autorisations ;
- `.env.local`, `.env.dev` et les autres fichiers locaux sensibles sont exclus de Git ;
- les secrets de production sont stockés dans l'environnement GitHub `production`.

Aucun mot de passe, secret applicatif ou clé SSH ne doit être ajouté au dépôt.

## Gestion des dates

Le serveur OVH est situé en France et les utilisateurs consultent les dates selon le fuseau `Europe/Paris`. L'application sépare cependant deux responsabilités :

- PHP Web, PHP CLI, Doctrine et les entités utilisent UTC pour créer, traiter et enregistrer les dates sans ambiguïté ;
- Twig convertit les dates en `Europe/Paris` au moment de l'affichage, avec la gestion automatique des heures d'été et d'hiver.

Les entités utilisent `DateTimeImmutable` afin qu'une opération comme `modify()` ne change jamais silencieusement une date déjà transmise à un autre composant.

## Contrôles automatisés

La CI GitHub Actions est exécutée sur les Pull Requests vers `develop` et `main`, ainsi que sur les mises à jour de `develop`. Elle contrôle notamment :

- la cohérence de `composer.json` et `composer.lock` ;
- la syntaxe PHP et JavaScript ;
- le conteneur Symfony ;
- les templates Twig ;
- les fichiers YAML ;
- les règles métier couvertes par le lanceur PHP du dossier `tests/` ;
- la compilation Sass et l'actualisation du CSS compilé.

Les tests ne se connectent ni à la base locale ni à la production. Les tests d'intégration utilisent une base SQLite créée uniquement en mémoire pendant leur exécution.

Pour lancer toute la campagne de tests :

```bash
composer test
```

Les principaux contrôles peuvent aussi être exécutés localement :

```bash
composer validate --strict --no-check-publish
php bin/console lint:container
php bin/console lint:twig templates
php bin/console lint:yaml config --parse-tags
composer test
npm run sass:build
```

## Déploiement

Le site est hébergé sur une offre Web mutualisée OVH. Le déploiement suit une démarche CI/CD avec Git, GitHub, GitHub Actions et SSH. Cette organisation relève d'une pratique DevOps : le développement, les contrôles et les opérations de mise en production sont reliés par un processus versionné, automatisé et reproductible.

Le projet utilise deux branches permanentes :

- `develop` pour l'intégration et le développement ;
- `main` pour la version stable déployée en production.

### Préparation de l'hébergement OVH

La mise en production initiale a nécessité les opérations suivantes :

1. associer le domaine `voisin.creativconsulting.fr` à l'hébergement OVH et activer son certificat SSL ;
2. configurer `public/` comme racine Web afin que les autres fichiers du projet ne soient pas directement accessibles ;
3. sélectionner PHP 8.2 sur l'hébergement et vérifier les extensions nécessaires ;
4. créer la base MySQL de production et vérifier la connexion avec `pdo_mysql` ;
5. cloner le dépôt dans le répertoire de déploiement OVH et positionner sa copie de travail sur `main` ;
6. créer sur OVH un fichier `.env.local` de production contenant `APP_ENV`, `APP_SECRET`, `DATABASE_URL` et `DEFAULT_URI` ;
7. créer un compte administrateur de production distinct des comptes locaux ;
8. installer sur le serveur une clé de déploiement GitHub en lecture seule pour permettre les opérations `git fetch` et `git pull`.

Le fichier `.env.local` de production est ignoré par Git. Une mise à jour du dépôt ne l'écrase donc pas et aucun secret de production n'est stocké dans le code source.

### Intégration continue

Le workflow [`.github/workflows/ci.yml`](.github/workflows/ci.yml) s'exécute sur les Pull Requests vers `develop` et `main`, ainsi que sur les mises à jour de `develop`. Il installe un environnement PHP 8.2 et Node.js, puis contrôle Composer, la syntaxe PHP et JavaScript, le conteneur Symfony, Twig, YAML, les tests automatisés et la compilation Sass.

Le chemin normal d'une modification est donc :

```text
branche de travail
→ Pull Request vers develop
→ contrôles CI
→ fusion dans develop
→ Pull Request de production vers main
→ nouveaux contrôles CI
→ fusion dans main
```

`develop` ne déploie jamais l'application. Seule une version relue, contrôlée puis fusionnée dans `main` peut atteindre la production.

### Secrets GitHub de production

L'environnement GitHub `production` fournit au workflow les secrets suivants :

| Secret | Utilisation |
|---|---|
| `OVH_HOST` | Adresse du serveur SSH OVH |
| `OVH_PORT` | Port de connexion SSH |
| `OVH_USER` | Compte SSH de l'hébergement |
| `OVH_DEPLOY_PATH` | Chemin absolu du dépôt sur OVH |
| `OVH_SSH_PRIVATE_KEY` | Clé privée utilisée temporairement par GitHub Actions pour joindre OVH |
| `OVH_KNOWN_HOSTS` | Empreinte SSH attendue du serveur OVH |
| `PRODUCTION_URL` | URL utilisée pour le contrôle final du site |

La clé privée GitHub Actions et la clé de déploiement en lecture seule installée sur OVH ont deux rôles différents : la première autorise GitHub Actions à ouvrir une session SSH vers OVH ; la seconde autorise le serveur OVH à lire le dépôt GitHub.

### Déploiement automatique

Le workflow [`.github/workflows/deploy-production.yml`](.github/workflows/deploy-production.yml) se déclenche après une mise à jour de `main` ou lors d'un lancement manuel autorisé. Il :

1. prépare une clé SSH temporaire sur le runner GitHub Actions ;
2. vérifie que le dépôt OVH est bien positionné sur `main` ;
3. compare le dossier `migrations/` installé avec celui de la version à déployer ;
4. interrompt le processus avant toute mise à jour si une migration est détectée ;
5. ouvre une connexion SSH vers OVH et exécute `git pull --ff-only origin main` ;
6. exécute Composer avec `--no-dev` et `--optimize-autoloader` ;
7. vide et reconstruit le cache Symfony en environnement `prod` ;
8. exécute `asset-map:compile` pour produire `public/assets/` ;
9. appelle l'URL de production avec `curl` et fait échouer le workflow si le site ne répond pas correctement ;
10. supprime la clé SSH temporaire du runner, même en cas d'échec.

Le CSS compilé dans `assets/styles/app.css` est versionné. La CI vérifie avant fusion qu'il correspond encore aux sources Sass. Le serveur de production n'a donc pas besoin de Node.js pour déployer la version validée ; AssetMapper se charge de publier les ressources suivies par Git.

### Gestion volontairement manuelle des migrations

Les migrations de production ne sont pas lancées automatiquement. Lorsqu'une modification du dossier `migrations/` est détectée, le workflow s'arrête avant de modifier le serveur. La procédure consiste alors à :

1. sauvegarder la base MySQL de production ;
2. vérifier les migrations concernées ;
3. les exécuter manuellement avec Doctrine sur OVH ;
4. contrôler l'état de la base ;
5. relancer le workflow de production.

Ce choix conserve l'automatisation des opérations répétitives tout en maintenant une validation humaine avant une modification structurelle des données de production.

## Licence

Projet pédagogique. Le code n'est pas distribué sous une licence open source.
