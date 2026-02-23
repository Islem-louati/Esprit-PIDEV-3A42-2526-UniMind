<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206140657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE consultation (consultation_id INT AUTO_INCREMENT NOT NULL, date_redaction DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, avis_psy LONGTEXT DEFAULT NULL, note_satisfaction SMALLINT DEFAULT NULL, rendez_vous_id INT NOT NULL, psy_user_id INT NOT NULL, etudiant_user_id INT NOT NULL, UNIQUE INDEX UNIQ_964685A691EF7EAA (rendez_vous_id), INDEX IDX_964685A662DF458B (psy_user_id), INDEX IDX_964685A673C19D11 (etudiant_user_id), PRIMARY KEY (consultation_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A691EF7EAA FOREIGN KEY (rendez_vous_id) REFERENCES rendez_vous (rendez_vous_id)');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A662DF458B FOREIGN KEY (psy_user_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A673C19D11 FOREIGN KEY (etudiant_user_id) REFERENCES user (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A691EF7EAA');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A662DF458B');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A673C19D11');
        $this->addSql('DROP TABLE consultation');
    }
}
