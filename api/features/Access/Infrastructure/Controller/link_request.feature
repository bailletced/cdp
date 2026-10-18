@link_request
Feature: Personal link request endpoint (F02-T15)
    As a visitor
    I want to request a personal login link by email
    So that I receive it by email without revealing whether my account exists

    Background:
        Given the system is initialized

    Scenario: Known account: link is requested, identical response
        Given an account exists with email "thomas@example.com"
        When I POST JSON to "/auth/link-request" with body:
            """
            {"email": "thomas@example.com", "website": ""}
            """
        Then the response should be the generic accepted response
        And a link message is queued for "thomas@example.com"

    Scenario: Unknown account: identical response, nothing is sent
        When I POST JSON to "/auth/link-request" with body:
            """
            {"email": "inconnu@example.com", "website": ""}
            """
        Then the response should be the generic accepted response
        And no link message is queued

    Scenario: Filled honeypot: identical response, nothing is sent
        Given an account exists with email "thomas@example.com"
        When I POST JSON to "/auth/link-request" with body:
            """
            {"email": "thomas@example.com", "website": "http://spam.example.com"}
            """
        Then the response should be the generic accepted response
        And no link message is queued

    Scenario: Invalid email: identical response, nothing is sent
        When I POST JSON to "/auth/link-request" with body:
            """
            {"email": "not-an-email", "website": ""}
            """
        Then the response should be the generic accepted response
        And no link message is queued

    Scenario: Missing email: identical response, nothing is sent
        When I POST JSON to "/auth/link-request" with body:
            """
            {"website": ""}
            """
        Then the response should be the generic accepted response
        And no link message is queued
