<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Entry point of the main firewall: answers 401 (JSON-LD error) instead of
 * redirecting to the login page when access_control rejects a visitor.
 *
 * The SPA expects a 401 on the API; the default entry point of a login_link
 * firewall would answer a 302 to /login.
 */
final class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse(
            [
                '@context' => '/api/contexts/Error',
                '@type' => 'hydra:Error',
                'title' => 'An error occurred',
                'detail' => 'Authentification requise.',
            ],
            Response::HTTP_UNAUTHORIZED,
            ['Content-Type' => 'application/ld+json; charset=utf-8'],
        );
    }
}
