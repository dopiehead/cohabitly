<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260302075646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feature_lists (id INT AUTO_INCREMENT NOT NULL, more_features JSON NOT NULL, bills JSON DEFAULT NULL, house_rules JSON NOT NULL, created_at DATETIME NOT NULL, property_id INT NOT NULL, INDEX IDX_C17895AC549213EC (property_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE feature_lists ADD CONSTRAINT FK_C17895AC549213EC FOREIGN KEY (property_id) REFERENCES property_lists (id)');
        $this->addSql('ALTER TABLE property_lists CHANGE property_images property_images JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feature_lists DROP FOREIGN KEY FK_C17895AC549213EC');
        $this->addSql('DROP TABLE feature_lists');
        $this->addSql('ALTER TABLE property_lists CHANGE property_images property_images TEXT DEFAULT NULL');
    }
}
