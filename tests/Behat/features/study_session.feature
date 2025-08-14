Feature: Study Session
  In order to learn effectively with spaced repetition
  As a logged in user
  I want to be able to study my flashcards

  Background:
    Given a user exists with email "student@example.com" and password "StudyPass123"
    And I am on the "/login" page
    When I fill in the login field "email" with "student@example.com"
    And I fill in the login field "password" with "StudyPass123"
    And I press "Zaloguj się"
    Then I should be redirected to the "/" page
    And there is a deck named "Biology Deck" with 3 flashcards

  Scenario: Starting a study session
    Given I am on the "/study/" page
    Then I should see "Sesja nauki"
    And I should see "Biology Deck"
    And I should see "Wszystkie fiszki: 3"
    When I click "Rozpocznij naukę"
    Then I should be redirected to the "/study/card" page
    And I should see "Pytanie"
    And I should see "1 / 3"

  Scenario: Reviewing a flashcard
    Given I have started a study session for "Biology Deck"
    When I am on the "/study/card" page
    Then I should see "Pytanie"
    And I should see "Kliknij, aby zobaczyć odpowiedź"
    When I click the flashcard
    Then I should see "Odpowiedź"
    And I should see "Jak dobrze znasz tę fiszkę?"
    When I rate the card with quality "4"
    Then I should be redirected to the next card

  Scenario: Completing a study session
    Given I have reviewed all cards in my study session
    When I am on the "/study/complete" page
    Then I should see "Gratulacje!"
    And I should see "Ukończyłeś sesję nauki!"
    And I should see "Skuteczność"
    And I should see a "Kontynuuj naukę" button
    And I should see a "Dashboard" button

  Scenario: Viewing study history
    Given I have completed 2 study sessions
    When I am on the "/study/history" page
    Then I should see "Historia sesji nauki"
    And I should see 2 sessions in the history table
    And I should see "Statystyki ogólne"
    And I should see "Wszystkie sesje: 2"

  Scenario: No cards due for review
    Given all my flashcards are scheduled for tomorrow
    When I am on the "/study/" page
    And I click "Rozpocznij naukę" for "Biology Deck"
    Then I should be redirected to the "/study/" page
    And I should see "Nie masz żadnych fiszek do powtórki w tej talii!"