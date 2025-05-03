# Fiszki

Projekt **Fiszki 10xDevs** to aplikacja webowa do tworzenia, przeglądania i powtarzania fiszek edukacyjnych. Umożliwia ręczne tworzenie fiszek oraz automatyczne generowanie pytań i odpowiedzi przy pomocy modelu LLM.

## Tech Stack

- PHP 8.3.4
- Symfony 6.4.21 (LTS)
- Symfony Live Components
- Bootstrap 5.3.5
- MySQL 8.0.39
- Docker & Docker Compose

## Funkcjonalności

1. Automatyczne generowanie fiszek przy użyciu AI:
   - Wklej fragment tekstu (1000–10 000 znaków).
   - Wygeneruj propozycje pytań i odpowiedzi.
   - Zatwierdź, edytuj lub odrzuć każdą propozycję.
2. Ręczne tworzenie i zarządzanie fiszkami:
   - Dodawanie, edycja, usuwanie fiszek.
3. Sesja nauki z algorytmem powtórek (spaced repetition).
4. Uwierzytelnianie i autoryzacja użytkowników.
5. Statystyki generowania i akceptacji fiszek.

## Instalacja i uruchamianie lokalne

1. Sklonuj repozytorium:
   ```bash
   git clone https://github.com/<org>/fiszki.git
   cd fiszki
   ```
2. Uruchom kontenery Docker:
   ```bash
   docker-compose up -d --build
   ```
3. Zainstaluj zależności PHP:
   ```bash
   docker-compose exec php-fpm composer install
   ```
4. Wygeneruj klucz aplikacji i uruchom migracje:
   ```bash
   docker-compose exec php-fpm php bin/console doctrine:migrations:migrate
   ```
5. Aplikacja dostępna pod adresem `http://localhost:8000`.

## Testy i analiza jakości

- Statyczna analiza kodu:  `docker-compose exec php-fpm vendor/bin/phpstan` (poziom 9)
- Unit / spec tests:        `docker-compose exec php-fpm vendor/bin/phpunit` / `vendor/bin/phpspec`
- BDD (Behat):               `docker-compose exec php-fpm vendor/bin/behat`  

CI/CD: GitHub Actions uruchamia kolejno: composer validate, PHPStan, PHPUnit, Behat, buduje i wypycha obraz Docker.

## Workflow BDD + DDD

Projekt oparty na cyklu _"Red → Design → Green → Refactor"_ z użyciem Gherkina (Behat) i Domain-Driven Design. Szczegóły w pliku `.ai/bdd_workflow.md`.

## Kontrybucja

1. Stwórz branch `feat/<kontekst>/<krótki-opis>`.
2. Dodaj scenariusze `.feature` w odpowiednim katalogu `tests/Behat`.
3. Przeprowadź TDD dla warstwy Domain (PHPUnit/phpspec) i implementację aplikacyjną.
4. Uruchom wszystkie testy przed PR: `phpstan`, `phpunit`, `behat`.
5. W PR używaj Conventional Commits (`feat:`, `fix:`, `test:`, …).

---

> Przyjemnego kodowania! 👩‍💻👨‍💻
