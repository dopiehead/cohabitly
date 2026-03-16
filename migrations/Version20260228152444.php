<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228152444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD user_dob DATE DEFAULT NULL, ADD user_phone VARCHAR(20) DEFAULT NULL, ADD lga VARCHAR(100) DEFAULT NULL, ADD user_rating DOUBLE PRECISION NOT NULL, ADD user_gender VARCHAR(10) DEFAULT NULL, ADD user_likes INT NOT NULL, ADD user_shares INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP user_dob, DROP user_phone, DROP lga, DROP user_rating, DROP user_gender, DROP user_likes, DROP user_shares');
    }
}
