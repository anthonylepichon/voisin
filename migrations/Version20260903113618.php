<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260903113618 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table centralisée des fichiers téléversés et ses relations.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE upload_fichier (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, nom VARCHAR(255) NOT NULL, chemin VARCHAR(255) NOT NULL, utilisateur_id INT DEFAULT NULL, publication_id INT DEFAULT NULL, INDEX idx_upload_utilisateur (utilisateur_id), INDEX idx_upload_publication (publication_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE upload_fichier ADD CONSTRAINT FK_5E414FF1FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE upload_fichier ADD CONSTRAINT FK_5E414FF138B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE upload_fichier DROP FOREIGN KEY FK_5E414FF1FB88E14F');
        $this->addSql('ALTER TABLE upload_fichier DROP FOREIGN KEY FK_5E414FF138B217A7');
        $this->addSql('DROP TABLE upload_fichier');
    }
}
