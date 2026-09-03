<!--
Description générale : Documente le jeu de données local de démonstration du projet Voisin.
Rôle : Expliquer les comptes fictifs et l'import du script SQL qui peuple toutes les tables métier.
Tâches : Identifier les profils, le mot de passe commun local, les volumes insérés et la source SQL à importer.
Liens avec les autres fichiers : Décrit documents/readme/donnees-demonstration.sql et les images de uploads/profils.
-->

# Requête des données de démonstration

Le script complet à importer est :

[`documents/readme/donnees-demonstration.sql`](donnees-demonstration.sql)

Il est prévu pour une base locale `voisin` ayant reçu la migration initiale. Il n'est jamais importé en production.

## Comptes de démonstration

Les huit comptes sont fictifs et possèdent tous le rôle `ROLE_USER`. Aucun compte administrateur n'est créé par ce script.

| Pseudonyme | Adresse e-mail | Image de profil |
|---|---|---|
| Alice | `alice@example.test` | `profil-alice.jpg` |
| Aline | `aline@example.test` | `profil-aline.jpg` |
| Benoit | `benoit@example.test` | `profil-benoit.jpg` |
| Eloise | `eloise@example.test` | `profil-eloise.jpg` |
| Jessica | `jessica@example.test` | `profil-jessica.jpg` |
| Quentin | `quentin@example.test` | `profil-quentin.jpg` |
| Quentin_2 | `quentin2@example.test` | `profil-quentin2.jpg` |
| Sophie | `sophie@example.test` | `profil-sophie.jpg` |

Le mot de passe commun, exclusivement local, est :

```text
VoisinDemo2026!
```

## Contenu du script

| Table | Données insérées |
|---|---:|
| `utilisateur` | 8 profils |
| `upload_fichier` | 8 photos de profil |
| `publication` | 24 publications |
| `commentaire` | 48 commentaires |
| `asso_utilisateur_publication` | 48 likes |
| `asso_utilisateur_utilisateur` | 10 amitiés normalisées |
| `demande_amitie` | 6 demandes d'amitié |

Le script contient uniquement des insertions. Il ne réinitialise ni ne supprime aucune donnée. Il doit donc être importé une seule fois dans une base de démonstration vide.
