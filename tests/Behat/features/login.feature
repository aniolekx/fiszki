# features/user_authentication/login.feature
Feature: User Login
  In order to access my personal flashcards and generation history
  As a registered user
  I want to be able to log into the application

  Background:
    Given a user exists with email "aniolekx@gmail.com" and password "correctPassword"

  Scenario: Successful login with valid credentials
    Given I am on the "/login" page
    When I fill in the login field "email" with "aniolekx@gmail.com"
    And I fill in the login field "password" with "correctPassword"
    And I press "Zaloguj się"
    Then I should be redirected to the "/test" page
    And I should see "Welcome, aniolekx@gmail.com"

  Scenario: Failed login with invalid password
    Given I am on the "/login" page
    When I fill in the login field "email" with "aniolekx@gmail.com"
    And I fill in the login field "password" with "wrongPassword"
    And I press "Zaloguj się"
    Then I should still be on the "/login" page
    And I should see the error message "The presented password is invalid."

  Scenario: Failed login with non-existent email
    Given I am on the "/login" page
    When I fill in the login field "email" with "nonexistent@example.com"
    And I fill in the login field "password" with "anyPassword"
    And I press "Zaloguj się"
    Then I should still be on the "/login" page
    And I should see the error message "The presented password is invalid."
