# Plan dokończenia projektu Fiszki

## Stan obecny projektu

### ✅ Zrealizowane funkcjonalności:
1. **Autentykacja i autoryzacja**
   - Rejestracja z potwierdzeniem email (US-001)
   - Logowanie (US-002)
   - System zabezpieczeń (US-009)

2. **Zarządzanie talią i fiszkami**
   - CRUD dla talii (Deck)
   - CRUD dla fiszek (Flashcard)
   - Ręczne tworzenie fiszek (US-007)
   - Edycja fiszek (US-005)
   - Usuwanie fiszek (US-006)

3. **Infrastruktura**
   - Docker environment
   - Baza danych MySQL
   - Mailer (MailPit)
   - Testy Behat

### ❌ Brakujące funkcjonalności (do implementacji):

## Faza 1: Integracja z AI (Priorytet: WYSOKI)

### 1.1 Serwis integracji z OpenAI API
**User Story:** US-003, US-004
**Czas:** 2-3 dni

#### Zadania:
1. Utworzenie serwisu `AIFlashcardService`:
   - Konfiguracja połączenia z OpenAI API
   - Model: gpt-3.5-turbo lub gpt-4
   - Implementacja metody `generateFlashcards(string $text): array`
   - Instalacja pakietu: `composer require openai-php/client`

2. Utworzenie encji `GenerationSession`:
   - Przechowywanie historii generowania
   - Powiązanie z User i Deck
   - Status generowania (pending, completed, failed)

3. Controller `AIGeneratorController`:
   - Endpoint do generowania fiszek
   - Walidacja tekstu (1000-10000 znaków)
   - Obsługa błędów API

#### Struktura promptu dla OpenAI:
```
System: Jesteś ekspertem w tworzeniu fiszek edukacyjnych. Tworzysz przejrzyste, konkretne pytania i odpowiedzi.

User: Wygeneruj fiszki edukacyjne na podstawie poniższego tekstu.
Każda fiszka powinna mieć pytanie (front) i odpowiedź (back).
Wygeneruj 5-10 najważniejszych fiszek.
Odpowiedz TYLKO w formacie JSON, bez dodatkowego tekstu:
[{"front": "pytanie", "back": "odpowiedź"}]

Tekst: [USER_TEXT]
```

### 1.2 Interfejs generowania fiszek
**Czas:** 1-2 dni

#### Zadania:
1. Template `ai_generator/generate.html.twig`:
   - Textarea na tekst (1000-10000 znaków)
   - Licznik znaków
   - Przycisk "Generuj fiszki"
   - Loader podczas generowania

2. Template `ai_generator/review.html.twig`:
   - Lista wygenerowanych fiszek
   - Przyciski: Akceptuj/Edytuj/Odrzuć dla każdej fiszki
   - Bulk actions: "Zaakceptuj wszystkie", "Odrzuć wszystkie"

3. JavaScript/Stimulus controller:
   - AJAX request do generowania
   - Dynamiczne aktualizacje UI
   - Walidacja po stronie klienta

## Faza 2: System powtórek (Priorytet: WYSOKI)

### 2.1 Integracja algorytmu Spaced Repetition
**User Story:** US-008
**Czas:** 3-4 dni

#### Zadania:
1. Integracja biblioteki SM-2 lub SuperMemo:
   ```bash
   composer require carlomarzochi/sm2
   ```

2. Rozszerzenie encji `Flashcard`:
   - `nextReviewDate`: DateTime
   - `repetitions`: int
   - `easeFactor`: float
   - `interval`: int

3. Serwis `SpacedRepetitionService`:
   - Metoda `getCardsForReview(User $user): array`
   - Metoda `processReview(Flashcard $card, int $quality): void`
   - Algorytm SM-2 implementation

### 2.2 Interfejs sesji nauki
**Czas:** 2 dni

#### Zadania:
1. Controller `StudySessionController`:
   - Endpoint `/study/session`
   - Pobieranie fiszek do nauki
   - Zapisywanie wyników

