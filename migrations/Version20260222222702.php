<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222222702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rendez_vous DROP INDEX IDX_65E8AA0AA18C1CC9, ADD UNIQUE INDEX UNIQ_65E8AA0AA18C1CC9 (dispo_id)');
        $this->addSql('ALTER TABLE seance_meditation CHANGE fichier fichier VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sponsor CHANGE domaine_activite domaine_activite VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rendez_vous DROP INDEX UNIQ_65E8AA0AA18C1CC9, ADD INDEX IDX_65E8AA0AA18C1CC9 (dispo_id)');
        $this->addSql('ALTER TABLE seance_meditation CHANGE fichier fichier VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE sponsor CHANGE domaine_activite domaine_activite VARCHAR(150) DEFAULT NULL');
    }
}
