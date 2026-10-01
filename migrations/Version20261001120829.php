<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001120829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE school_assignment (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, raw_input VARCHAR(255) NOT NULL, city VARCHAR(100) DEFAULT NULL, status VARCHAR(20) NOT NULL, score DOUBLE PRECISION NOT NULL, candidates JSON NOT NULL, user_id INT NOT NULL, school_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_8564C448A76ED395 (user_id), INDEX idx_school_assignment_status (status), INDEX IDX_8564C448C32A47EE (school_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, email VARCHAR(180) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE school_assignment ADD CONSTRAINT FK_8564C448A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE school_assignment ADD CONSTRAINT FK_8564C448C32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE school_assignment DROP FOREIGN KEY FK_8564C448A76ED395');
        $this->addSql('ALTER TABLE school_assignment DROP FOREIGN KEY FK_8564C448C32A47EE');
        $this->addSql('DROP TABLE school_assignment');
        $this->addSql('DROP TABLE `user`');
    }
}
