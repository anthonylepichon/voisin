-- Description générale : Crée un jeu complet de données fictives pour la démonstration locale de Voisin.
-- Rôle : Alimenter toutes les tables métier avec huit profils, leurs fichiers, des publications, commentaires, likes, amitiés et demandes.
-- Tâches : Insérer uniquement des données de démonstration locales, sans compte administrateur ni donnée personnelle réelle.
-- Liens avec les autres fichiers : Utilise les images présentes dans uploads/profils et est documenté dans documents/readme/requete-donnees-demo.md.

INSERT INTO utilisateur (id, pseudonyme, adresse_email, mot_de_passe, roles, nom_photo_profil, biographie, date_inscription, date_derniere_activite) VALUES
(1, 'Alice', 'alice@example.test', '$2y$13$33UYRTzpqOnbDrF1vk7HCOsnVQawfB0W0yJfI9pTczSMkOW/ixvaS', '["ROLE_USER"]', 'profil-alice.jpg', 'Habitante du quartier et passionnée de jardinage.', '2026-01-12 09:15:00', '2026-09-02 17:40:00'),
(2, 'Aline', 'aline@example.test', '$2y$13$33UYRTzpqOnbDrF1vk7HCOsnVQawfB0W0yJfI9pTczSMkOW/ixvaS', '["ROLE_USER"]', 'profil-aline.jpg', 'Toujours partante pour les initiatives entre voisins.', '2026-01-18 11:30:00', '2026-09-02 16:25:00'),
(3, 'Benoit', 'benoit@example.test', '$2y$13$33UYRTzpqOnbDrF1vk7HCOsnVQawfB0W0yJfI9pTczSMkOW/ixvaS', '["ROLE_USER"]', 'profil-benoit.jpg', 'Bricoleur du dimanche et voisin attentif.', '2026-02-03 14:10:00', '2026-09-02 15:50:00'),
(4, 'Eloise', 'eloise@example.test', '$2y$13$33UYRTzpqOnbDrF1vk7HCOsnVQawfB0W0yJfI9pTczSMkOW/ixvaS', '["ROLE_USER"]', 'profil-eloise.jpg', 'Aime partager les bons plans du quartier.', '2026-02-11 08:45:00', '2026-09-01 19:20:00'),
(5, 'Jessica', 'jessica@example.test', '$2y$13$33UYRTzpqOnbDrF1vk7HCOsnVQawfB0W0yJfI9pTczSMkOW/ixvaS', '["ROLE_USER"]', 'profil-jessica.jpg', 'Membre active de la vie locale.', '2026-03-02 10:05:00', '2026-09-02 18:05:00'),
(6, 'Quentin', 'quentin@example.test', '$2y$13$33UYRTzpqOnbDrF1vk7HCOsnVQawfB0W0yJfI9pTczSMkOW/ixvaS', '["ROLE_USER"]', 'profil-quentin.jpg', 'Nouveau voisin curieux de découvrir les environs.', '2026-03-19 16:35:00', '2026-09-02 12:10:00'),
(7, 'Quentin_2', 'quentin2@example.test', '$2y$13$33UYRTzpqOnbDrF1vk7HCOsnVQawfB0W0yJfI9pTczSMkOW/ixvaS', '["ROLE_USER"]', 'profil-quentin2.jpg', 'Partage volontiers des nouvelles du voisinage.', '2026-04-06 13:20:00', '2026-09-01 14:45:00'),
(8, 'Sophie', 'sophie@example.test', '$2y$13$33UYRTzpqOnbDrF1vk7HCOsnVQawfB0W0yJfI9pTczSMkOW/ixvaS', '["ROLE_USER"]', 'profil-sophie.jpg', 'Amatrice de balades et de moments conviviaux.', '2026-04-22 09:50:00', '2026-09-02 17:15:00');

INSERT INTO upload_fichier (type, nom, chemin, utilisateur_id, publication_id) VALUES
('profil', 'profil-alice.jpg', 'uploads/profils', 1, NULL),
('profil', 'profil-aline.jpg', 'uploads/profils', 2, NULL),
('profil', 'profil-benoit.jpg', 'uploads/profils', 3, NULL),
('profil', 'profil-eloise.jpg', 'uploads/profils', 4, NULL),
('profil', 'profil-jessica.jpg', 'uploads/profils', 5, NULL),
('profil', 'profil-quentin.jpg', 'uploads/profils', 6, NULL),
('profil', 'profil-quentin2.jpg', 'uploads/profils', 7, NULL),
('profil', 'profil-sophie.jpg', 'uploads/profils', 8, NULL);

