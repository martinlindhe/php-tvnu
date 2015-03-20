Feature: tv
  In order to see what's on the TV
  As a UNIX user
  I want to list what is currently showing on TV without leaving my terminal

  Scenario: Show what is on TV in 2 columns
    Given I am in the terminal
    And the terminal width is at least 80 characters
    When I run "bin/tv"
    Then I should get 2 columns

  Scenario: Show what is on TV in 1 column
    Given I am in the terminal
    And the terminal width is less than 80 characters
    When I run "bin/tv"
    Then I should get 1 columns

  Scenario: Show what is on a given channel
    Given I am in the terminal
    When I run "bin/tv SVT1"
    Then I should get 1 columns
    And I should get a program listing containing "SVT1"
    And I should get at least 4 rows

  Scenario: Show all programming for a given channel
    Given I am in the terminal
    When I run "bin/tv SVT1 --all"
    Then I should get 1 columns
    And I should get a program listing containing "SVT1"
    And I should get at least 20 rows

  Scenario: Search when a show is airing
    Given I am in the terminal
    When I run "bin/tv when show"
    Then I should get a search result

  Scenario: Search when a show is airing and forgot to type show
    Given I am in the terminal
    When I run "bin/tv when"
    Then I should get a error message
    And I should get error code 1

  Scenario: Get usage help
    Given I am in the terminal
    When I run "bin/tv --help"
    Then I should get a help screen

  Scenario: Get invalid parameter message
    Given I am in the terminal
    When I run "bin/tv --invalid-command"
    Then I should get a error message
    And I should get error code 1
