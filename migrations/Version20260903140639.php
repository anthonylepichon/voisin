<?php

/*
 * Description générale : Migration de la propriété des fichiers téléversés vers les entités qui les utilisent.
 * Rôle : Aligner la base de données sur le MPD corrigé sans perdre les noms de fichiers existants.
 * Tâches : Créer les nouvelles clés étrangères, transférer les données historiques et retirer les anciennes colonnes dupliquées.
 * Liens avec les autres fichiers : Correspond aux entités Utilisateur, Publication et UploadFichier.
 */

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260903140639 extends AbstractMigration
{
    /**
     * Rôle : Décrire la modification de schéma réalisée par la migration.
     * Paramètres : Aucun.
     * Retour : La description lisible de la migration.
     */
    public function getDescription(): string
    {
        return 'Déplace les clés étrangères des fichiers vers utilisateur et publication en conservant les données existantes.';
    }

    /**
     * Rôle : Appliquer le MPD corrigé et transférer les références de fichiers existantes.
     * Paramètres : Le schéma Doctrine fourni au moment de la migration.
     * Retour : Aucun.
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication ADD upload_fichier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD upload_fichier_id INT DEFAULT NULL');

        $this->addSql("UPDATE utilisateur u INNER JOIN upload_fichier f ON f.utilisateur_id = u.id AND f.type = 'profil' SET u.upload_fichier_id = f.id");
        $this->addSql("UPDATE publication p INNER JOIN upload_fichier f ON f.publication_id = p.id AND f.type = 'publication' SET p.upload_fichier_id = f.id");

        $this->addSql("INSERT INTO upload_fichier (type, nom, chemin) SELECT 'profil', u.nom_photo_profil, 'uploads/profils' FROM utilisateur u WHERE u.upload_fichier_id IS NULL");
        $this->addSql("UPDATE utilisateur u SET u.upload_fichier_id = (SELECT MAX(f.id) FROM upload_fichier f WHERE f.type = 'profil' AND f.nom = u.nom_photo_profil) WHERE u.upload_fichier_id IS NULL");
        $this->addSql("INSERT INTO upload_fichier (type, nom, chemin) SELECT 'publication', p.nom_image, 'uploads/publications' FROM publication p WHERE p.upload_fichier_id IS NULL AND p.nom_image IS NOT NULL");
        $this->addSql("UPDATE publication p SET p.upload_fichier_id = (SELECT MAX(f.id) FROM upload_fichier f WHERE f.type = 'publication' AND f.nom = p.nom_image) WHERE p.upload_fichier_id IS NULL AND p.nom_image IS NOT NULL");

        $this->addSql('ALTER TABLE upload_fichier DROP FOREIGN KEY `FK_5E414FF138B217A7`');
        $this->addSql('ALTER TABLE upload_fichier DROP FOREIGN KEY `FK_5E414FF1FB88E14F`');
        $this->addSql('DROP INDEX idx_upload_publication ON upload_fichier');
        $this->addSql('DROP INDEX idx_upload_utilisateur ON upload_fichier');
        $this->addSql('ALTER TABLE upload_fichier DROP utilisateur_id, DROP publication_id');

        $this->addSql('ALTER TABLE publication DROP nom_image');
        $this->addSql('ALTER TABLE utilisateur DROP nom_photo_profil, CHANGE upload_fichier_id upload_fichier_id INT NOT NULL');

        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C6779A47DFE45 FOREIGN KEY (upload_fichier_id) REFERENCES upload_fichier (id) ON DELETE RESTRICT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AF3C6779A47DFE45 ON publication (upload_fichier_id)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3A47DFE45 FOREIGN KEY (upload_fichier_id) REFERENCES upload_fichier (id) ON DELETE RESTRICT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3A47DFE45 ON utilisateur (upload_fichier_id)');
    }

    /**
     * Rôle : Restaurer l'ancien schéma tout en recopiant les noms et rattachements des fichiers.
     * Paramètres : Le schéma Doctrine fourni au moment du retour arrière.
     * Retour : Aucun.
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upload_fichier ADD utilisateur_id INT DEFAULT NULL, ADD publication_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE publication ADD nom_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD nom_photo_profil VARCHAR(255) DEFAULT NULL');

        $this->addSql('UPDATE upload_fichier f INNER JOIN utilisateur u ON u.upload_fichier_id = f.id SET f.utilisateur_id = u.id, u.nom_photo_profil = f.nom');
        $this->addSql('UPDATE upload_fichier f INNER JOIN publication p ON p.upload_fichier_id = f.id SET f.publication_id = p.id, p.nom_image = f.nom');

        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C6779A47DFE45');
        $this->addSql('DROP INDEX UNIQ_AF3C6779A47DFE45 ON publication');
        $this->addSql('ALTER TABLE publication DROP upload_fichier_id');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3A47DFE45');
        $this->addSql('DROP INDEX UNIQ_1D1C63B3A47DFE45 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP upload_fichier_id, CHANGE nom_photo_profil nom_photo_profil VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE upload_fichier ADD CONSTRAINT `FK_5E414FF138B217A7` FOREIGN KEY (publication_id) REFERENCES publication (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE upload_fichier ADD CONSTRAINT `FK_5E414FF1FB88E14F` FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_upload_publication ON upload_fichier (publication_id)');
        $this->addSql('CREATE INDEX idx_upload_utilisateur ON upload_fichier (utilisateur_id)');
    }
}
