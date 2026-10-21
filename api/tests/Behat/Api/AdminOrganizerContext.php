<?php

declare(strict_types=1);

namespace App\Tests\Behat\Api;

use App\Tests\Behat\BehatKernelTrait;
use Behat\Behat\Context\Context;
use Behat\Step\When;
use Symfony\Component\HttpFoundation\Request;

/**
 * Behat context for the /api/admin/organizers endpoints (F04-T02).
 */
final class AdminOrganizerContext implements Context
{
    use BehatKernelTrait;

    #[When('I get the organizer id for :email')]
    public function getOrganizerIdFor(string $email): void
    {
        $state = $this->state();

        // Fetch the organizers list to find the ID for this email
        $request = Request::create('/api/admin/organizers', 'GET');
        $this->handleRequest($request);

        $data = json_decode($state->lastResponseContent ?? '', true);
        $members = $data['member'] ?? $data['hydra:member'] ?? [];

        foreach ($members as $member) {
            if (($member['email'] ?? null) === $email) {
                $state->capturedOrganizerId = $member['id'] ?? null;
                if (null === $state->capturedOrganizerId) {
                    throw new \RuntimeException(\sprintf('Organizer "%s" found but has no id in the response', $email));
                }

                return;
            }
        }

        throw new \RuntimeException(\sprintf('Organizer with email "%s" not found in the list', $email));
    }

    #[When('I make a DELETE request to :path with the captured organizer id')]
    public function makeDeleteRequestWithCapturedOrganizerId(string $path): void
    {
        $state = $this->state();

        if (null === $state->capturedOrganizerId) {
            throw new \RuntimeException('No organizer id captured. Run "I get the organizer id for ..." first.');
        }

        $request = Request::create($path.$state->capturedOrganizerId, 'DELETE');
        $this->handleRequest($request);
    }
}
