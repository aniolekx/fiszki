Feature: Deck Creation
  In order to organize my flashcards
  As a logged in user
  I want to be able to create new decks

  Background:
    Given a user exists with email "aniolekx@gmail.com" and password "correctPassword"
    And I am on the "/login" page
    When I fill in the login field "email" with "aniolekx@gmail.com"
    And I fill in the login field "password" with "correctPassword"
    And I press "Zaloguj się"
    Then I should be redirected to the "/" page

  Scenario: Successful deck creation
    Given I am on the "/decks/new" page
    When I fill in "name" with "Moja pierwsza talia"
    And I fill in "description" with "Talia do nauki angielskiego"
    And I press "Utwórz talię"
    Then I should be redirected to the "/decks" page
    And I should see "Moja pierwsza talia"
    And I should see "Talia do nauki angielskiego"

  Scenario: Failed deck creation with empty name
    Given I am on the "/decks/new" page
    When I fill in "description" with "Talia do nauki angielskiego"
    And I press "Utwórz talię"
    Then I should still be on the "/decks/new" page
    And I should see the error message "Nazwa talii jest wymagana" 