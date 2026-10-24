@session-resilience
Feature: Session storage survives a lost database connection
    As an authenticated visitor
    I want my session to keep working when its database connection dies
    So that a network blip never logs me out with a 500 error

    Background:
        Given the system is initialized
        And an account exists with email "thomas@bailletced.org"
        And I am logged in as this account

    Scenario: Authenticated request succeeds after the session connection is lost
        When I make a GET request to "/api/me"
        Then the response should be "200 OK"
        When the application database connections are terminated
        And the application database connection is used again
        And I make a GET request to "/api/me"
        Then the response should be "200 OK"
        And the response should contain JSON with "email" equal to "thomas@bailletced.org"
