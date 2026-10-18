<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Hook\BeforeScenario;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Messenger\Test\Bus\TestBus;
use Zenstruck\Messenger\Test\Transport\TestTransport;

/**
 * Cross-cutting Behat steps shared by all bounded contexts.
 * Owns the scenario lifecycle (kernel boot/shutdown, Foundry init).
 */
final class SharedContext implements Context
{
    use BehatKernelTrait;

    #[BeforeScenario]
    public function setupScenario(BeforeScenarioScope $scope): void
    {
        $this->state()->reset();
        $this->bootKernel();
    }

    #[AfterScenario]
    public function teardownScenario(AfterScenarioScope $scope): void
    {
        $this->shutdownKernel();

        // The messenger test transports keep their state in static
        // (process-wide) properties: reset them so queued messages
        // do not leak from one scenario into the next.
        TestBus::resetAll();
        TestTransport::resetAll();
    }

    #[Given('the system is initialized')]
    public function systemIsInitialized(): void
    {
        // Already initialized in setupScenario
    }

    #[When('I make a POST request to :path')]
    public function makePostRequest(string $path): void
    {
        $request = Request::create($path, 'POST');
        $this->handleRequest($request);
    }

    #[When('I make a GET request to :path')]
    public function makeGetRequest(string $path): void
    {
        $request = Request::create($path, 'GET');
        $this->handleRequest($request);
    }

    #[Then('the response should be :expectedStatus')]
    public function responseShouldBe(string $expectedStatus): void
    {
        $state = $this->state();
        $statusCode = $this->mapStatusStringToCode($expectedStatus);

        if ($state->lastResponseStatus !== $statusCode) {
            throw new \RuntimeException(
                sprintf('Expected status %d (%s), got %d', $statusCode, $expectedStatus, $state->lastResponseStatus)
            );
        }
    }

    #[Then('the response should contain JSON with :key equal to :expectedValue')]
    public function responseShouldContainJsonWithEqualTo(string $key, string $expectedValue): void
    {
        $state = $this->state();
        if ($state->lastResponseContent === null) {
            throw new \RuntimeException('No response content available');
        }

        $data = json_decode($state->lastResponseContent, true);

        if (!isset($data[$key])) {
            throw new \RuntimeException("JSON response does not contain key '$key'");
        }

        $actual = $data[$key];
        if (is_bool($actual)) {
            $actual = $actual ? 'true' : 'false';
        }

        if ((string) $actual !== $expectedValue) {
            throw new \RuntimeException(
                sprintf("Expected '%s' for key '$key', got '%s'", $expectedValue, $actual)
            );
        }
    }

    #[Then('the response should redirect to :expectedPath')]
    public function responseShouldRedirectTo(string $expectedPath): void
    {
        $state = $this->state();
        if (!isset($state->lastResponseHeaders['location'])) {
            throw new \RuntimeException('Response does not have Location header');
        }

        $location = $state->lastResponseHeaders['location'][0] ?? '';
        if (!str_contains($location, $expectedPath)) {
            throw new \RuntimeException(
                sprintf("Expected redirect to contain '%s', got '%s'", $expectedPath, $location)
            );
        }
    }

    #[Then('the response should set a cookie :cookieName')]
    public function responseShouldSetCookie(string $cookieName): void
    {
        $state = $this->state();

        // Cookie name may be prefixed with __Host- in config; match flexibly
        $found = false;
        foreach ($state->responseCookies as $name => $value) {
            if (str_contains($name, $cookieName)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $available = implode(', ', array_keys($state->responseCookies)) ?: '(none)';
            throw new \RuntimeException(
                sprintf("Expected response to set cookie containing '%s', got cookies: %s", $cookieName, $available)
            );
        }
    }

    private function mapStatusStringToCode(string $statusString): int
    {
        $map = [
            '200 OK' => Response::HTTP_OK,
            '201 Created' => Response::HTTP_CREATED,
            '202 Accepted' => Response::HTTP_ACCEPTED,
            '204 No Content' => Response::HTTP_NO_CONTENT,
            '302 Found' => Response::HTTP_FOUND,
            '400 Bad Request' => Response::HTTP_BAD_REQUEST,
            '401 Unauthorized' => Response::HTTP_UNAUTHORIZED,
            '403 Forbidden' => Response::HTTP_FORBIDDEN,
            '404 Not Found' => Response::HTTP_NOT_FOUND,
            '500 Internal Server Error' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ];

        return $map[$statusString] ?? 0;
    }
}
