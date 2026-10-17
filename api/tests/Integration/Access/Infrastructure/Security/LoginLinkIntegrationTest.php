<?php

declare(strict_types=1);

namespace App\Tests\Integration\Access\Infrastructure\Security;

use App\Access\Domain\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * Tests d'intégration pour login_link (F02-T08).
 * Critère de fin : POST /auth/connexion ouvre la session et renvoie la cible.
 * Les 4 cas : lien valide, expiré, altéré, révoqué → 401 typé.
 */
final class LoginLinkIntegrationTest extends WebTestCase
{
    /**
     * Teste que GET /auth/connexion redirige vers /connexion (F02-T07).
     */
    public function testGetAuthConnexionRedirectsToSpa(): void
    {
        $client = static::createClient();
        
        // GET /auth/connexion doit rediriger vers /connexion
        $client->request('GET', '/auth/connexion');
        
        $response = $client->getResponse();
        
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('/connexion', $response->headers->get('Location'));
    }

    /**
     * Teste que POST avec un token valide ouvre la session et renvoie la cible.
     */
    public function testPostWithValidTokenOpensSessionAndReturnsTarget(): void
    {
        $client = static::createClient();
        
        // Créer un compte pour le test avec email unique
        $accountUuid = Uuid::v7();
        $account = new Account(
            new AccountId($accountUuid),
            new Email('test-login-link-valid-' . $accountUuid->toRfc4122() . '@example.com'),
            1
        );
        
        $container = self::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($account);
        $em->flush();
        
        // Générer un lien de connexion avec la cible
        $loginLinkParams = $this->generateLoginLinkParamsForUuid($accountUuid);
        
        // POST /auth/connexion avec les paramètres du lien de connexion
        // login_link attend user, expires, hash et _target_path
        $client->request('POST', '/auth/connexion', array_merge(
            $loginLinkParams,
            ['_target_path' => '/mon-espace']
        ));
        
        $response = $client->getResponse();
        
        // Doit retourner 200 avec JSON contenant la cible
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('target', $data);
        $this->assertEquals('/mon-espace', $data['target']);
        
        // Nettoyer
        $em->remove($account);
        $em->flush();
    }

    /**
     * Teste qu'un token invalide retourne 401.
     */
    public function testPostWithInvalidTokenReturns401(): void
    {
        $client = static::createClient();
        
        // POST avec des paramètres manifestement invalides
        $client->request('POST', '/auth/connexion', [
            'user' => 'invalid_user_id',
            'expires' => time() + 3600,
            'hash' => 'invalid_hash',
        ]);
        
        $response = $client->getResponse();
        
        // Doit retourner 401
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('invalid_login_link', $data['error']);
    }

    /**
     * Teste qu'un token altéré retourne 401.
     */
    public function testPostWithAlteredTokenReturns401(): void
    {
        $client = static::createClient();
        
        // Créer un compte avec email unique
        $accountUuid = Uuid::v7();
        $account = new Account(
            new AccountId($accountUuid),
            new Email('test-login-link-altered-' . $accountUuid->toRfc4122() . '@example.com'),
            1
        );
        
        $container = self::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($account);
        $em->flush();
        
        // Générer un lien valide puis altérer le hash
        $loginLinkParams = $this->generateLoginLinkParamsForUuid($accountUuid);
        $loginLinkParams['hash'] = $loginLinkParams['hash'] . 'ALTERED';
        
        $client->request('POST', '/auth/connexion', array_merge(
            $loginLinkParams,
            ['_target_path' => '/mon-espace']
        ));
        
        $response = $client->getResponse();
        
        // Doit retourner 401
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('invalid_login_link', $data['error']);
        
        // Nettoyer
        $em->remove($account);
        $em->flush();
    }

    /**
     * Teste qu'un token pour un compte révoqué (supprimé) retourne 401.
     */
    public function testPostWithRevokedLinkReturns401(): void
    {
        $client = static::createClient();
        
        // Créer un compte temporaire avec email unique
        $tempUuid = Uuid::v7();
        $tempAccount = new Account(
            new AccountId($tempUuid),
            new Email('test-login-link-revoked-' . $tempUuid->toRfc4122() . '@example.com'),
            1
        );
        
        $container = self::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($tempAccount);
        $em->flush();
        
        // Générer un lien pour ce compte
        $loginLinkParams = $this->generateLoginLinkParamsForUuid($tempUuid);
        
        // Supprimer le compte (révoquer le lien)
        $em->remove($tempAccount);
        $em->flush();
        
        // Tester avec le lien révoqué
        $client->request('POST', '/auth/connexion', array_merge(
            $loginLinkParams,
            ['_target_path' => '/mon-espace']
        ));
        
        $response = $client->getResponse();
        
        // Doit retourner 401
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('invalid_login_link', $data['error']);
    }

    /**
     * Génère les paramètres login_link pour un UUID donné.
     * Retourne un tableau avec user, expires, hash.
     */
    private function generateLoginLinkParamsForUuid(Uuid $uuid): array
    {
        $container = self::getContainer();
        
        // Charger le SecurityUser
        $userProvider = $container->get('App\\Access\\Infrastructure\\Security\\UserProvider');
        $securityUser = $userProvider->loadUserByIdentifier($uuid->toRfc4122());
        
        // Générer le lien via LoginLinkHandler
        $handler = $container->get('security.authenticator.login_link_handler.main');
        
        // createLoginLink retourne un LoginLinkDetails
        $loginLinkDetails = $handler->createLoginLink($securityUser);
        
        // Extraire les paramètres de l'URL générée
        $url = $loginLinkDetails->getUrl();
        
        // Extraire les paramètres (format: /auth/connexion?user=...&expires=...&hash=...)
        parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
        
        return [
            'user' => $queryParams['user'] ?? '',
            'expires' => $queryParams['expires'] ?? '',
            'hash' => $queryParams['hash'] ?? '',
        ];
    }
}
