<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250328231145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE problems DROP FOREIGN KEY FK_8E6662459D86650F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8E6662459D86650F ON problems
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE problems ADD user_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', DROP user_id_id, CHANGE id id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE problems ADD CONSTRAINT FK_8E666245A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8E666245A76ED395 ON problems (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE id id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)'
        SQL);
        $this->addSql('ALTER TABLE problems DROP FOREIGN KEY FK_8E6662459D86650F');
        $this->addSql('ALTER TABLE problems DROP COLUMN user_id_id');
        $this->addSql('ALTER TABLE problems ADD user_id BINARY(16) NOT NULL'); // UUID ist 16 Byte
        $this->addSql('ALTER TABLE problems ADD CONSTRAINT FK_8E666245A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE problems DROP FOREIGN KEY FK_8E666245A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8E666245A76ED395 ON problems
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE problems ADD user_id_id INT NOT NULL, DROP user_id, CHANGE id id INT AUTO_INCREMENT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE problems ADD CONSTRAINT FK_8E6662459D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8E6662459D86650F ON problems (user_id_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE id id INT AUTO_INCREMENT NOT NULL
        SQL);
        $this->addSql('ALTER TABLE problems DROP FOREIGN KEY FK_8E666245A76ED395');
        $this->addSql('ALTER TABLE problems DROP COLUMN user_id');
        $this->addSql('ALTER TABLE problems ADD user_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE problems ADD CONSTRAINT FK_8E6662459D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)');    
    }
}
