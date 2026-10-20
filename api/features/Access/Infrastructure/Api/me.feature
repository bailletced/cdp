@me
Feature: Current account API endpoint (F02-T16)
    As a visitor or an authenticated account
    I want to know who I am through the API
    So that the SPA can adapt its interface to my profile

    Background:
        Given the system is initialized

    Scenario: Visitor gets a 401
        When I make a GET request to "/api/me"
        Then the response should be "401 Unauthorized"

    Scenario: Authenticated priant gets their email and isOrganizer false
        Given an account exists with email "thomas@bailletced.org"
        And I am logged in as this account
        When I make a GET request to "/api/me"
        Then the response should be "200 OK"
        And the response should contain JSON with "email" equal to "thomas@bailletced.org"
        And the response should contain JSON with "isOrganizer" equal to "false"

    Scenario: Authenticated organizer gets isOrganizer true
        Given an account exists with email "organisateur@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I make a GET request to "/api/me"
        Then the response should be "200 OK"
        And the response should contain JSON with "email" equal to "organisateur@bailletced.org"
        And the response should contain JSON with "isOrganizer" equal to "true"
