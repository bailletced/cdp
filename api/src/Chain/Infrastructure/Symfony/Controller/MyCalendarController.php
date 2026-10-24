<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Symfony\Controller;

use App\Chain\Application\Port\CalendarFile;
use App\Chain\Application\Query\InscriptionDigestFinder;
use App\Chain\Application\Query\PublicEditionViewFinder;
use App\Chain\Domain\ValueObject\EditionId;
use App\Chain\Domain\ValueObject\PriantId;
use App\Shared\Application\Port\CurrentViewer;
use App\Shared\Infrastructure\ApiPlatform\Exception\AccessDeniedException;
use App\Shared\Infrastructure\ApiPlatform\Exception\MissingModelException;
use App\Shared\Infrastructure\ApiPlatform\Exception\UnauthenticatedException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/me/calendar.ics (F11-T06, US-11.2): one agenda file with all the
 * slots of the verified priant in the current public edition — the single
 * « Ajouter mes créneaux à mon agenda » button of Mon espace (arbitrage Q15).
 *
 * A session is required: a device pass only gives the read-only view
 * (RG-IN-6), so a visitor or a pass bearer gets a 401; an account without
 * priant (an organizer who never registered) gets a 403.
 */
final readonly class MyCalendarController
{
    public function __construct(
        private CurrentViewer $currentViewer,
        private PublicEditionViewFinder $publicEditionViewFinder,
        private InscriptionDigestFinder $inscriptionDigestFinder,
        private CalendarFile $calendarFile,
    ) {
    }

    #[Route('/api/me/calendar.ics', name: 'me_calendar', methods: ['GET'])]
    public function __invoke(): Response
    {
        $viewer = $this->currentViewer->current();

        if (!$viewer->verified) {
            throw new UnauthenticatedException();
        }

        if (null === $viewer->priantId) {
            throw new AccessDeniedException();
        }

        $edition = $this->publicEditionViewFinder->current();

        if (null === $edition) {
            throw new MissingModelException(self::class);
        }

        $digest = $this->inscriptionDigestFinder->findByPriantAndEdition(new PriantId($viewer->priantId), new EditionId($edition->id));

        return new Response(
            $this->calendarFile->forSlots($edition->title, $digest->slots ?? []),
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'mes-creneaux.ics'),
                'Cache-Control' => 'private, no-store',
            ],
        );
    }
}
