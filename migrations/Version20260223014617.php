<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223014617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE favori (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, evenement_id INT NOT NULL, etudiant_id INT NOT NULL, INDEX IDX_EF85A2CCFD02F13 (evenement_id), INDEX IDX_EF85A2CCDDEAB1A3 (etudiant_id), UNIQUE INDEX uniq_favori_evenement_etudiant (evenement_id, etudiant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCFD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (evenement_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCDDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES user (user_id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCFD02F13');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCDDEAB1A3');
        $this->addSql('DROP TABLE favori');
    }
}
