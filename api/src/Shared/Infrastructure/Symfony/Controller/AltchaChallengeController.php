<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Controller;

use App\Shared\Infrastructure\Symfony\Altcha\AltchaGate;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/altcha-challenge (F15-T05): tells the SPA whether to show the
 * ALTCHA widget — 204 while off — and hands it a fresh challenge once on.
 */
final readonly class AltchaChallengeController
{
    public function __construct(
        private AltchaGate $altchaGate,
    ) {
    }

    #[Route('/api/altcha-challenge', name: 'altcha_challenge', methods: ['GET'])]
    public function __invoke(): Response
    {
        $response = $this->altchaGate->isEnabled()
            ? new JsonResponse($this->altchaGate->challenge())
            : new Response(status: Response::HTTP_NO_CONTENT);
        // Each challenge is for one form only
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
