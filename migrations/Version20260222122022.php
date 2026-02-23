<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222122022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->getSchemaManager();

        $evenementColumns = $this->listColumnNames($sm, 'evenement');
        if (!isset($evenementColumns['image'])) {
            $this->addSql('ALTER TABLE evenement ADD image VARCHAR(255) DEFAULT NULL');
        }

        $participationColumns = $this->listColumnNames($sm, 'participation');
        if (!isset($participationColumns['note_satisfaction'])) {
            $this->addSql('ALTER TABLE participation ADD note_satisfaction SMALLINT DEFAULT NULL');
        }
        if (!isset($participationColumns['feedback_commentaire'])) {
            $this->addSql('ALTER TABLE participation ADD feedback_commentaire LONGTEXT DEFAULT NULL');
        }
        if (!isset($participationColumns['feedback_at'])) {
            $this->addSql('ALTER TABLE participation ADD feedback_at DATETIME DEFAULT NULL');
        }

        $sponsorColumns = $this->listColumnNames($sm, 'sponsor');
        if (!isset($sponsorColumns['logo'])) {
            $this->addSql('ALTER TABLE sponsor ADD logo VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->getSchemaManager();

        $evenementColumns = $this->listColumnNames($sm, 'evenement');
        if (isset($evenementColumns['image'])) {
            $this->addSql('ALTER TABLE evenement DROP image');
        }

        $participationColumns = $this->listColumnNames($sm, 'participation');
        if (isset($participationColumns['note_satisfaction'])) {
            $this->addSql('ALTER TABLE participation DROP note_satisfaction');
        }
        if (isset($participationColumns['feedback_commentaire'])) {
            $this->addSql('ALTER TABLE participation DROP feedback_commentaire');
        }
        if (isset($participationColumns['feedback_at'])) {
            $this->addSql('ALTER TABLE participation DROP feedback_at');
        }

        $sponsorColumns = $this->listColumnNames($sm, 'sponsor');
        if (isset($sponsorColumns['logo'])) {
            $this->addSql('ALTER TABLE sponsor DROP logo');
        }
    }

    private function getSchemaManager(): AbstractSchemaManager
    {
        $connection = $this->connection;
        if (method_exists($connection, 'createSchemaManager')) {
            return $connection->createSchemaManager();
        }

        return $connection->getSchemaManager();
    }

    /**
     * @return array<string, true>
     */
    private function listColumnNames(AbstractSchemaManager $sm, string $tableName): array
    {
        $columns = [];
        foreach ($sm->listTableColumns($tableName) as $column) {
            $columns[strtolower($column->getName())] = true;
        }

        return $columns;
    }
}
