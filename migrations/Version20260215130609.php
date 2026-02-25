<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215130609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE suivi_traitement DROP FOREIGN KEY `FK_80568183DDA344B6`');
        $this->addSql('ALTER TABLE suivi_traitement MODIFY suivi_id INT NOT NULL');
        $this->addSql('ALTER TABLE suivi_traitement ADD heureEffective TIME DEFAULT NULL, ADD observations TEXT DEFAULT NULL, ADD observationsPsy TEXT DEFAULT NULL, ADD ressenti VARCHAR(50) DEFAULT NULL, ADD saisiPar VARCHAR(50) NOT NULL, ADD createdAt DATETIME NOT NULL, DROP commentaires, CHANGE effectue effectue TINYINT DEFAULT 0 NOT NULL, CHANGE valide valide TINYINT DEFAULT 0 NOT NULL, CHANGE suivi_id suivitraitement_id INT AUTO_INCREMENT NOT NULL, CHANGE date_suivi dateSuivi DATE NOT NULL, CHANGE created_at dateSaisie DATETIME NOT NULL, CHANGE heure_effective heurePrevue TIME DEFAULT NULL, CHANGE updated_at updatedAt DATETIME DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (suivitraitement_id)');
        $this->addSql('DROP INDEX idx_80568183dda344b6 ON suivi_traitement');
        $this->addSql('CREATE INDEX IDX_805681836EDD0F43 ON suivi_traitement (Traitement_id)');
        $this->addSql('ALTER TABLE suivi_traitement ADD CONSTRAINT `FK_80568183DDA344B6` FOREIGN KEY (traitement_id) REFERENCES traitement (traitement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE suivi_traitement DROP FOREIGN KEY FK_805681836EDD0F43');
        $this->addSql('ALTER TABLE suivi_traitement MODIFY suivitraitement_id INT NOT NULL');
        $this->addSql('ALTER TABLE suivi_traitement ADD heure_effective TIME DEFAULT NULL, ADD commentaires LONGTEXT DEFAULT NULL, ADD created_at DATETIME NOT NULL, DROP dateSaisie, DROP heurePrevue, DROP heureEffective, DROP observations, DROP observationsPsy, DROP ressenti, DROP saisiPar, DROP createdAt, CHANGE effectue effectue TINYINT NOT NULL, CHANGE valide valide TINYINT NOT NULL, CHANGE suivitraitement_id suivi_id INT AUTO_INCREMENT NOT NULL, CHANGE dateSuivi date_suivi DATE NOT NULL, CHANGE updatedAt updated_at DATETIME DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (suivi_id)');
        $this->addSql('DROP INDEX idx_805681836edd0f43 ON suivi_traitement');
        $this->addSql('CREATE INDEX IDX_80568183DDA344B6 ON suivi_traitement (traitement_id)');
        $this->addSql('ALTER TABLE suivi_traitement ADD CONSTRAINT FK_805681836EDD0F43 FOREIGN KEY (Traitement_id) REFERENCES traitement (traitement_id)');
    }
}
