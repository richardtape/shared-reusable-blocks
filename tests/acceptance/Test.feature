Feature: Test
  In order to log in
  As a not signed in user
  I need to click the Log in button

  Scenario: When a not signed in user clicks the log in button they are sent to the log in page
  Given I am on the home page
  When I click the log in button
  Then I should be on the log in page
