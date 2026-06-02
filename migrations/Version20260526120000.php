<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ON DELETE CASCADE to fk_content (and report.fk_comment) constraints so content deletion cascades to comments, ratings, favorites, tags, and reports.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526C74CE697E');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C74CE697E FOREIGN KEY (fk_content_id) REFERENCES content (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE rating DROP CONSTRAINT FK_D889262274CE697E');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D889262274CE697E FOREIGN KEY (fk_content_id) REFERENCES content (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE favorite DROP CONSTRAINT FK_68C58ED974CE697E');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED974CE697E FOREIGN KEY (fk_content_id) REFERENCES content (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE content_tag DROP CONSTRAINT FK_B662E17674CE697E');
        $this->addSql('ALTER TABLE content_tag ADD CONSTRAINT FK_B662E17674CE697E FOREIGN KEY (fk_content_id) REFERENCES content (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F778474CE697E');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F778474CE697E FOREIGN KEY (fk_content_id) REFERENCES content (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F7784807B780');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784807B780 FOREIGN KEY (fk_comment_id) REFERENCES comment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526C74CE697E');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C74CE697E FOREIGN KEY (fk_content_id) REFERENCES content (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE rating DROP CONSTRAINT FK_D889262274CE697E');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D889262274CE697E FOREIGN KEY (fk_content_id) REFERENCES content (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE favorite DROP CONSTRAINT FK_68C58ED974CE697E');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED974CE697E FOREIGN KEY (fk_content_id) REFERENCES content (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE content_tag DROP CONSTRAINT FK_B662E17674CE697E');
        $this->addSql('ALTER TABLE content_tag ADD CONSTRAINT FK_B662E17674CE697E FOREIGN KEY (fk_content_id) REFERENCES content (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F778474CE697E');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F778474CE697E FOREIGN KEY (fk_content_id) REFERENCES content (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F7784807B780');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784807B780 FOREIGN KEY (fk_comment_id) REFERENCES comment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
