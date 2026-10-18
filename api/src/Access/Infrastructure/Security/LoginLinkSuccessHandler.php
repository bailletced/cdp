<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Success handler for login_link.
 * Returns the target as JSON for the SPA.
 * F02-T08: POST /auth/login opens the session and returns the target.
 */
final class LoginLinkSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * Called when authentication succeeded.
     * Returns a JSON with the redirect URL (target).
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        // Get the target from the request
        // For POST, _target_path is in the request parameters
        // For GET, _target_path would be in the query parameters
        $targetPath = $request->request->get('_target_path') ?? $request->query->get('_target_path') ?? '/';

        // Return JSON with the target so the SPA can redirect
        return new JsonResponse([
            'target' => $targetPath,
        ]);
    }
}
