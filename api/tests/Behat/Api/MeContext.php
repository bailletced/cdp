<?php

declare(strict_types=1);

namespace App\Tests\Behat\Api;

use App\Tests\Behat\BehatKernelTrait;
use App\Tests\Factory\Access\OrganizerFactory;
use Behat\Behat\Context\Context;
use Behat\Step\Given;

/**
 * Behat context for the GET /api/me endpoint (F02-T16).
 */
final class MeContext implements Context
{
    use BehatKernelTrait;

    #[Given('this account is an organizer')]
    public function thisAccountIsAnOrganizer(): void
    {
        $state = $this->state();
        $account = $state->currentAccount;
        $entityManager = $state->entityManager;

        if (null === $account || null === $entityManager) {
            throw new \RuntimeException('No account available. Create an account first.');
        }

        $entityManager->persist(OrganizerFactory::create($account->getId()->value, $account->getId()->value));
        $entityManager->flush();
    }
}
