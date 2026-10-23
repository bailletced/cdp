@inscriptions
Feature: Public inscriptions API (F09-T07 to F09-T13)
    As a visitor of the public site
    I want to register on one or several slots of the open edition
    So that I take part in the prayer chain

    Background:
        Given the system is initialized
        And an open edition exists

    Scenario: A visitor registers on two slots and is linked to an account
        When I register on the first two slots of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        Then the response should be "201 Created"
        And the response should contain JSON with "displayName" equal to "Famille Martin"
        And the response should contain 2 inscriptions
        And a priant should exist with email "famille.martin@bailletced.org" and display name "Famille Martin"
        And an inscription should exist on the first slot of the last created edition
        When I process the async queue
        Then the account with email "famille.martin@bailletced.org" should be linked to the priant with email "famille.martin@bailletced.org"

    Scenario: The account is linked asynchronously, only when the queue is processed (transactional outbox)
        When I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        Then the response should be "201 Created"
        And a priant should exist with email "famille.martin@bailletced.org" and display name "Famille Martin"
        And the account table should be empty
        When I process the async queue
        Then exactly one account should exist
        And the account with email "famille.martin@bailletced.org" should be linked to the priant with email "famille.martin@bailletced.org"

    Scenario: The registration form never overwrites an existing display name (RG-IN-2)
        Given a priant exists with email "marie@bailletced.org" and display name "Marie Dupont"
        When I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "marie@bailletced.org", "consent": true}
            """
        Then the response should be "201 Created"
        And the response should contain JSON with "displayName" equal to "Marie Dupont"
        And the priant with email "marie@bailletced.org" should still be named "Marie Dupont"

    Scenario: An organizer registering keeps a single account (F09-T09)
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        When I register on the first slot of the last created edition with body:
            """
            {"displayName": "Anne Organisatrice", "email": "anne@bailletced.org", "consent": true}
            """
        Then the response should be "201 Created"
        When I process the async queue
        Then exactly one account should exist
        And the account with email "anne@bailletced.org" should be linked to the priant with email "anne@bailletced.org"

    Scenario: The consent is required for an anonymous visitor (RG-IN-5)
        When I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org"}
            """
        Then the response should be "422 Unprocessable Entity"
        And the response should contain a violation on "consent"

    Scenario: A recognized priant who already consented registers without the checkbox (RG-IN-5, RG-IN-7)
        Given an account exists with email "marie@bailletced.org"
        And a priant exists with email "marie@bailletced.org" and display name "Marie Dupont"
        And the last created priant has given consent
        And this account is linked to the last created priant
        And I am logged in as this account
        When I register on the first slot of the last created edition with body:
            """
            {"displayName": "Marie Dupont", "email": "marie@bailletced.org"}
            """
        Then the response should be "201 Created"
        And an inscription should exist on the first slot of the last created edition

    Scenario: Registering twice on the same slot returns a 409 (RG-IN-3)
        When I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        Then the response should be "201 Created"
        When I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        Then the response should be "409 Conflict"
        And the response should contain JSON with "detail" equal to "Vous êtes déjà inscrit sur ce créneau."

    Scenario: A missing display name returns a 422 on displayName
        When I register on the first slot of the last created edition with body:
            """
            {"email": "famille.martin@bailletced.org", "consent": true}
            """
        Then the response should be "422 Unprocessable Entity"
        And the response should contain a violation on "displayName"

    Scenario: A missing email returns a 422 on email
        When I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "consent": true}
            """
        Then the response should be "422 Unprocessable Entity"
        And the response should contain a violation on "email"

    Scenario: An invalid email returns a 422 on email
        When I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "not-an-email", "consent": true}
            """
        Then the response should be "422 Unprocessable Entity"
        And the response should contain a violation on "email"

    Scenario: An unknown slot returns a 404
        When I register on an unknown slot with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        Then the response should be "404 Not Found"

    Scenario: The honeypot field silently fakes a success (§6.7)
        When I register on the first slot of the last created edition with body:
            """
            {"displayName": "Spam Bot", "email": "spam@example.com", "consent": true, "website": "http://spam.example"}
            """
        Then the response should be "201 Created"
        And the priant table should be empty
        And no inscription should exist on the first slot of the last created edition

    Scenario: The planning exposes the registration counts (RG-PL-1, F09-T13)
        When I register on the first two slots of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        Then the response should be "201 Created"
        When I get the slots of the last created edition
        Then the response should be "200 OK"
        And the response should contain JSON with "slots.0.registrationCount" equal to "1"
        And the response should contain JSON with "slots.0.occupied" equal to "true"
        And the response should contain JSON with "slots.1.registrationCount" equal to "1"
        And the response should contain JSON with "slots.1.occupied" equal to "true"
        And the response should contain JSON with "slots.2.registrationCount" equal to "0"
        And the response should contain JSON with "slots.2.occupied" equal to "false"

    Scenario: The start time is locked after the first inscription (RG-ED-3, F09-T12)
        When I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        Then the response should be "201 Created"
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I PATCH JSON-LD to the last created edition with body:
            """
            {"title": "24 heures de prière", "presentation": "", "startsAt": "2099-06-15 20:00", "opensAt": "2020-01-01 00:00"}
            """
        Then the response should be "409 Conflict"
        And the response should contain JSON with "detail" equal to "L’heure de début n’est plus modifiable dès la première inscription (RG-ED-3)."
