<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create license table, seed data, replace content.license string with FK';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE license (
            id SERIAL NOT NULL,
            short_code VARCHAR(20) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_license_short_code ON license (short_code)');

        $this->addSql("INSERT INTO license (short_code, full_name) VALUES
            ('CC BY',       'CC BY – Namensnennung 4.0 International'),
            ('CC BY-SA',    'CC BY-SA – Namensnennung-Share Alike 4.0 International'),
            ('CC BY-ND',    'CC BY-ND – Namensnennung-Keine Bearbeitungen 4.0 International'),
            ('CC BY-NC',    'CC BY-NC – Namensnennung-Nicht kommerziell 4.0 International'),
            ('CC BY-NC-SA', 'CC BY-NC-SA – Namensnennung-Nicht kommerziell-Share Alike 4.0 International'),
            ('CC BY-NC-ND', 'CC BY-NC-ND – Namensnennung-Nicht kommerziell-Keine Bearbeitungen 4.0 International')
        ");

        // Bestehende content.license Strings auf die neue FK migrieren
        $this->addSql('ALTER TABLE content ADD COLUMN license_id INT DEFAULT NULL');
        $this->addSql('UPDATE content SET license_id = (SELECT id FROM license WHERE short_code = content.license) WHERE content.license IS NOT NULL');
        $this->addSql('ALTER TABLE content DROP COLUMN license');
        $this->addSql('ALTER TABLE content ADD CONSTRAINT FK_content_license FOREIGN KEY (license_id) REFERENCES license (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_content_license_id ON content (license_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE content DROP CONSTRAINT FK_content_license');
        $this->addSql('ALTER TABLE content DROP COLUMN license_id');
        $this->addSql('ALTER TABLE content ADD COLUMN license VARCHAR(20) DEFAULT NULL');
        $this->addSql('DROP TABLE license');
    }
}
