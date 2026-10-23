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

    Scenario: Verified priant gets their display name and consent already given (RG-IN-7)
        Given an account exists with email "marie@bailletced.org"
        And a priant exists with email "marie@bailletced.org" and display name "Marie Dupont"
        And the last created priant has given consent
        And this account is linked to the last created priant
        And I am logged in as this account
        When I make a GET request to "/api/me"
        Then the response should be "200 OK"
        And the response should contain JSON with "email" equal to "marie@bailletced.org"
        And the response should contain JSON with "displayName" equal to "Marie Dupont"
        And the response should contain JSON with "consentGiven" equal to "true"

    Scenario: Account without a linked priant gets no display name and no consent
        Given an account exists with email "thomas@bailletced.org"
        And I am logged in as this account
        When I make a GET request to "/api/me"
        Then the response should be "200 OK"
        And the response JSON key "displayName" should be null
        And the response JSON key "consentGiven" should be false
