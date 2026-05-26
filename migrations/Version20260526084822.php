<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260526084822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX idx_comment_parent RENAME TO IDX_9474526C711D77BB');
        $this->addSql('ALTER INDEX idx_comment_like_user RENAME TO IDX_8A55E25F5741EEB9');
        $this->addSql('ALTER INDEX idx_comment_like_comment RENAME TO IDX_8A55E25F807B780');
        $this->addSql('ALTER TABLE license ADD description VARCHAR(512) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX idx_9474526c711d77bb RENAME TO idx_comment_parent');
        $this->addSql('ALTER INDEX idx_8a55e25f807b780 RENAME TO idx_comment_like_comment');
        $this->addSql('ALTER INDEX idx_8a55e25f5741eeb9 RENAME TO idx_comment_like_user');
        $this->addSql('ALTER TABLE license DROP description');
    }
}
