<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251002092338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD bio LONGTEXT DEFAULT NULL, ADD avatar_name VARCHAR(255) DEFAULT NULL, ADD avatar_updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD location VARCHAR(100) DEFAULT NULL, ADD website VARCHAR(255) DEFAULT NULL, ADD joined_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        
        // Update existing users to have a joined_at date (set to now)
        $this->addSql('UPDATE user SET joined_at = NOW() WHERE joined_at IS NULL');
        
        // Now make joined_at NOT NULL
        $this->addSql('ALTER TABLE user MODIFY joined_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP bio, DROP avatar_name, DROP avatar_updated_at, DROP location, DROP website, DROP joined_at');
    }
}
