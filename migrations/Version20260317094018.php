<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260317094018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE content DROP CONSTRAINT fk_fec530a97bb031d6');
        $this->addSql('DROP INDEX idx_fec530a97bb031d6');
        $this->addSql('ALTER TABLE content ADD fk_tag_id INT NOT NULL');
        $this->addSql('ALTER TABLE content DROP type');
        $this->addSql('ALTER TABLE content RENAME COLUMN fk_category_id TO type_id');
        $this->addSql('ALTER TABLE content ADD CONSTRAINT FK_FEC530A9C54C8C93 FOREIGN KEY (type_id) REFERENCES category (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE content ADD CONSTRAINT FK_FEC530A99A7C0B81 FOREIGN KEY (fk_tag_id) REFERENCES tag (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_FEC530A9C54C8C93 ON content (type_id)');
        $this->addSql('CREATE INDEX IDX_FEC530A99A7C0B81 ON content (fk_tag_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE content DROP CONSTRAINT FK_FEC530A9C54C8C93');
        $this->addSql('ALTER TABLE content DROP CONSTRAINT FK_FEC530A99A7C0B81');
        $this->addSql('DROP INDEX IDX_FEC530A9C54C8C93');
        $this->addSql('DROP INDEX IDX_FEC530A99A7C0B81');
        $this->addSql('ALTER TABLE content ADD type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE content ADD fk_category_id INT NOT NULL');
        $this->addSql('ALTER TABLE content DROP type_id');
        $this->addSql('ALTER TABLE content DROP fk_tag_id');
        $this->addSql('ALTER TABLE content ADD CONSTRAINT fk_fec530a97bb031d6 FOREIGN KEY (fk_category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_fec530a97bb031d6 ON content (fk_category_id)');
    }
}
