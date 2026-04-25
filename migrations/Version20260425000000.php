<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Coabit v2: Application, Profile, Conversation; updated PropertyList, UserNotification, Message, User';
    }

    public function up(Schema $schema): void
    {
        // ── User: new columns ──
        $this->addSql('ALTER TABLE user ADD COLUMN refresh_token LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN reset_token LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN reset_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user ADD COLUMN user_role VARCHAR(10) DEFAULT NULL');

        // ── PropertyList: new columns ──
        $this->addSql('ALTER TABLE property_lists ADD COLUMN state VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE property_lists ADD COLUMN type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE property_lists ADD COLUMN toilets INT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE property_lists ADD COLUMN parking_space TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE property_lists ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT \'draft\'');
        $this->addSql('ALTER TABLE property_lists ADD COLUMN available_from DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE property_lists ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

        // ── Profile ──
        $this->addSql('CREATE TABLE profiles (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            full_name VARCHAR(255) DEFAULT NULL,
            phone_number VARCHAR(20) DEFAULT NULL,
            date_of_birth DATE DEFAULT NULL,
            gender VARCHAR(30) DEFAULT NULL,
            occupation VARCHAR(100) DEFAULT NULL,
            employment_status VARCHAR(30) DEFAULT NULL,
            monthly_income_range VARCHAR(30) DEFAULT NULL,
            bio LONGTEXT DEFAULT NULL,
            photo_url LONGTEXT DEFAULT NULL,
            is_complete TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX uniq_profile_user (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE profiles ADD CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // ── Application ──
        $this->addSql('CREATE TABLE applications (
            id INT AUTO_INCREMENT NOT NULL,
            listing_id INT NOT NULL,
            applicant_id INT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX unique_listing_applicant (listing_id, applicant_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE applications ADD CONSTRAINT fk_application_listing FOREIGN KEY (listing_id) REFERENCES property_lists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE applications ADD CONSTRAINT fk_application_applicant FOREIGN KEY (applicant_id) REFERENCES user (id) ON DELETE CASCADE');

        // ── Conversation ──
        $this->addSql('CREATE TABLE conversations (
            id INT AUTO_INCREMENT NOT NULL,
            listing_id INT NOT NULL,
            owner_id INT NOT NULL,
            applicant_id INT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX unique_conversation (listing_id, owner_id, applicant_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE conversations ADD CONSTRAINT fk_conversation_listing FOREIGN KEY (listing_id) REFERENCES property_lists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversations ADD CONSTRAINT fk_conversation_owner FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversations ADD CONSTRAINT fk_conversation_applicant FOREIGN KEY (applicant_id) REFERENCES user (id) ON DELETE CASCADE');

        // ── UserNotification: drop old columns, add new ──
        $this->addSql('ALTER TABLE user_notification DROP COLUMN sender_id');
        $this->addSql('ALTER TABLE user_notification DROP COLUMN recipient_id');
        $this->addSql('ALTER TABLE user_notification DROP COLUMN message');
        $this->addSql('ALTER TABLE user_notification DROP COLUMN pending');
        $this->addSql('ALTER TABLE user_notification DROP COLUMN date');
        $this->addSql('ALTER TABLE user_notification ADD COLUMN user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_notification ADD COLUMN type VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE user_notification ADD COLUMN payload JSON NOT NULL');
        $this->addSql('ALTER TABLE user_notification ADD COLUMN `read` TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_notification ADD COLUMN created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_notification ADD CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // ── Message: rebuild for conversation-based model ──
        $this->addSql('DROP TABLE messages');
        $this->addSql('CREATE TABLE messages (
            id INT AUTO_INCREMENT NOT NULL,
            conversation_id INT NOT NULL,
            sender_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            `read` TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT fk_message_conversation FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT fk_message_sender FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE messages');
        $this->addSql('DROP TABLE conversations');
        $this->addSql('DROP TABLE applications');
        $this->addSql('DROP TABLE profiles');

        $this->addSql('ALTER TABLE user_notification DROP FOREIGN KEY fk_notification_user');
        $this->addSql('ALTER TABLE user_notification DROP COLUMN user_id');
        $this->addSql('ALTER TABLE user_notification DROP COLUMN type');
        $this->addSql('ALTER TABLE user_notification DROP COLUMN payload');
        $this->addSql('ALTER TABLE user_notification DROP COLUMN `read`');
        $this->addSql('ALTER TABLE user_notification DROP COLUMN created_at');
        $this->addSql('ALTER TABLE user_notification ADD COLUMN sender_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_notification ADD COLUMN recipient_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_notification ADD COLUMN message VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user_notification ADD COLUMN pending TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE user_notification ADD COLUMN date DATETIME NOT NULL');

        $this->addSql('ALTER TABLE property_lists DROP COLUMN state');
        $this->addSql('ALTER TABLE property_lists DROP COLUMN type');
        $this->addSql('ALTER TABLE property_lists DROP COLUMN toilets');
        $this->addSql('ALTER TABLE property_lists DROP COLUMN parking_space');
        $this->addSql('ALTER TABLE property_lists DROP COLUMN status');
        $this->addSql('ALTER TABLE property_lists DROP COLUMN available_from');
        $this->addSql('ALTER TABLE property_lists DROP COLUMN updated_at');

        $this->addSql('ALTER TABLE user DROP COLUMN refresh_token');
        $this->addSql('ALTER TABLE user DROP COLUMN reset_token');
        $this->addSql('ALTER TABLE user DROP COLUMN reset_token_expires_at');
        $this->addSql('ALTER TABLE user DROP COLUMN user_role');
    }
}
