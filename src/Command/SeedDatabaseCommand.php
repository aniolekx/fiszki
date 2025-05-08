<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Entity\Deck;
use App\Entity\Flashcard;
use App\Repository\DoctrineUserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;

#[AsCommand(
    name: 'app:seed-database',
    description: 'Czyści bazę danych i dodaje przykładowe dane.',
)]
class SeedDatabaseCommand extends Command
{
    public function __construct(
        private readonly DoctrineUserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $faker = Factory::create('pl_PL');
        
        try {
            // Czyszczenie bazy danych
            $io->section('Czyszczenie bazy danych...');
            $this->clearDatabase();
            $io->success('Baza danych została wyczyszczona.');

            // Tworzenie użytkownika
            $io->section('Tworzenie użytkownika...');
            $email = 'aniolekx@gmail.com';
            $password = 'correctPassword';

            $user = new User($email);

            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $password
            );
            $user->setPassword($hashedPassword);

            // Set confirmation token and confirmed status
            $confirmationToken = Uuid::v4()->toRfc4122();
            $user->setConfirmationToken($confirmationToken);
            $user->setIsConfirmed(true);

            // Persist the user
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $io->success(sprintf('Użytkownik "%s" został pomyślnie utworzony.', $email));
            $io->info(sprintf('Token potwierdzający: %s', $confirmationToken));

            // Tworzenie przykładowych talii
            $io->section('Tworzenie przykładowych talii...');
            $io->text('Rozpoczynam tworzenie talii...');
            
            try {
                $this->createSampleDecks($user, $faker, $io);
                $io->success('Wszystkie talie zostały utworzone.');
            } catch (\Throwable $e) {
                $io->error('Błąd podczas tworzenia talii:');
                $io->error($e->getMessage());
                $io->error($e->getTraceAsString());
                throw $e;
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error(sprintf('Nie udało się utworzyć danych: %s', $e->getMessage()));
            $io->error(sprintf('Stack trace: %s', $e->getTraceAsString()));
            return Command::FAILURE;
        }
    }

    private function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();

        // Wyłączamy sprawdzanie kluczy obcych
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        // Usuwamy dane z każdej tabeli
        $connection->executeStatement('TRUNCATE TABLE users');
        $connection->executeStatement('TRUNCATE TABLE deck');
        $connection->executeStatement('TRUNCATE TABLE flashcard');
        $connection->executeStatement('TRUNCATE TABLE doctrine_migration_versions');

        // Włączamy sprawdzanie kluczy obcych
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function createSampleDecks(User $user, \Faker\Generator $faker, SymfonyStyle $io): void
    {
        $io->text('Wewnątrz createSampleDecks...');
        
        $deckNames = [
            'Angielski - Podstawowe słówka',
            'Niemiecki - Czasowniki',
            'Hiszpański - Zwroty codzienne',
            'Francuski - Przymiotniki',
            'Włoski - Liczby i kolory'
        ];

        $io->text(sprintf('Znaleziono %d talii do utworzenia', count($deckNames)));

        foreach ($deckNames as $deckName) {
            try {
                $io->text(sprintf('Tworzenie talii "%s"...', $deckName));
                
                $deck = new Deck();
                $deck->setName($deckName);
                $deck->setUser($user);
                $deck->setDescription($faker->sentence());

                $this->entityManager->persist($deck);
                $io->text('Talia została utworzona.');

                // Dodajemy fiszki do talii
                $numberOfFlashcards = $faker->numberBetween(5, 10);
                $io->text(sprintf('Dodawanie %d fiszek...', $numberOfFlashcards));
                
                for ($i = 0; $i < $numberOfFlashcards; $i++) {
                    $flashcard = new Flashcard();
                    $flashcard->setFront($faker->word());
                    $flashcard->setBack($faker->word());
                    $flashcard->setDeck($deck);
                    $this->entityManager->persist($flashcard);
                }

                $this->entityManager->flush();
                $io->info(sprintf('Utworzono talię "%s" z %d fiszkami', $deckName, $numberOfFlashcards));
            } catch (\Throwable $e) {
                $io->error(sprintf('Nie udało się utworzyć talii "%s": %s', $deckName, $e->getMessage()));
                $io->error(sprintf('Stack trace: %s', $e->getTraceAsString()));
                throw $e;
            }
        }

        $io->text('Zakończono tworzenie talii.');
    }
} 