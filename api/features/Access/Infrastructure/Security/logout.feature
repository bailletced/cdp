@logout
Feature: Logout and same-origin protection (F02-T10)
    As an authenticated priant
    I want to log out and be protected from cross-origin attacks
    So that my session is destroyed and only same-origin requests are accepted

    Background:
        Given the system is initialized

    Scenario: Logout destroys the session
        Given an account exists
        And a valid login link is generated for this account
        When I submit the login link via POST
        Then the response should be "200 OK"
        When I make a GET request to "/auth/status"
        Then the response should contain JSON with "authenticated" equal to "true"
        When I make a POST request to "/auth/logout"
        Then the response should be "302 Found"
        When I make a GET request to "/auth/status"
        Then the response should contain JSON with "authenticated" equal to "false"

    Scenario: POST from another origin is rejected with 403
        Given an account exists
        And a valid login link is generated for this account
        When I submit the login link via POST with Origin "https://evil.example.com"
        Then the response should be "403 Forbidden"

    Scenario: POST with cross-site Sec-Fetch-Site is rejected with 403
        Given an account exists
        And a valid login link is generated for this account
        When I submit the login link via POST with Sec-Fetch-Site "cross-site"
        Then the response should be "403 Forbidden"

    Scenario: POST without Origin header is allowed (non-browser client)
        Given an account exists
        And a valid login link is generated for this account
        When I submit the login link via POST
        Then the response should be "200 OK"
