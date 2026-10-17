<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller pour la route GET /auth/connexion.
 * GET redirige vers la SPA /connexion (F02-T07).
 * POST est géré par Symfony Security login_link (F02-T08).
 */
final class LoginLinkController
{
    /**
     * GET /auth/connexion redirige vers la SPA /connexion.
     * selon F02-T07 : redirection vers la SPA /connexion.
     */
    public function loginLinkGet(): Response
    {
        return new RedirectResponse('/connexion');
    }
}
