<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206134805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rendez_vous (rendez_vous_id INT AUTO_INCREMENT NOT NULL, motif LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, dispo_id INT NOT NULL, etudiant_id INT NOT NULL, psy_id INT NOT NULL, INDEX IDX_65E8AA0AA18C1CC9 (dispo_id), INDEX IDX_65E8AA0ADDEAB1A3 (etudiant_id), INDEX IDX_65E8AA0A8BA5C549 (psy_id), PRIMARY KEY (rendez_vous_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AA18C1CC9 FOREIGN KEY (dispo_id) REFERENCES disponibilite_psy (dispo_id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0ADDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A8BA5C549 FOREIGN KEY (psy_id) REFERENCES user (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AA18C1CC9');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0ADDEAB1A3');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A8BA5C549');
        $this->addSql('DROP TABLE rendez_vous');
    }
}
