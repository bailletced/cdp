@mark_gospel
Feature: Import the Mark gospel and spread it over an edition
    As an organizer of the Communion
    I want a single command to import the 48-reading cutting of the Gospel
    of Mark and spread it over the slots of an edition
    So that every 30-minute slot of the 24-hour chain carries its reading

    Background:
        Given the system is initialized
        And an open edition exists

    Scenario: The 48 readings are imported and distributed in order
        When I import the Mark gospel readings on the last created edition
        Then the command should succeed
        And every slot of the last created edition should carry its Mark reading, in order
        And the library should contain 48 readings of the Mark gospel
        And the command output should mention "48 lectures ajoutées"

    Scenario: The command is idempotent, the readings already imported are reused
        Given the Mark gospel has been imported on the last created edition
        When I import the Mark gospel readings on the last created edition
        Then the command should succeed
        And the library should contain 48 readings of the Mark gospel
        And the command output should mention "0 lecture ajoutée"
        And every slot of the last created edition should carry its Mark reading, in order

    Scenario: An unknown edition fails the command
        When I import the Mark gospel readings on an unknown edition
        Then the command should fail
        And the command output should mention "Édition introuvable"
