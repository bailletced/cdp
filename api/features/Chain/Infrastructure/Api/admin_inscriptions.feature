@admin @inscriptions
Feature: Admin inscriptions list (F16-T03)
    As an organizer
    I want the list of the inscriptions of an edition, with the registered
    names and e-mail addresses
    So that I can reach a priant of a slot when needed

    Background:
        Given the system is initialized
        And an open edition exists

    Scenario: A visitor cannot list the inscriptions
        When I request the admin inscriptions of the last created edition
        Then the response should be "401 Unauthorized"

    Scenario: A priant without the organizer role cannot list the inscriptions
        Given an account exists with email "thomas@bailletced.org"
        And I am logged in as this account
        When I request the admin inscriptions of the last created edition
        Then the response should be "403 Forbidden"

    Scenario: An organizer lists the names and e-mails of the inscriptions, slot by slot
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        When I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        Then the response should be "201 Created"
        When I register on the second slot of the last created edition with body:
            """
            {"displayName": "Marie Dupont", "email": "marie@bailletced.org", "consent": true}
            """
        Then the response should be "201 Created"
        When I register on the second slot of the last created edition with body:
            """
            {"displayName": "Paul Lebreton", "email": "paul@bailletced.org", "consent": true}
            """
        Then the response should be "201 Created"
        When I am logged in as this account
        And I request the admin inscriptions of the last created edition
        Then the response should be "200 OK"
        And the response should contain a JSON array with 3 members
        And the response should contain JSON with "member.0.displayName" equal to "Famille Martin"
        And the response should contain JSON with "member.0.email" equal to "famille.martin@bailletced.org"
        And the response should contain JSON with "member.0.slotRank" equal to "1"
        # An edition spans two days, so a slot label alone needs its day
        And the response should contain JSON with "member.0.dayLabel" equal to "jeudi 1 janvier"
        And the response should contain JSON with "member.2.displayName" equal to "Paul Lebreton"
        And the response should contain JSON with "member.2.slotRank" equal to "2"
        And the response should contain JSON with "member.2.dayLabel" equal to "jeudi 1 janvier"

    Scenario: The slot filter narrows the list to one slot
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        When I register on the first two slots of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        Then the response should be "201 Created"
        When I am logged in as this account
        And I request the admin inscriptions of the first slot of the last created edition
        Then the response should be "200 OK"
        And the response should contain a JSON array with 1 member
        And the response should contain JSON with "member.0.displayName" equal to "Famille Martin"
        And the response should contain JSON with "member.0.slotRank" equal to "1"