INSERT INTO publication (id, contenu, nom_image, visibilite, date_creation, utilisateur_id) VALUES
(1, 'Bienvenue sur Voisin : partageons les informations utiles de notre quartier.', NULL, 'publique', '2026-08-10 08:00:00', 1),
(2, 'Le jardin partagé sera ouvert samedi matin.', NULL, 'amis', '2026-08-12 17:30:00', 1),
(3, 'Je cherche des idées pour animer la prochaine rencontre de voisins.', NULL, 'publique', '2026-08-20 18:15:00', 1),
(4, 'Merci pour votre accueil depuis mon arrivée dans le quartier.', NULL, 'publique', '2026-08-11 10:20:00', 2),
(5, 'Une petite collecte de livres est organisée devant la médiathèque.', NULL, 'amis', '2026-08-15 12:05:00', 2),
(6, 'Qui serait disponible pour arroser les plantes pendant le week-end ?', NULL, 'publique', '2026-08-24 09:40:00', 2),
(7, 'J ai réparé le banc près de la place, il est de nouveau utilisable.', NULL, 'publique', '2026-08-09 15:10:00', 3),
(8, 'Je peux prêter quelques outils pour les petits travaux.', NULL, 'amis', '2026-08-17 11:25:00', 3),
(9, 'Le marché du dimanche accueille un nouveau producteur local.', NULL, 'publique', '2026-08-28 08:55:00', 3),
(10, 'Une belle journée pour se retrouver entre voisins.', NULL, 'publique', '2026-08-13 14:35:00', 4),
(11, 'Je connais un atelier de réparation de vélos tout près.', NULL, 'amis', '2026-08-19 16:45:00', 4),
(12, 'Pensez à la réunion de quartier mardi prochain.', NULL, 'publique', '2026-08-30 18:20:00', 4),
(13, 'Je propose un échange de plantes pour la fin du mois.', NULL, 'publique', '2026-08-14 09:05:00', 5),
(14, 'Des voisins intéressés par une marche du matin ?', NULL, 'amis', '2026-08-21 07:50:00', 5),
(15, 'Le parc est particulièrement agréable en cette saison.', NULL, 'publique', '2026-09-01 11:10:00', 5),
(16, 'Je découvre avec plaisir les initiatives du quartier.', NULL, 'publique', '2026-08-16 13:40:00', 6),
(17, 'Quel est votre endroit préféré pour prendre un café ici ?', NULL, 'amis', '2026-08-23 10:30:00', 6),
(18, 'Merci pour les conseils concernant les commerces de proximité.', NULL, 'publique', '2026-09-02 08:20:00', 6),
(19, 'Je peux aider à installer les tables pour la fête de rue.', NULL, 'publique', '2026-08-18 12:15:00', 7),
(20, 'Une séance de jeux de société vous intéresserait-elle ?', NULL, 'amis', '2026-08-26 19:00:00', 7),
(21, 'Le coucher de soleil était magnifique depuis le square.', NULL, 'publique', '2026-09-01 20:10:00', 7),
(22, 'Je prépare quelques gâteaux pour le goûter des voisins.', NULL, 'publique', '2026-08-22 15:25:00', 8),
(23, 'Merci à celles et ceux qui participent au nettoyage de la rue.', NULL, 'amis', '2026-08-29 09:15:00', 8),
(24, 'Rendez-vous dimanche pour la promenade au bord du canal.', NULL, 'publique', '2026-09-02 16:30:00', 8);

