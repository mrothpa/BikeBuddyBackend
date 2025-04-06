<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250406114144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE solutions (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', user_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', problem_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', description LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_A90F77EA76ED395 (user_id), INDEX IDX_A90F77EA0DCED86 (problem_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE solutions ADD CONSTRAINT FK_A90F77EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE solutions ADD CONSTRAINT FK_A90F77EA0DCED86 FOREIGN KEY (problem_id) REFERENCES problems (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE solutions DROP FOREIGN KEY FK_A90F77EA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE solutions DROP FOREIGN KEY FK_A90F77EA0DCED86
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE solutions
        SQL);
    }
}
