<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250406123822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE upvotes (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', user_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)', problem_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_BFB7A0E1A76ED395 (user_id), INDEX IDX_BFB7A0E1A0DCED86 (problem_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE upvotes ADD CONSTRAINT FK_BFB7A0E1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE upvotes ADD CONSTRAINT FK_BFB7A0E1A0DCED86 FOREIGN KEY (problem_id) REFERENCES problems (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE upvotes DROP FOREIGN KEY FK_BFB7A0E1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE upvotes DROP FOREIGN KEY FK_BFB7A0E1A0DCED86
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE upvotes
        SQL);
    }
}
