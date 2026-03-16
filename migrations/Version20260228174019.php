<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228174019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE property_lists (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, location VARCHAR(100) NOT NULL, property_images JSON DEFAULT NULL, lga VARCHAR(100) DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, rooms INT NOT NULL, bathrooms INT NOT NULL, featured TINYINT NOT NULL, created_at DATETIME NOT NULL, owner_id INT NOT NULL, INDEX IDX_CE355EA87E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE property_lists ADD CONSTRAINT FK_CE355EA87E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE property_lists DROP FOREIGN KEY FK_CE355EA87E3C61F9');
        $this->addSql('DROP TABLE property_lists');
    }
}
