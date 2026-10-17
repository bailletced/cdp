<?php

declare(strict_types=1);

namespace App\Access\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Controller pour la route /auth/connexion.
 * GET redirige vers la SPA /connexion.
 * POST est géré par Symfony Security login_link.
 */
final class LoginLinkController
{
    #[Route('/auth/connexion', name: 'auth_login_link', methods: ['GET'])]
    public function loginLinkGet(): Response
    {
        // GET /auth/connexion redirige vers la SPA /connexion
        // selon F02-T07 : redirection vers la SPA /connexion
        return new RedirectResponse('/connexion');
    }

    #[Route('/auth/connexion', name: 'auth_login_link_post', methods: ['POST'])]
    public function loginLinkPost(
        Request $request,
        AuthenticationUtils $authenticationUtils
    ): Response {
        // POST /auth/connexion est géré par Symfony Security login_link
        // Cette méthode est appelée si login_link ne gère pas la requête
        // Mais normalement, avec check_post_only: true, GET est bloqué et POST est géré par login_link
        
        // Si nous arrivons ici, c'est que login_link a échoué
        // Retourner une erreur 401
        if ($error = $authenticationUtils->getLastAuthenticationError()) {
            return new Response('Invalid login link', Response::HTTP_UNAUTHORIZED);
        }

        // Si tout va bien, login_link a ouvert la session et redirigé
        // Nous ne devrions pas arriver ici
        return new Response('Login successful', Response::HTTP_OK);
    }
}
