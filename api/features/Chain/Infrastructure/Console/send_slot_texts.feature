Feature: Send the slot texts by e-mail
    As an organizer of the Communion
    I want a command that sends every priant one e-mail with the texts of
    all their slots
    So that each priant prays with their texts at the hour of their slot

    Background:
        Given the system is initialized
        And an open edition exists
        And the first slot of the last created edition carries the text "Que rien ne te trouble"

    Scenario: A priant receives one e-mail listing all their slots with their texts
        Given I register on the first two slots of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        And I process the async queue
        When I send the slot texts of the last created edition
        Then the command should succeed
        And a slot texts email is sent to "famille.martin@bailletced.org"
        And the slot texts email sent to "famille.martin@bailletced.org" mentions "20h00–20h30"
        And the slot texts email sent to "famille.martin@bailletced.org" mentions "20h30–21h00"
        And the slot texts email sent to "famille.martin@bailletced.org" mentions "Que rien ne te trouble"
        And the slot texts email sent to "famille.martin@bailletced.org" carries the attachment "pour-vivre-les-24h-de-la-priere-2026.pdf" of type "application/pdf"

    Scenario: The --email option sends the texts to one priant only, the others hear nothing
        Given I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        And I process the async queue
        And I register on the second slot of the last created edition with body:
            """
            {"displayName": "Marie Dupont", "email": "marie@bailletced.org", "consent": true}
            """
        And I process the async queue
        When I send the slot texts of the last created edition to "marie@bailletced.org"
        Then the command should succeed
        And the command output should mention "1 e-mail envoyé"
        And exactly 1 slot texts email is sent to "marie@bailletced.org"
        And exactly 0 slot texts email is sent to "famille.martin@bailletced.org"

    Scenario: The --email option with an address no priant carries sends nothing and still succeeds
        Given I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        And I process the async queue
        When I send the slot texts of the last created edition to "inconnu@bailletced.org"
        Then the command should succeed
        And the command output should mention "Aucun priant avec cette adresse"
        And exactly 0 slot texts email is sent to "famille.martin@bailletced.org"

    Scenario: Without --edition, the command sends the texts of the current open edition
        Given I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        And I process the async queue
        When I send the slot texts of the current edition
        Then the command should succeed
        And a slot texts email is sent to "famille.martin@bailletced.org"

    Scenario: The command is idempotent: a second run sends nothing new
        Given I register on the first slot of the last created edition with body:
            """
            {"displayName": "Famille Martin", "email": "famille.martin@bailletced.org", "consent": true}
            """
        And I process the async queue
        When I send the slot texts of the last created edition
        Then the command should succeed
        And a slot texts email is sent to "famille.martin@bailletced.org"
        When I send the slot texts of the last created edition
        Then the command should succeed
        And the command output should mention "1 e-mail déjà envoyé"
        And the command output should mention "0 e-mail envoyé"
        And exactly 1 slot texts email is sent to "famille.martin@bailletced.org"

    Scenario: An edition whose only priant has no e-mail sends nothing and still succeeds
        Given a priant without e-mail has taken the first slot of the last created edition
        When I send the slot texts of the last created edition
        Then the command should succeed
        And the command output should mention "Aucun priant avec e-mail inscrit sur cette édition"
        And no email is sent
