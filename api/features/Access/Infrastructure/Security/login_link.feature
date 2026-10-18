@login_link
Feature: Personal login link authentication (F02-T08)
    As a priant
    I want to log in with a personal link received by email
    So that I can access my space without a password

    Background:
        Given the system is initialized

    Scenario: Valid login link opens session and returns target
        Given an account exists
        And a valid login link is generated for this account with target "/my-space"
        When I submit the login link via POST
        Then the response should be "200 OK"
        And the response should contain JSON with "target" equal to "/my-space"

    Scenario: Invalid login link returns 401
        When I submit an invalid login link via POST
        Then the response should be "401 Unauthorized"
        And the response should contain JSON with "error" equal to "invalid_login_link"
        And the response should contain JSON with "type" equal to "invalid"

    Scenario: Expired login link returns 401
        Given an account exists
        And an expired login link is generated for this account
        When I submit the expired login link via POST
        Then the response should be "401 Unauthorized"
        And the response should contain JSON with "error" equal to "invalid_login_link"
        And the response should contain JSON with "type" equal to "expired"

    Scenario: Tampered login link returns 401
        Given an account exists
        And a valid login link is generated for this account
        And the link hash is tampered
        When I submit the tampered login link via POST
        Then the response should be "401 Unauthorized"
        And the response should contain JSON with "error" equal to "invalid_login_link"
        And the response should contain JSON with "type" equal to "altered"

    Scenario: Revoked login link returns 401
        Given an account exists
        And a valid login link is generated for this account
        And the account is deleted
        When I submit the login link via POST
        Then the response should be "401 Unauthorized"
        And the response should contain JSON with "error" equal to "invalid_login_link"
        And the response should contain JSON with "type" equal to "revoked"

    Scenario: GET on /auth/login redirects to the SPA
        When I make a GET request to "/auth/login"
        Then the response should be "302 Found"
        And the response should redirect to "/login"
