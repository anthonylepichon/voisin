<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260902182442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée le schéma initial de l’application Voisin.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, auteur_id INT NOT NULL, publication_id INT NOT NULL, INDEX idx_commentaire_auteur (auteur_id), INDEX idx_commentaire_publication (publication_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE demande_amitie (id INT AUTO_INCREMENT NOT NULL, date_creation DATETIME NOT NULL, expediteur_id INT NOT NULL, destinataire_id INT NOT NULL, INDEX IDX_CDBDB57310335F61 (expediteur_id), INDEX idx_demande_destinataire (destinataire_id), UNIQUE INDEX uq_demande_expediteur_destinataire (expediteur_id, destinataire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE publication (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT DEFAULT NULL, nom_image VARCHAR(255) DEFAULT NULL, visibilite VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, auteur_id INT NOT NULL, INDEX idx_publication_auteur (auteur_id), INDEX idx_publication_visibilite_creation (visibilite, date_creation), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE asso_utilisateur_publication (publication_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_40250EFA38B217A7 (publication_id), INDEX IDX_40250EFAFB88E14F (utilisateur_id), PRIMARY KEY (publication_id, utilisateur_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, pseudonyme VARCHAR(50) NOT NULL, adresse_email VARCHAR(180) NOT NULL, roles JSON NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, nom_photo_profil VARCHAR(255) NOT NULL, biographie VARCHAR(500) DEFAULT NULL, date_inscription DATETIME NOT NULL, date_derniere_activite DATETIME DEFAULT NULL, UNIQUE INDEX uq_utilisateur_pseudonyme (pseudonyme), UNIQUE INDEX uq_utilisateur_adresse_email (adresse_email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE asso_utilisateur_utilisateur (utilisateur_id INT NOT NULL, ami_id INT NOT NULL, INDEX IDX_F2050A30FB88E14F (utilisateur_id), INDEX IDX_F2050A30CCE66A0B (ami_id), PRIMARY KEY (utilisateur_id, ami_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande_amitie ADD CONSTRAINT FK_CDBDB57310335F61 FOREIGN KEY (expediteur_id) REFERENCES utilisateur (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE demande_amitie ADD CONSTRAINT FK_CDBDB573A4F84F6E FOREIGN KEY (destinataire_id) REFERENCES utilisateur (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C677960BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE asso_utilisateur_publication ADD CONSTRAINT FK_CB44F6ED38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE asso_utilisateur_publication ADD CONSTRAINT FK_CB44F6EDFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE asso_utilisateur_utilisateur ADD CONSTRAINT FK_7964F227FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE asso_utilisateur_utilisateur ADD CONSTRAINT FK_7964F227CCE66A0B FOREIGN KEY (ami_id) REFERENCES utilisateur (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC60BB6FE6');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC38B217A7');
        $this->addSql('ALTER TABLE demande_amitie DROP FOREIGN KEY FK_CDBDB57310335F61');
        $this->addSql('ALTER TABLE demande_amitie DROP FOREIGN KEY FK_CDBDB573A4F84F6E');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C677960BB6FE6');
        $this->addSql('ALTER TABLE asso_utilisateur_publication DROP FOREIGN KEY FK_CB44F6ED38B217A7');
        $this->addSql('ALTER TABLE asso_utilisateur_publication DROP FOREIGN KEY FK_CB44F6EDFB88E14F');
        $this->addSql('ALTER TABLE asso_utilisateur_utilisateur DROP FOREIGN KEY FK_7964F227FB88E14F');
        $this->addSql('ALTER TABLE asso_utilisateur_utilisateur DROP FOREIGN KEY FK_7964F227CCE66A0B');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE demande_amitie');
        $this->addSql('DROP TABLE publication');
        $this->addSql('DROP TABLE asso_utilisateur_publication');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE asso_utilisateur_utilisateur');
    }
}
