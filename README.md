<!--
Origine du code : Code créé par le développeur.
Description générale : Présente l'application Voisin, son installation, sa conception et son déploiement.
Rôle : Servir de point d'entrée technique et fonctionnel pour découvrir, installer et évaluer le projet.
Tâches : Décrire les fonctionnalités, les prérequis, la configuration locale, les données de démonstration et le processus de livraison.
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
| `documents/` | Conception et ressources de démonstration |

Le dossier généré `public/assets/` n'est pas versionné. AssetMapper le construit pour la production à partir des fichiers suivis dans `assets/`.

## Prérequis locaux

- PHP 8.2 ou supérieur avec les extensions requises par Symfony et MySQL ;
- Composer ;
- MySQL ;
- Node.js et npm ;
- Laragon avec Apache configuré pour utiliser `public/` comme racine Web.

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

- [Consulter le guide des données de démonstration](documents/readme/requete-donnees-demo.md)
- [Consulter le script SQL d'insertion](documents/readme/donnees-demonstration.sql)

Le script doit être importé uniquement après l'exécution de toutes les migrations, dans une base locale vide. Il contient exclusivement des insertions et ne doit être exécuté qu'une seule fois.

Pour l'importer avec le client MySQL :

```bash
mysql -u utilisateur -p voisin
```

Puis, dans le terminal MySQL, en adaptant le chemin au poste de travail :

```sql
SOURCE C:/laragon/www/voisin/documents/readme/donnees-demonstration.sql;
```

Les huit comptes fictifs utilisent le mot de passe local indiqué dans le [guide des données de démonstration](documents/readme/requete-donnees-demo.md). Ils possèdent uniquement `ROLE_USER` : aucun compte administrateur n'est créé par ce script.

> Le jeu de démonstration est strictement réservé au développement local. Il ne doit jamais être importé dans la base de production.

## Conception

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

## Contrôles automatisés

La CI GitHub Actions est exécutée sur les Pull Requests vers `develop` et `main`, ainsi que sur les mises à jour de `develop`. Elle contrôle notamment :

- la cohérence de `composer.json` et `composer.lock` ;
- la syntaxe PHP et JavaScript ;
- le conteneur Symfony ;
- les templates Twig ;
- les fichiers YAML ;
- la compilation Sass et l'actualisation du CSS compilé.

Les principaux contrôles peuvent aussi être exécutés localement :

```bash
composer validate --strict --no-check-publish
php bin/console lint:container
php bin/console lint:twig templates
php bin/console lint:yaml config --parse-tags
npm run sass:build
```

## Déploiement

Le projet utilise deux branches permanentes :

- `develop` pour l'intégration et le développement ;
- `main` pour la version stable déployée en production.

Le workflow `.github/workflows/deploy-production.yml` se déclenche uniquement après une mise à jour de `main`. Il :

1. vérifie la connexion SSH et l'état des migrations ;
2. interrompt le déploiement si une migration non appliquée est détectée ;
3. met à jour le dépôt sur OVH par avance rapide ;
4. installe les dépendances Composer sans les outils de développement ;
5. vide le cache Symfony ;
6. compile les ressources avec AssetMapper ;
7. vérifie que l'URL de production répond correctement.

Les migrations de production sont exécutées manuellement après sauvegarde de la base de données.

## Licence

Projet pédagogique. Le code n'est pas distribué sous une licence open source.
