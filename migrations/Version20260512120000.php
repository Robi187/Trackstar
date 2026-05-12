<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add comment replies (fk_parent_comment) and comment_like table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment ADD fk_parent_comment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_comment_parent FOREIGN KEY (fk_parent_comment_id) REFERENCES comment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_comment_parent ON comment (fk_parent_comment_id)');

        $this->addSql('CREATE TABLE comment_like (fk_user_id INT NOT NULL, fk_comment_id INT NOT NULL, PRIMARY KEY(fk_user_id, fk_comment_id))');
        $this->addSql('CREATE INDEX IDX_comment_like_user ON comment_like (fk_user_id)');
        $this->addSql('CREATE INDEX IDX_comment_like_comment ON comment_like (fk_comment_id)');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT FK_comment_like_user FOREIGN KEY (fk_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT FK_comment_like_comment FOREIGN KEY (fk_comment_id) REFERENCES comment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment_like DROP CONSTRAINT FK_comment_like_user');
        $this->addSql('ALTER TABLE comment_like DROP CONSTRAINT FK_comment_like_comment');
        $this->addSql('DROP TABLE comment_like');

        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_comment_parent');
        $this->addSql('DROP INDEX IDX_comment_parent');
        $this->addSql('ALTER TABLE comment DROP fk_parent_comment_id');
    }
}
