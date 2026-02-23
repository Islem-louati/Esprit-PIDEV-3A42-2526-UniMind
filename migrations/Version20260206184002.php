<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206184002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE suivi_traitement (suivi_id INT AUTO_INCREMENT NOT NULL, date_suivi DATE NOT NULL, effectue TINYINT NOT NULL, heure_effective TIME DEFAULT NULL, commentaires LONGTEXT DEFAULT NULL, evaluation INT DEFAULT NULL, valide TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, traitement_id INT NOT NULL, INDEX IDX_80568183DDA344B6 (traitement_id), PRIMARY KEY (suivi_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE traitement (traitement_id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, categorie VARCHAR(255) NOT NULL, duree_jours INT NOT NULL, dosage LONGTEXT DEFAULT NULL, date_debut DATE NOT NULL, date_fin DATE DEFAULT NULL, statut VARCHAR(255) NOT NULL, priorite VARCHAR(255) NOT NULL, objectif_therapeutique LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, psychologue_id INT NOT NULL, etudiant_id INT NOT NULL, INDEX IDX_2A356D27465459D3 (psychologue_id), INDEX IDX_2A356D27DDEAB1A3 (etudiant_id), PRIMARY KEY (traitement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE suivi_traitement ADD CONSTRAINT FK_80568183DDA344B6 FOREIGN KEY (traitement_id) REFERENCES traitement (traitement_id)');
        $this->addSql('ALTER TABLE traitement ADD CONSTRAINT FK_2A356D27465459D3 FOREIGN KEY (psychologue_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE traitement ADD CONSTRAINT FK_2A356D27DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES user (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE suivi_traitement DROP FOREIGN KEY FK_80568183DDA344B6');
        $this->addSql('ALTER TABLE traitement DROP FOREIGN KEY FK_2A356D27465459D3');
        $this->addSql('ALTER TABLE traitement DROP FOREIGN KEY FK_2A356D27DDEAB1A3');
        $this->addSql('DROP TABLE suivi_traitement');
        $this->addSql('DROP TABLE traitement');
    }
}
