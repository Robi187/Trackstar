<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260428062005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE content DROP CONSTRAINT fk_fec530a99a7c0b81');
        $this->addSql('DROP INDEX idx_fec530a99a7c0b81');
        $this->addSql('ALTER TABLE content DROP fk_tag_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE content ADD fk_tag_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE content ADD CONSTRAINT fk_fec530a99a7c0b81 FOREIGN KEY (fk_tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_fec530a99a7c0b81 ON content (fk_tag_id)');
    }
}
