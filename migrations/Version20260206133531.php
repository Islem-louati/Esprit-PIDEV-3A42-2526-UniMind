<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206133531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE disponibilite_psy (dispo_id INT AUTO_INCREMENT NOT NULL, date_dispo DATE NOT NULL, heure_debut TIME NOT NULL, heure_fin TIME NOT NULL, type_consult VARCHAR(20) NOT NULL, lieu VARCHAR(255) DEFAULT NULL, statut VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_848265E6A76ED395 (user_id), PRIMARY KEY (dispo_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE disponibilite_psy ADD CONSTRAINT FK_848265E6A76ED395 FOREIGN KEY (user_id) REFERENCES user (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE disponibilite_psy DROP FOREIGN KEY FK_848265E6A76ED395');
        $this->addSql('DROP TABLE disponibilite_psy');
    }
}
