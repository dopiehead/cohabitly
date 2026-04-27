<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260302075646 extends AbstractMigration
{
    public function getDescription(): string { return 'Superseded by Version20260228145653'; }
    public function up(Schema $schema): void {}
    public function down(Schema $schema): void {}
}
