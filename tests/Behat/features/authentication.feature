Feature: Authentication
  In order to access protected resources
  As a user
  I need to be able to log in

  Scenario: Successful login
    Given I am on "/login"
    When I fill in "email" with "test@example.com"
    And I fill in "password" with "password"
    And I press "Sign in"
    Then I should be on "/"
    And I should see "Welcome to Fiszki" 