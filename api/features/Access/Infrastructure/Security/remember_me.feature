@remember_me
Feature: Remember-me (F02-T09)
    As a priant logged in via personal link
    I want to stay authenticated even if my session expires
    So that I don't have to log in again on every visit

    Background:
        Given the system is initialized

    Scenario: Remember-me cookie is set on login
        Given an account exists
        And a valid login link is generated for this account
        When I submit the login link via POST
        Then the response should be "200 OK"
        And the response should set a cookie "REMEMBERME"

    Scenario: Session deleted, remember-me cookie keeps authentication
        Given an account exists
        And a valid login link is generated for this account
        When I submit the login link via POST
        Then the response should be "200 OK"
        And the response should set a cookie "REMEMBERME"
        When I delete all sessions from the database
        And I make a GET request to "/auth/status" with only the cookie "REMEMBERME"
        Then the response should be "200 OK"
        And the response should contain JSON with "authenticated" equal to "true"

    Scenario: Without remember-me cookie, a visitor is not authenticated
        When I make a GET request to "/auth/status"
        Then the response should be "200 OK"
        And the response should contain JSON with "authenticated" equal to "false"
