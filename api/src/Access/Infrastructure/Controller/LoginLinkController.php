<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the GET /auth/login route.
 * GET redirects to the SPA /login (F02-T07), keeping the link query string
 * so the SPA can POST the parameters back to /auth/login.
 * POST is handled by Symfony Security login_link (F02-T08).
 */
final class LoginLinkController
{
    /**
     * GET /auth/login redirects to the SPA /login.
     * Per F02-T07: redirect to the SPA /login, preserving the query string.
     */
    public function loginLinkGet(Request $request): RedirectResponse
    {
        $query = $request->query->all();
        $target = '/login'.([] === $query ? '' : '?'.http_build_query($query));

        return new RedirectResponse($target);
    }
}
