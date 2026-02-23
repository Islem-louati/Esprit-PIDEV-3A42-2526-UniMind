<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222222922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_participation_evenement_etudiant ON participation (evenement_id, etudiant_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_participation_evenement_etudiant ON participation');
    }
}
