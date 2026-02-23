<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216203419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE question ADD type_question VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE questionnaire CHANGE description description LONGTEXT DEFAULT NULL, CHANGE interpretat_legere interpretat_legere LONGTEXT DEFAULT NULL, CHANGE interpretat_modere interpretat_modere LONGTEXT DEFAULT NULL, CHANGE interpretat_severe interpretat_severe LONGTEXT DEFAULT NULL, CHANGE admin_id admin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reponse_questionnaire CHANGE user_id user_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE question DROP type_question');
        $this->addSql('ALTER TABLE questionnaire CHANGE description description LONGTEXT NOT NULL, CHANGE interpretat_legere interpretat_legere LONGTEXT NOT NULL, CHANGE interpretat_modere interpretat_modere LONGTEXT NOT NULL, CHANGE interpretat_severe interpretat_severe LONGTEXT NOT NULL, CHANGE admin_id admin_id INT NOT NULL');
        $this->addSql('ALTER TABLE reponse_questionnaire CHANGE user_id user_id INT NOT NULL');
    }
}
