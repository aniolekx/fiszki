# features/user_authentication/login.feature
Feature: User Login
  In order to access my personal flashcards and generation history
  As a registered user
  I want to be able to log into the application

  Background:
    Given a user exists with email "test@example.com" and password "correctPassword"

  Scenario: Successful login with valid credentials
    Given I am on the "/login" page
    When I fill in "email" with "test@example.com"
    And I fill in "password" with "correctPassword"
    And I press "Log In"
    Then I should be redirected to the "/generate-flashcards" page # Or the main dashboard/generation view route
    And I should see "Welcome, test@example.com" # Or some other indication of being logged in

  Scenario: Failed login with invalid password
    Given I am on the "/login" page
    When I fill in "email" with "test@example.com"
    And I fill in "password" with "wrongPassword"
    And I press "Log In"
    Then I should still be on the "/login" page # Or see the login form again
    And I should see the error message "Invalid credentials."

  Scenario: Failed login with non-existent email
    Given I am on the "/login" page
    When I fill in "email" with "nonexistent@example.com"
    And I fill in "password" with "anyPassword"
    And I press "Log In"
    Then I should still be on the "/login" page
    And I should see the error message "Invalid credentials." # Usually the same message for security reasons