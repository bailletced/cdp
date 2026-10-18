<?php

declare(strict_types=1);

namespace App\Tests\Behat\Security;

use App\Tests\Behat\BehatKernelTrait;
use Behat\Behat\Context\Context;
use Behat\Step\When;
use Symfony\Component\HttpFoundation\Request;

/**
 * Behat context for the remember_me bounded context (F02-T09).
 */
final class RememberMeContext implements Context
{
    use BehatKernelTrait;

    #[When('I delete all sessions from the database')]
    public function deleteAllSessionsFromDatabase(): void
    {
        $connection = $this->state()->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM sessions');
    }

    #[When('I make a GET request to :path with only the cookie :cookieName')]
    public function makeGetRequestWithOnlyCookie(string $path, string $cookieName): void
    {
        $state = $this->state();

        // Find the cookie value from the captured response cookies
        $cookieValue = null;
        $cookieHeaderName = null;
        foreach ($state->responseCookies as $name => $value) {
            if (str_contains($name, $cookieName)) {
                $cookieValue = $value;
                $cookieHeaderName = $name;
                break;
            }
        }

        if (null === $cookieValue || null === $cookieHeaderName) {
            throw new \RuntimeException(\sprintf("Cookie '%s' not found in captured response cookies", $cookieName));
        }

        $request = Request::create($path, 'GET');
        $request->headers->set('Cookie', $cookieHeaderName.'='.$cookieValue);
        $request->cookies->set($cookieHeaderName, $cookieValue);

        $this->handleRequest($request);
    }
}