2. Template `study/session.html.twig`:
   - Wyświetlanie przodu fiszki
   - Przycisk "Pokaż odpowiedź"
   - Przyciski oceny (1-5 lub Łatwe/Średnie/Trudne)
   - Progress bar sesji

3. Stimulus controller dla interaktywności:
   - Flip animation
   - Keyboard shortcuts (spacja = pokaż, 1-5 = ocena)

## Faza 3: Statystyki i monitoring (Priorytet: ŚREDNI)

### 3.1 System statystyk
**Czas:** 2 dni

#### Zadania:
1. Encja `Statistics`:
   - Liczba wygenerowanych fiszek
   - Liczba zaakceptowanych fiszek
   - Współczynnik akceptacji
   - Statystyki per user/deck

2. Dashboard użytkownika:
   - Widget statystyk
   - Wykres postępów nauki
   - Liczba fiszek do powtórki

## Faza 4: Optymalizacja i testy (Priorytet: ŚREDNI)

### 4.1 Testy i jakość kodu
**Czas:** 2-3 dni

#### Zadania:
1. Testy Behat dla nowych funkcjonalności:
   - `ai_generation.feature`
   - `study_session.feature`

2. Testy jednostkowe:
   - `AIFlashcardServiceTest`
   - `SpacedRepetitionServiceTest`

3. Optymalizacja:
   - Cache dla API calls
   - Batch processing dla bulk operations
   - Query optimization

## Harmonogram implementacji

| Tydzień | Faza | Funkcjonalności |
|---------|------|-----------------|
| 1 | Faza 1.1 | Serwis AI, encja GenerationSession |
| 1-2 | Faza 1.2 | UI generowania fiszek |
| 2 | Faza 2.1 | Algorytm Spaced Repetition |
| 3 | Faza 2.2 | Interfejs sesji nauki |
| 3-4 | Faza 3 | Statystyki |
| 4 | Faza 4 | Testy i optymalizacja |

## Konfiguracja OpenAI API

### Wymagania:
1. Konto OpenAI z aktywnym kluczem API
2. Wystarczające środki na koncie OpenAI
3. Dostęp do modeli GPT-3.5-turbo lub GPT-4

### Konfiguracja w `.env`:
```env
OPEN_AI_KEY=sk-your-api-key-here
AI_MODEL=gpt-3.5-turbo
AI_MAX_TOKENS=1000
AI_TEMPERATURE=0.7
```

### Instalacja pakietu OpenAI dla PHP:
```bash
docker-compose exec php-fpm composer require openai-php/client
```

## Następne kroki

1. **Rozpocznij od Fazy 1.1** - Serwis integracji z OpenAI API
2. Zainstaluj pakiet `openai-php/client` 
3. Skonfiguruj serwis z kluczem API z `.env`
4. Implementuj podstawowy flow generowania fiszek
5. Testuj z różnymi tekstami edukacyjnymi
6. Iteracyjnie udoskonalaj prompty dla lepszych wyników

## Metryki sukcesu (zgodnie z PRD)

- [ ] 75% wygenerowanych fiszek jest akceptowanych
- [ ] 75% nowych fiszek tworzone jest z AI
- [ ] Czas generowania < 10 sekund dla typowego tekstu
- [ ] System powtórek działa zgodnie z algorytmem SM-2

## Uwagi techniczne

1. **Bezpieczeństwo**: 
   - Rate limiting dla API calls
   - Nigdy nie commituj klucza API do repozytorium
   - Używaj `.env.local` dla lokalnych ustawień
2. **Performance**: 
   - Asynchroniczne przetwarzanie dla długich tekstów
   - Cache odpowiedzi dla identycznych tekstów
3. **UX**: 
   - Progressive enhancement - aplikacja działa bez JS
   - Timeout handling dla długich requestów do OpenAI
4. **Monitoring**: 
   - Logowanie wszystkich interakcji z AI
   - Tracking kosztów API (tokens użyte)
5. **Koszt**: 
   - GPT-3.5-turbo: ~$0.002 per 1K tokens
   - GPT-4: ~$0.03 per 1K tokens
   - Rozważ limity per użytkownik