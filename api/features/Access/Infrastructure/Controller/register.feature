@register
Feature: Public registration endpoint (F07-T01)
    As a visitor
    I want to register with my email on the landing page
    So that an account is created and I receive a personal login link by email

    Background:
        Given the system is initialized

    Scenario: New email: account is created and link is sent
        When I POST JSON to "/auth/register" with body:
            """
            {"email": "nouveau@bailletced.org", "website": ""}
            """
        Then the response should be the registration accepted response
        And an account should exist with email "nouveau@bailletced.org"
        When I process the async queue
        Then a personal link email is sent to "nouveau@bailletced.org"

    Scenario: Existing account: link is sent, no duplicate
        Given an account exists with email "thomas@bailletced.org"
        When I POST JSON to "/auth/register" with body:
            """
            {"email": "thomas@bailletced.org", "website": ""}
            """
        Then the response should be the registration accepted response
        When I process the async queue
        Then a personal link email is sent to "thomas@bailletced.org"

    Scenario: Filled honeypot: identical response, nothing happens
        When I POST JSON to "/auth/register" with body:
            """
            {"email": "spam@bailletced.org", "website": "http://spam.example.com"}
            """
        Then the response should be the registration accepted response
        And no account should exist with email "spam@bailletced.org"
        When I process the async queue
        Then no email is sent

    Scenario: Invalid email: identical response, nothing happens
        When I POST JSON to "/auth/register" with body:
            """
            {"email": "not-an-email", "website": ""}
            """
        Then the response should be the registration accepted response
        When I process the async queue
        Then no email is sent

    Scenario: Missing email: identical response, nothing happens
        When I POST JSON to "/auth/register" with body:
            """
            {"website": ""}
            """
        Then the response should be the registration accepted response
        When I process the async queue
        Then no email is sent
