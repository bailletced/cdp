<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the GET /auth/connexion route.
 * GET redirects to the SPA /connexion (F02-T07).
 * POST is handled by Symfony Security login_link (F02-T08).
 */
final class LoginLinkController
{
    /**
     * GET /auth/connexion redirects to the SPA /connexion.
     * Per F02-T07: redirect to the SPA /connexion.
     */
    public function loginLinkGet(): Response
    {
        return new RedirectResponse('/connexion');
    }
}
