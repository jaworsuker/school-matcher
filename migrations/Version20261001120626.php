<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001120626 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE school (id INT AUTO_INCREMENT NOT NULL, official_name VARCHAR(255) NOT NULL, city VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, UNIQUE INDEX uniq_school_name_city (official_name, city), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE school_alias (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, school_id INT NOT NULL, INDEX IDX_3F96DD8EC32A47EE (school_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE school_alias ADD CONSTRAINT FK_3F96DD8EC32A47EE FOREIGN KEY (school_id) REFERENCES school (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE school_alias DROP FOREIGN KEY FK_3F96DD8EC32A47EE');
        $this->addSql('DROP TABLE school');
        $this->addSql('DROP TABLE school_alias');
    }
}
