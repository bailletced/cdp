<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Classe de base pour les tests d'API (acceptance).
 * Étend WebTestCase pour fournir des utilitaires de test HTTP.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected static function createClient(array $options = [], array $server = []): static
    {
        $client = parent::createClient($options, $server);

        // Configurer le client pour les tests API
        $client->disableReboot();

        return $client;
    }

    /**
     * Effectue une requête GET et retourne la réponse.
     */
    protected function get(string $url, array $headers = [], array $query = []): array
    {
        $client = static::createClient();
        $client->request('GET', $url, $query, [], $headers);

        return [
            'status' => $client->getResponse()->getStatusCode(),
            'content' => $client->getResponse()->getContent(),
            'headers' => $client->getResponse()->headers->all(),
        ];
    }

    /**
     * Effectue une requête POST et retourne la réponse.
     */
    protected function post(string $url, array $data = [], array $headers = []): array
    {
        $client = static::createClient();
        $client->request('POST', $url, [], [], $headers, json_encode($data) ?: '{}');

        return [
            'status' => $client->getResponse()->getStatusCode(),
            'content' => $client->getResponse()->getContent(),
            'headers' => $client->getResponse()->headers->all(),
        ];
    }

    /**
     * Assert que la réponse a le statut HTTP attendu.
     */
    protected function assertStatusCode(int $expected, int $actual, string $message = ''): void
    {
        self::assertSame($expected, $actual, $message ?: \sprintf('Expected status code %d, got %d', $expected, $actual));
    }

    /**
     * Assert que le content-type est JSON-LD.
     */
    protected function assertJsonLd(string $contentType): void
    {
        self::assertStringContainsString('application/ld+json', $contentType);
    }

    /**
     * Décode le contenu JSON de la réponse.
     */
    protected function getJsonContent(string $content): array
    {
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
