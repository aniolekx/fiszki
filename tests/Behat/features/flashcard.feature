Feature: Flashcard Creation
  In order to learn effectively
  As a logged in user
  I want to be able to create flashcards in my decks

  Background:
    Given a user exists with email "aniolekx@gmail.com" and password "correctPassword"
    And I am on the "/login" page
    When I fill in the login field "email" with "aniolekx@gmail.com"
    And I fill in the login field "password" with "correctPassword"
    And I press "Zaloguj się"
    Then I should be redirected to the "/" page
    And there is a deck named "Moja pierwsza talia"

  Scenario: Successful flashcard creation
    Given I am on the "/decks/1/flashcards/new" page
    When I fill in "front" with "What is the capital of France?"
    And I fill in "back" with "Paris"
    And I press "Utwórz fiszkę"
    Then I should be redirected to the "/decks/1" page
    And I should see "What is the capital of France?"
    And I should see "Paris"

  Scenario: Failed flashcard creation with empty fields
    Given I am on the "/decks/1/flashcards/new" page
    When I fill in "front" with "What is the capital of France?"
    And I press "Utwórz fiszkę"
    Then I should still be on the "/decks/1/flashcards/new" page
    And I should see the error message "Treść odpowiedzi jest wymagana"

  Scenario: Failed flashcard creation with empty front
    Given I am on the "/decks/1/flashcards/new" page
    When I fill in "back" with "Paris"
    And I press "Utwórz fiszkę"
    Then I should still be on the "/decks/1/flashcards/new" page
    And I should see the error message "Treść pytania jest wymagana" 