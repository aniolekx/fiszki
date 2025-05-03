# 📚 Development Workflow Guide (BDD + DDD) – _Flashcards_ Project  
> _Target reader: Cursor AI assistant **and** human developers_

---

## 0. Project overview
Workflow w projekcie Flashcards został zaprojektowany z myślą o jednoosobowym zespole, więc cały cykl od analizy po wdrożenie realizowany jest przez jednego dewelopera. Praca zaczyna się od przygotowania scenariuszy .feature w Gherkinie na podstawie przykładów i założeń — pełniąc rolę zarówno Product Ownera, jak i analityka. Następnie uruchamiane są testy Behat, które muszą początkowo nie przejść (Red), co potwierdza, że nowe zachowanie jeszcze nie zostało zaimplementowane. Dalej projektowana jest logika dziedzinowa (Design), czyli czysty kod PHP bez zależności od frameworka, oparty na DDD. Gdy model jest gotowy, tworzona jest implementacja aplikacyjna i infrastrukturalna, aż scenariusze Behat przechodzą (Green). Ostatni etap to refaktoryzacja, zatwierdzenie jakości przez samego siebie oraz automatyczne wdrożenie po przejściu testów w CI.

## 1. Principles

| Topic | Guideline |
|-------|-----------|
| **Method** | • _Behaviour-Driven Development_ for every new behaviour.<br>• _Domain-Driven Design_ to model the problem space – each **bounded context** lives in its own Symfony bundle. |
| **Stack** | Symfony 6.4 (LTS), PHP 8.3, Live Components, Bootstrap 5, MySQL 8, Behat v4, PHPUnit/phpspec, Symfony Messenger. |
| **CI/CD** | GitHub Actions → run _composer validate → PHPStan → PHPUnit → Behat_ → build & deploy Docker images. |
| **Branching** | `main` = always deployable. Feature work → `feat/<context>/<short-slug>` using trunk-based flow (PR < 400 LOC). |
| **Commits** | Conventional Commits (`feat:`, `fix:`, `test:`, …). |
| **Code reviews** | Mandatory; reviewer checks **green Behat** + domain vocabulary. |
| **Artefacts** | Everything user-visible is first captured in Gherkin. No Jira tickets without a matching `.feature`. |

---

## 2. Folder layout

```
src/
 ├─ IdentityAccess/        # context: logowanie, rejestracja
 ├─ FlashcardAuthoring/    # ręczny CRUD fiszek
 ├─ AISuggestions/         # integracja z LLM + statystyki
 ├─ ReviewScheduling/      # SRS & harmonogram
 └─ StudySession/          # przebieg nauki
tests/
 ├─ Behat/
 │   ├─ identityaccess/
 │   ├─ flashcardauthoring/
 │   └─ aisuggestions/
 └─ phpunit/
```

Each context = 3 layers:

```
<Context>/
 ├─ Domain/
 ├─ Application/
 └─ Infrastructure/
```

---

## 3. Happy-Path Cycle (_“Red → Blue/Design → Green”_)

1. **Discovery**    
   *Example Mapping* with PO → decide examples & vocabulary.  
2. **Specification**    
   Create / update `*.feature` in the appropriate sub‑folder.  
   > _Cursor hint:_ When you see “### TODO DEFINE”, propose Given/When/Then skeletons.  
3. **Red – run Behat**    
   All new scenarios fail.  
4. **Design – model domain**    
   *Domain* layer only PHP classes; no Symfony or DB code.  
   Use **Domain Events** to publish cross‑context actions.  
5. **Unit TDD** within the context (PHPUnit/phpspec).  
6. **Green – implement Behat steps**    
   Wire Application Services & Infrastructure adapters until all scenarios pass.  
7. **Refactor**    
   Keep Ubiquitous Language; no logic in controllers, only in apps/services.  
8. **Merge & Deploy** when Behat + PHPUnit are green in CI.  

---

## 4. Example Feature

```gherkin
Feature: AI generates flashcard suggestions
  In order to shorten preparation time
  As a logged-in learner
  I want to receive flashcard candidates from the AI

  Background:
    Given the user "alice@example.com" is registered and logged in

  Scenario: Generating from a 2 000-character excerpt
    When Alice submits a text of 2000 characters for generation
    Then 8 flashcard suggestions should be shown
    And every suggestion contains a question and an answer
    And the generation stats for Alice record 8 candidates
```

Behat context class → `tests/Behat/AISuggestions/AISuggestionsContext.php`.

---

## 5. Synthetic LLM client for tests

```php
final class FakeLLMClient implements LLMClientInterface
{
    public function generate(string $input): array
    {
        return [
            ['question' => 'Q1', 'answer' => 'A1'],
            // …
        ];
    }
}
```

*In `behat.yml`:*

```yaml
default:
  suites:
    aisuggestions:
      contexts:
        - AISuggestionsContext
  extensions:
    FriendsOfBehat\SymfonyExtension:
      kernel:
        class: App\Tests\Behat\TestKernel
  services:
    App\AISuggestions\Infrastructure\LLMClientInterface:
      class: App\Tests\Double\FakeLLMClient
```

---

## 6. Event choreography

| Domain Event | Publisher | Subscribers |
|--------------|-----------|-------------|
| `FlashcardsAccepted` | AISuggestions | FlashcardAuthoring → persist as drafts |
| `FlashcardCreated` | FlashcardAuthoring | ReviewScheduling → `ScheduleInitialReview` |
| `ReviewCompleted` | StudySession | ReviewScheduling (algorithm) |

All events dispatched via **Symfony Messenger** (Doctrine transport in dev/stage; RabbitMQ in prod).

---

## 7. CI Pipeline (GitHub Actions)

```yaml
name: CI
on: [push, pull_request]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install --prefer-dist
      - run: vendor/bin/phpstan
      - run: vendor/bin/phpunit
      - run: vendor/bin/behat --stop-on-failure
      - run: docker build -t flashcards:${{ github.sha }} .
```

---

## 8. How Cursor-AI can help

| Phase | Prompt pattern for Cursor |
|-------|---------------------------|
| _Specification_ | “Generate Gherkin scenarios for **US-003** using domain terms ‘flashcard’, ‘suggestion’…” |
| _Design_ | “Suggest DDD aggregate + value objects needed to satisfy this scenario.” |
| _Implementation_ | “Provide Symfony Messenger command handler skeleton for `GenerateSuggestions`.” |
| _Refactor_ | “Identify SRP violations in `AISuggestionsContext.php` and propose separation.” |

---

## 9. Definition of Done ✔︎

1. All new `.feature` files pass (`vendor/bin/behat`).
2. Unit coverage ≥ 80 % for changed classes.
3. All public PHP methods have typed signatures & PHPDoc for domain-level behaviour.
4. No PHPStan level 9 errors.
5. PR reviewed & approved by another context owner.

---

Happy coding 👩‍💻👨‍💻 – and happy prompting!
