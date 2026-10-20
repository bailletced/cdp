@health
Feature: Health check endpoint (F03-T06)
    As a monitoring system
    I want to check the application health
    So that I can detect when the service is down or the database is unreachable

    Background:
        Given the system is initialized

    Scenario: Database is up and schema is up-to-date
        When I make a GET request to "/health"
        Then the response should be "200 OK"
        And the response should contain JSON with "status" equal to "ok"
        And the response should contain JSON with "database" equal to "up"
        And the response should contain JSON with "schema" equal to "up-to-date"

    Scenario: Response contains the application version
        When I make a GET request to "/health"
        Then the response should be "200 OK"
        And the response should contain JSON with "version" equal to "unknown"
