@login_link
Feature: Authentification par lien personnel (F02-T08)
    En tant que priant
    Je veux me connecter avec un lien personnel reçu par email
    Afin d'accéder à mon espace sans mot de passe

    Background:
        Given le système est initialisé

    Scenario: Lien de connexion valide ouvre la session et retourne la cible
        Given un compte utilisateur existe
        And un lien de connexion valide est généré pour ce compte avec cible "/mon-espace"
        When je soumets le lien de connexion via POST
        Then la réponse doit être "200 OK"
        And la réponse doit contenir JSON avec "target" égal à "/mon-espace"

    Scenario: Lien de connexion invalide retourne 401
        When je soumets un lien de connexion invalide via POST
        Then la réponse doit être "401 Unauthorized"
        And la réponse doit contenir JSON avec "error" égal à "invalid_login_link"
        And la réponse doit contenir JSON avec "type" égal à "invalid"

    Scenario: Lien de connexion expiré retourne 401
        Given un compte utilisateur existe
        And un lien de connexion expiré est généré pour ce compte
        When je soumets le lien de connexion expiré via POST
        Then la réponse doit être "401 Unauthorized"
        And la réponse doit contenir JSON avec "error" égal à "invalid_login_link"
        And la réponse doit contenir JSON avec "type" égal à "expired"

    Scenario: Lien de connexion altéré retourne 401
        Given un compte utilisateur existe
        And un lien de connexion valide est généré pour ce compte
        And le hash du lien est altéré
        When je soumets le lien de connexion altéré via POST
        Then la réponse doit être "401 Unauthorized"
        And la réponse doit contenir JSON avec "error" égal à "invalid_login_link"
        And la réponse doit contenir JSON avec "type" égal à "altered"

    Scenario: Lien de connexion révoqué retourne 401
        Given un compte utilisateur existe
        And un lien de connexion valide est généré pour ce compte
        And le compte est supprimé
        When je soumets le lien de connexion via POST
        Then la réponse doit être "401 Unauthorized"
        And la réponse doit contenir JSON avec "error" égal à "invalid_login_link"
        And la réponse doit contenir JSON avec "type" égal à "revoked"

    Scenario: GET sur /auth/connexion redirige vers SPA
        When je fais une requête GET sur "/auth/connexion"
        Then la réponse doit être "302 Found"
        And la réponse doit rediriger vers "/connexion"
