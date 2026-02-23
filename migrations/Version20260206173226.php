<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206173226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie_meditation (categorie_id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, icon_url VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_DAF4A3FF6C6E55B5 (nom), PRIMARY KEY (categorie_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE seance_meditation (seance_id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(150) NOT NULL, description LONGTEXT NOT NULL, fichier VARCHAR(255) NOT NULL, type_fichier VARCHAR(10) NOT NULL, duree INT NOT NULL, is_active TINYINT NOT NULL, niveau VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, categorie_id INT NOT NULL, INDEX IDX_4E63F536BCF5E72D (categorie_id), PRIMARY KEY (seance_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE seance_meditation ADD CONSTRAINT FK_4E63F536BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_meditation (categorie_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seance_meditation DROP FOREIGN KEY FK_4E63F536BCF5E72D');
        $this->addSql('DROP TABLE categorie_meditation');
        $this->addSql('DROP TABLE seance_meditation');
    }
}
