@admin @organizers
Feature: Admin organizers API (F04-T02)
    As an organizer
    I want to list, add and remove organizers through the admin API
    So that the organizer team can be managed

    Background:
        Given the system is initialized

    Scenario: Visitor gets a 401 on the organizers list
        When I make a GET request to "/api/admin/organizers"
        Then the response should be "401 Unauthorized"

    Scenario: Authenticated priant gets a 403 on the organizers list
        Given an account exists with email "thomas@bailletced.org"
        And I am logged in as this account
        When I make a GET request to "/api/admin/organizers"
        Then the response should be "403 Forbidden"

    Scenario: Organizer gets the list of organizers
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I make a GET request to "/api/admin/organizers"
        Then the response should be "200 OK"
        And the response should contain a JSON array with 1 member
        And the response should contain JSON with "member.0.email" equal to "anne@bailletced.org"

    Scenario: Organizer adds a new organizer
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I POST JSON-LD to "/api/admin/organizers" with body:
            """
            {"email": "bob@bailletced.org"}
            """
        Then the response should be "201 Created"
        And the response should contain JSON with "email" equal to "bob@bailletced.org"

    Scenario: Adding an existing organizer is idempotent
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I POST JSON-LD to "/api/admin/organizers" with body:
            """
            {"email": "anne@bailletced.org"}
            """
        Then the response should be "201 Created"
        And the response should contain JSON with "email" equal to "anne@bailletced.org"

    Scenario: Organizer removes another organizer
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And an account exists with email "bob@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I get the organizer id for "bob@bailletced.org"
        And I make a DELETE request to "/api/admin/organizers/" with the captured organizer id
        Then the response should be "204 No Content"

    Scenario: A removed organizer no longer appears in the next list request
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And an account exists with email "bob@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I get the organizer id for "anne@bailletced.org"
        And I make a DELETE request to "/api/admin/organizers/" with the captured organizer id
        And I make a GET request to "/api/admin/organizers"
        Then the response should be "200 OK"
        And the response should contain a JSON array with 1 member
        And the response should contain JSON with "member.0.email" equal to "bob@bailletced.org"

    Scenario: A removed organizer loses admin access on the next request
        Given an account exists with email "bob@bailletced.org"
        And this account is an organizer
        And an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I get the organizer id for "anne@bailletced.org"
        And I make a DELETE request to "/api/admin/organizers/" with the captured organizer id
        And I make a GET request to "/api/admin/organizers"
        Then the response should be "403 Forbidden"

    Scenario: Removing the last organizer is refused
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I get the organizer id for "anne@bailletced.org"
        And I make a DELETE request to "/api/admin/organizers/" with the captured organizer id
        Then the response should be "400 Bad Request"

    Scenario: Visitor gets a 401 on POST
        When I POST JSON-LD to "/api/admin/organizers" with body:
            """
            {"email": "bob@bailletced.org"}
            """
        Then the response should be "401 Unauthorized"

    Scenario: Priant gets a 403 on POST
        Given an account exists with email "thomas@bailletced.org"
        And I am logged in as this account
        When I POST JSON-LD to "/api/admin/organizers" with body:
            """
            {"email": "bob@bailletced.org"}
            """
        Then the response should be "403 Forbidden"

    Scenario: Visitor gets a 401 on DELETE
        When I make a DELETE request to "/api/admin/organizers/00000000-0000-0000-0000-000000000000"
        Then the response should be "401 Unauthorized"

    Scenario: Priant gets a 403 on DELETE
        Given an account exists with email "thomas@bailletced.org"
        And I am logged in as this account
        When I make a DELETE request to "/api/admin/organizers/00000000-0000-0000-0000-000000000000"
        Then the response should be "403 Forbidden"

    Scenario: Priant authenticated by remember-me cookie gets a 403 on the organizers list
        Given an account exists with email "thomas@bailletced.org"
        And I am logged in as this account
        When I delete all sessions from the database
        And I make a GET request to "/api/admin/organizers" with only the cookie "REMEMBERME"
        Then the response should be "403 Forbidden"

    Scenario: Organizer authenticated by remember-me cookie gets the list of organizers
        Given an account exists with email "anne@bailletced.org"
        And this account is an organizer
        And I am logged in as this account
        When I delete all sessions from the database
        And I make a GET request to "/api/admin/organizers" with only the cookie "REMEMBERME"
        Then the response should be "200 OK"
        And the response should contain a JSON array with 1 member
        And the response should contain JSON with "member.0.email" equal to "anne@bailletced.org"
