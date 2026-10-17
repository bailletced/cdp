<?php

declare(strict_types=1);

namespace App\Tests\Integration\Access\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests d'intégration pour LoginLinkController (F02-T07).
 * Critère de fin : un GET n'ouvre aucune session (test).
 */
final class LoginLinkControllerTest extends WebTestCase
{
    public function testGetAuthConnexionRedirectsToSpa(): void
    {
        $client = static::createClient();
        
        // GET /auth/connexion doit rediriger vers /connexion
        $client->request('GET', '/auth/connexion');
        
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('/connexion', $client->getResponse()->headers->get('Location'));
    }

    public function testGetAuthConnexionDoesNotStartAuthentication(): void
    {
        $client = static::createClient();
        
        // GET /auth/connexion ne doit pas déclencher l'authentification login_link
        // Avec check_post_only: true, GET ne doit pas être géré par login_link
        $client->request('GET', '/auth/connexion');
        
        // Le contrôleur doit être appelé, pas login_link
        // La réponse doit être une redirection, pas une erreur d'authentification
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testRoutesAreConfigured(): void
    {
        $container = self::getContainer();
        
        // Vérifier que les routes existent
        $router = $container->get('router');
        
        // Route GET pour la redirection vers SPA
        $getRoute = $router->getRouteCollection()->get('auth_login_link_get');
        $this->assertNotNull($getRoute);
        $this->assertEquals('/auth/connexion', $getRoute->getPath());
        $this->assertContains('GET', $getRoute->getMethods());
        
        // Route POST gérée par Symfony Security login_link
        $postRoute = $router->getRouteCollection()->get('_security_login_link');
        $this->assertNotNull($postRoute);
        $this->assertEquals('/auth/connexion', $postRoute->getPath());
        $this->assertContains('POST', $postRoute->getMethods());
    }
}
