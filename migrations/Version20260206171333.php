<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206171333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE question (question_id INT AUTO_INCREMENT NOT NULL, texte LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, options_quest JSON NOT NULL, score_options JSON NOT NULL, questionnaire_id INT NOT NULL, INDEX IDX_B6F7494ECE07E8FF (questionnaire_id), PRIMARY KEY (question_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE questionnaire (questionnaire_id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, nom VARCHAR(150) NOT NULL, description LONGTEXT NOT NULL, type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, interpretat_legere LONGTEXT NOT NULL, interpretat_modere LONGTEXT NOT NULL, interpretat_severe LONGTEXT NOT NULL, seuil_leger INT NOT NULL, seuil_modere INT NOT NULL, seuil_severe INT NOT NULL, nbre_questions INT NOT NULL, admin_id INT NOT NULL, UNIQUE INDEX UNIQ_7A64DAF77153098 (code), INDEX IDX_7A64DAF642B8210 (admin_id), PRIMARY KEY (questionnaire_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE reponse_questionnaire (reponse_questionnaire_id INT AUTO_INCREMENT NOT NULL, score_totale DOUBLE PRECISION NOT NULL, reponse_quest JSON NOT NULL, interpretation LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, duree_passage INT DEFAULT NULL, niveau VARCHAR(20) NOT NULL, a_besoin_psy TINYINT NOT NULL, commentaire LONGTEXT DEFAULT NULL, questionnaire_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_2C627462CE07E8FF (questionnaire_id), INDEX IDX_2C627462A76ED395 (user_id), PRIMARY KEY (reponse_questionnaire_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494ECE07E8FF FOREIGN KEY (questionnaire_id) REFERENCES questionnaire (questionnaire_id)');
        $this->addSql('ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAF642B8210 FOREIGN KEY (admin_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE reponse_questionnaire ADD CONSTRAINT FK_2C627462CE07E8FF FOREIGN KEY (questionnaire_id) REFERENCES questionnaire (questionnaire_id)');
        $this->addSql('ALTER TABLE reponse_questionnaire ADD CONSTRAINT FK_2C627462A76ED395 FOREIGN KEY (user_id) REFERENCES user (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494ECE07E8FF');
        $this->addSql('ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAF642B8210');
        $this->addSql('ALTER TABLE reponse_questionnaire DROP FOREIGN KEY FK_2C627462CE07E8FF');
        $this->addSql('ALTER TABLE reponse_questionnaire DROP FOREIGN KEY FK_2C627462A76ED395');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE questionnaire');
        $this->addSql('DROP TABLE reponse_questionnaire');
    }
}
