<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250813113302 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE ai_usage_logs (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, generation_session_id INT DEFAULT NULL, tokens_used INT NOT NULL, credits_charged INT NOT NULL, model VARCHAR(50) NOT NULL, estimated_cost NUMERIC(10, 4) DEFAULT NULL, prompt LONGTEXT DEFAULT NULL, response LONGTEXT DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_3A038613A76ED395 (user_id), INDEX IDX_3A038613BB9DBED7 (generation_session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE credit_transactions (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, performed_by_id INT DEFAULT NULL, type VARCHAR(50) NOT NULL, amount INT NOT NULL, balance_after INT NOT NULL, description VARCHAR(255) DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_CC5D0006A76ED395 (user_id), INDEX IDX_CC5D00062E65C292 (performed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE system_settings (id INT AUTO_INCREMENT NOT NULL, updated_by_id INT DEFAULT NULL, setting_key VARCHAR(100) NOT NULL, value LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8CAF11475FA1E697 (setting_key), INDEX IDX_8CAF1147896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_credits (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, balance INT NOT NULL, total_earned INT NOT NULL, total_spent INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_191ACF74A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_usage_logs ADD CONSTRAINT FK_3A038613A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_usage_logs ADD CONSTRAINT FK_3A038613BB9DBED7 FOREIGN KEY (generation_session_id) REFERENCES generation_session (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE credit_transactions ADD CONSTRAINT FK_CC5D0006A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE credit_transactions ADD CONSTRAINT FK_CC5D00062E65C292 FOREIGN KEY (performed_by_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE system_settings ADD CONSTRAINT FK_8CAF1147896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_credits ADD CONSTRAINT FK_191ACF74A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD created_at DATETIME DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_usage_logs DROP FOREIGN KEY FK_3A038613A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ai_usage_logs DROP FOREIGN KEY FK_3A038613BB9DBED7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE credit_transactions DROP FOREIGN KEY FK_CC5D0006A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE credit_transactions DROP FOREIGN KEY FK_CC5D00062E65C292
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE system_settings DROP FOREIGN KEY FK_8CAF1147896DBBDE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_credits DROP FOREIGN KEY FK_191ACF74A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ai_usage_logs
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE credit_transactions
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE system_settings
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_credits
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users DROP created_at
        SQL);
    }
}
