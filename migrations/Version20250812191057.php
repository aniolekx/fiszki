<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250812191057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE generation_session (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, deck_id INT DEFAULT NULL, input_text LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, generated_flashcards JSON DEFAULT NULL, accepted_flashcards JSON DEFAULT NULL, tokens_used INT DEFAULT NULL, cost_usd NUMERIC(10, 4) DEFAULT NULL, model VARCHAR(50) DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_2F1DBEB1A76ED395 (user_id), INDEX IDX_2F1DBEB1111948DC (deck_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE generation_session ADD CONSTRAINT FK_2F1DBEB1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE generation_session ADD CONSTRAINT FK_2F1DBEB1111948DC FOREIGN KEY (deck_id) REFERENCES deck (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE generation_session DROP FOREIGN KEY FK_2F1DBEB1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE generation_session DROP FOREIGN KEY FK_2F1DBEB1111948DC
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE generation_session
        SQL);
    }
}
