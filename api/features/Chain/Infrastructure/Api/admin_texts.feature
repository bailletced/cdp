@admin @texts
Feature: Admin texts API (F06-T08, F06-T09)
    As an organizer
    I want to create, update and delete texts through the admin API
    So that the text library can be managed

    Background:
        Given the system is initialized

    Scenario: Visitor gets a 401 on text creation
        When I POST JSON-LD to "/api/admin/texts" with body:
            """
            {"title": "Oraison", "source": "Auteur", "body": "<p>Corps.</p>"}
            """
        Then the response should be "401 Unauthorized"

    Scenario: Authenticated priant gets a 403 on text creation
        Given an account exists with email "thomas@bailletced.org"
        And I am logged in as this account
        When I POST JSON-LD to "/api/admin/texts" with body:
            """
            {"title": "Oraison", "source": "Auteur", "body": "<p>Corps.</p>"}
            """
        Then the response should be "403 Forbidden"

    Scenario: Organizer creates a text
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I POST JSON-LD to "/api/admin/texts" with body:
            """
            {"title": "Oraison de Thérèse", "source": "Thérèse de Lisieux", "body": "<p>Je voudrais aimer.</p>", "hints": "Méditer le matin."}
            """
        Then the response should be "201 Created"
        And the response should contain a text id
        And the response should contain JSON with "title" equal to "Oraison de Thérèse"

    Scenario: Creating a text with a too short title returns 422
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I POST JSON-LD to "/api/admin/texts" with body:
            """
            {"title": "ab", "source": "Auteur", "body": "<p>Corps.</p>"}
            """
        Then the response should be "422 Unprocessable Entity"
        And the response should contain a violation on "title"

    Scenario: Creating a text with an empty body returns 422
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I POST JSON-LD to "/api/admin/texts" with body:
            """
            {"title": "Titre valide", "source": "Auteur", "body": "<p></p>"}
            """
        Then the response should be "422 Unprocessable Entity"
        And the response should contain a violation on "body"

    Scenario: Organizer updates a text
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And a text exists with title "Titre original" and source "Auteur original"
        And I am logged in as this account
        When I PATCH JSON-LD to the last created text with body:
            """
            {"title": "Titre modifié", "source": "Nouvel auteur", "body": "<p>Nouveau corps.</p>"}
            """
        Then the response should be "200 OK"
        And the response should contain JSON with "title" equal to "Titre modifié"

    Scenario: Updating an unknown text returns 404
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I PATCH JSON-LD to "/api/admin/texts/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d" with body:
            """
            {"title": "Titre modifié", "source": "Auteur", "body": "<p>Corps.</p>"}
            """
        Then the response should be "404 Not Found"

    Scenario: Organizer deletes a text
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And a text exists with title "À supprimer" and source "Auteur"
        And I am logged in as this account
        When I DELETE the last created text
        Then the response should be "204 No Content"
        And the text "À supprimer" should not exist

    Scenario: Deleting an unknown text returns 404
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I make a DELETE request to "/api/admin/texts/9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d"
        Then the response should be "404 Not Found"
