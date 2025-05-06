<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250506145556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dodanie kolumn created_at i updated_at do tabeli deck';
    }

    public function up(Schema $schema): void
    {
        // Najpierw dodajemy kolumny jako nullable
        $this->addSql('ALTER TABLE deck ADD created_at DATETIME NULL, ADD updated_at DATETIME NULL');
        
        // Ustawiamy wartości domyślne dla istniejących rekordów
        $this->addSql('UPDATE deck SET created_at = NOW(), updated_at = NOW()');
        
        // Zmieniamy kolumny na NOT NULL
        $this->addSql('ALTER TABLE deck MODIFY created_at DATETIME NOT NULL, MODIFY updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE deck DROP created_at, DROP updated_at');
    }
}