INSERT INTO commentaire (id, contenu, date_creation, utilisateur_id, publication_id) VALUES
(1, 'Très bonne idée, merci pour le partage.', '2026-08-10 09:15:00', 2, 1),
(2, 'Je suis partant pour participer.', '2026-08-10 10:00:00', 3, 1),
(3, 'Je viendrai avec plaisir.', '2026-08-12 18:05:00', 5, 2),
(4, 'Je peux apporter quelques graines.', '2026-08-12 18:20:00', 8, 2),
(5, 'Pourquoi pas une chasse au trésor pour les enfants ?', '2026-08-20 19:10:00', 4, 3),
(6, 'Excellente suggestion.', '2026-08-20 19:45:00', 7, 3),
(7, 'Le plaisir est partagé.', '2026-08-11 11:00:00', 1, 4),
(8, 'Bienvenue parmi nous.', '2026-08-11 11:30:00', 8, 4),
(9, 'Je déposerai quelques romans demain.', '2026-08-15 13:15:00', 3, 5),
(10, 'Merci pour cette initiative.', '2026-08-15 14:20:00', 6, 5),
(11, 'Je suis disponible samedi matin.', '2026-08-24 10:05:00', 1, 6),
(12, 'Je peux passer dimanche aussi.', '2026-08-24 10:30:00', 5, 6),
(13, 'Merci pour le coup de main.', '2026-08-09 16:05:00', 1, 7),
(14, 'Le banc est beaucoup plus agréable maintenant.', '2026-08-09 16:40:00', 4, 7),
(15, 'Je pourrais emprunter une perceuse la semaine prochaine.', '2026-08-17 12:10:00', 2, 8),
(16, 'Bien sûr, écris-moi quand tu veux.', '2026-08-17 12:35:00', 3, 8),
(17, 'J irai le découvrir dimanche.', '2026-08-28 09:25:00', 5, 9),
(18, 'Merci pour l information.', '2026-08-28 10:00:00', 8, 9),
(19, 'Avec plaisir.', '2026-08-13 15:05:00', 2, 10),
(20, 'Ce sera parfait si le temps reste doux.', '2026-08-13 15:30:00', 6, 10),
(21, 'Très utile, merci.', '2026-08-19 17:05:00', 1, 11),
(22, 'Je cherche justement une bonne adresse.', '2026-08-19 17:30:00', 7, 11),
(23, 'Je serai présente.', '2026-08-30 18:35:00', 2, 12),
(24, 'Merci pour le rappel.', '2026-08-30 19:00:00', 5, 12),
(25, 'Je viendrai avec quelques boutures.', '2026-08-14 10:15:00', 4, 13),
(26, 'Très bonne idée pour faire connaissance.', '2026-08-14 10:45:00', 8, 13),
(27, 'Je suis intéressé.', '2026-08-21 08:05:00', 1, 14),
(28, 'Je peux vous rejoindre vers neuf heures.', '2026-08-21 08:20:00', 6, 14),
(29, 'Merci pour cette belle photo décrite.', '2026-09-01 11:25:00', 3, 15),
(30, 'Le parc est vraiment reposant.', '2026-09-01 11:50:00', 7, 15),
(31, 'Bienvenue Quentin.', '2026-08-16 14:10:00', 1, 16),
(32, 'Tu verras, il y a beaucoup de choses à découvrir.', '2026-08-16 14:30:00', 5, 16),
(33, 'Le café de la place est très sympathique.', '2026-08-23 10:50:00', 4, 17),
(34, 'Je recommande aussi celui près du marché.', '2026-08-23 11:15:00', 8, 17),
(35, 'Avec plaisir.', '2026-09-02 08:35:00', 2, 18),
(36, 'N hésite pas à demander si besoin.', '2026-09-02 08:55:00', 3, 18),
(37, 'Merci, ce sera très utile.', '2026-08-18 12:40:00', 5, 19),
(38, 'Je peux venir un peu plus tôt.', '2026-08-18 13:00:00', 6, 19),
(39, 'Oui, avec plaisir.', '2026-08-26 19:20:00', 2, 20),
(40, 'Je peux apporter un jeu coopératif.', '2026-08-26 19:45:00', 4, 20),
(41, 'Le square est idéal pour cela.', '2026-09-01 20:25:00', 1, 21),
(42, 'Merci pour le partage.', '2026-09-01 20:45:00', 8, 21),
(43, 'Je peux apporter du thé.', '2026-08-22 15:50:00', 3, 22),
(44, 'Je viendrai avec des fruits.', '2026-08-22 16:15:00', 7, 22),
(45, 'Merci à toutes et tous.', '2026-08-29 09:35:00', 2, 23),
(46, 'La rue est déjà bien plus agréable.', '2026-08-29 10:05:00', 5, 23),
(47, 'Très bonne idée pour dimanche.', '2026-09-02 16:45:00', 4, 24),
(48, 'Je serai au rendez-vous.', '2026-09-02 17:05:00', 6, 24);

INSERT INTO asso_utilisateur_publication (publication_id, utilisateur_id) VALUES
(1, 2), (1, 3), (1, 5), (2, 5), (2, 8), (3, 4), (3, 7), (4, 1),
(5, 3), (5, 6), (6, 1), (6, 5), (7, 1), (7, 4), (8, 2), (8, 7),
(9, 5), (9, 8), (10, 2), (10, 6), (11, 1), (11, 7), (12, 2), (12, 5),
(13, 4), (13, 8), (14, 1), (14, 6), (15, 3), (15, 7), (16, 1), (16, 5),
(17, 4), (17, 8), (18, 2), (18, 3), (19, 5), (19, 6), (20, 2), (20, 4),
(21, 1), (21, 8), (22, 3), (22, 7), (23, 2), (23, 5), (24, 4), (24, 6);

INSERT INTO asso_utilisateur_utilisateur (utilisateur_id, ami_id) VALUES
(1, 2), (1, 3), (1, 8), (2, 4), (2, 5), (3, 6), (4, 7), (5, 8), (6, 7), (7, 8);

INSERT INTO demande_amitie (date_creation, expediteur_id, destinataire_id) VALUES
('2026-09-01 09:00:00', 6, 2),
('2026-09-01 10:15:00', 7, 1),
('2026-09-01 11:30:00', 3, 5),
('2026-09-02 08:45:00', 8, 4),
('2026-09-02 14:20:00', 4, 6),
('2026-09-02 15:10:00', 5, 7);
