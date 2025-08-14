<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250813091245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE flashcard_progress (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, flashcard_id INT NOT NULL, study_session_id INT DEFAULT NULL, repetitions INT NOT NULL, ease_factor DOUBLE PRECISION NOT NULL, `interval` INT NOT NULL, last_reviewed_at DATETIME NOT NULL, next_review_at DATETIME NOT NULL, consecutive_correct INT NOT NULL, total_attempts INT NOT NULL, correct_attempts INT NOT NULL, last_quality INT DEFAULT NULL, INDEX IDX_C399C35CA76ED395 (user_id), INDEX IDX_C399C35CC5D16576 (flashcard_id), INDEX IDX_C399C35CE6A388BF (study_session_id), UNIQUE INDEX unique_user_flashcard (user_id, flashcard_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE study_session (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, deck_id INT NOT NULL, started_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, total_cards INT NOT NULL, reviewed_cards INT NOT NULL, correct_answers INT NOT NULL, status VARCHAR(20) NOT NULL, INDEX IDX_E55128B6A76ED395 (user_id), INDEX IDX_E55128B6111948DC (deck_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE flashcard_progress ADD CONSTRAINT FK_C399C35CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE flashcard_progress ADD CONSTRAINT FK_C399C35CC5D16576 FOREIGN KEY (flashcard_id) REFERENCES flashcard (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE flashcard_progress ADD CONSTRAINT FK_C399C35CE6A388BF FOREIGN KEY (study_session_id) REFERENCES study_session (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE study_session ADD CONSTRAINT FK_E55128B6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE study_session ADD CONSTRAINT FK_E55128B6111948DC FOREIGN KEY (deck_id) REFERENCES deck (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE flashcard_progress DROP FOREIGN KEY FK_C399C35CA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE flashcard_progress DROP FOREIGN KEY FK_C399C35CC5D16576
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE flashcard_progress DROP FOREIGN KEY FK_C399C35CE6A388BF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE study_session DROP FOREIGN KEY FK_E55128B6A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE study_session DROP FOREIGN KEY FK_E55128B6111948DC
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE flashcard_progress
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE study_session
        SQL);
    }
}
