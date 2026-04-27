<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228145653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema: user, property_lists, messages, user_notification, feature_lists, subscription';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS user (
            id INT AUTO_INCREMENT NOT NULL,
            public_id VARCHAR(255) NOT NULL,
            user_name VARCHAR(255) NOT NULL,
            user_email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            roles JSON NOT NULL,
            user_location VARCHAR(255) DEFAULT NULL,
            user_address VARCHAR(255) DEFAULT NULL,
            user_image LONGTEXT DEFAULT NULL,
            verified TINYINT NOT NULL DEFAULT 0,
            vkey LONGTEXT DEFAULT NULL,
            user_dob DATE DEFAULT NULL,
            user_phone VARCHAR(20) DEFAULT NULL,
            lga VARCHAR(100) DEFAULT NULL,
            user_rating DOUBLE PRECISION NOT NULL DEFAULT 0,
            user_gender VARCHAR(10) DEFAULT NULL,
            user_likes INT NOT NULL DEFAULT 0,
            user_shares INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_8D93D649B5B48B91 (public_id),
            UNIQUE INDEX UNIQ_8D93D649550872C (user_email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('CREATE TABLE IF NOT EXISTS property_lists (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            location VARCHAR(100) NOT NULL,
            property_images JSON DEFAULT NULL,
            lga VARCHAR(100) DEFAULT NULL,
            price NUMERIC(10, 2) NOT NULL,
            rooms INT NOT NULL DEFAULT 1,
            bathrooms INT NOT NULL DEFAULT 1,
            featured TINYINT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            owner_id INT NOT NULL,
            INDEX IDX_CE355EA87E3C61F9 (owner_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE property_lists ADD CONSTRAINT FK_CE355EA87E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');

        $this->addSql('CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT NOT NULL,
            sender_email VARCHAR(255) NOT NULL,
            receiver_email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            has_read TINYINT DEFAULT 0 NOT NULL,
            is_sender_deleted TINYINT DEFAULT 0 NOT NULL,
            is_receiver_deleted TINYINT DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('CREATE TABLE IF NOT EXISTS user_notification (
            id INT AUTO_INCREMENT NOT NULL,
            sender_id INT NOT NULL,
            recipient_id INT NOT NULL,
            message VARCHAR(255) NOT NULL,
            pending TINYINT NOT NULL DEFAULT 1,
            date DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('CREATE TABLE IF NOT EXISTS feature_lists (
            id INT AUTO_INCREMENT NOT NULL,
            more_features JSON NOT NULL,
            bills JSON DEFAULT NULL,
            house_rules JSON NOT NULL,
            created_at DATETIME NOT NULL,
            property_id INT NOT NULL,
            INDEX IDX_C17895AC549213EC (property_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE feature_lists ADD CONSTRAINT FK_C17895AC549213EC FOREIGN KEY (property_id) REFERENCES property_lists (id)');

        $this->addSql('CREATE TABLE IF NOT EXISTS subscription (
            id INT AUTO_INCREMENT NOT NULL,
            price DOUBLE PRECISION NOT NULL,
            user_id INT NOT NULL,
            user_type VARCHAR(255) NOT NULL,
            reference LONGTEXT NOT NULL,
            duration INT NOT NULL,
            expiry_date VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS feature_lists');
        $this->addSql('DROP TABLE IF EXISTS subscription');
        $this->addSql('DROP TABLE IF EXISTS messages');
        $this->addSql('DROP TABLE IF EXISTS user_notification');
        $this->addSql('DROP TABLE IF EXISTS property_lists');
        $this->addSql('DROP TABLE IF EXISTS user');
    }
}
