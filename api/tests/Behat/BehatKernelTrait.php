<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Trait providing shared kernel lifecycle and state access for all Behat contexts.
 * The shared state is stored in a static property so all contexts in a scenario
 * see the same kernel, entity manager, and response state.
 */
trait BehatKernelTrait
{
    protected function state(): BehatState
    {
        return BehatState::instance();
    }

    protected function bootKernel(): void
    {
        $state = $this->state();

        // Load environment variables
        if (class_exists(\Symfony\Component\Dotenv\Dotenv::class)) {
            $dotenv = new \Symfony\Component\Dotenv\Dotenv();
            $dotenv->loadEnv(\dirname(__DIR__, 2).'/.env');
        }

        // Create fresh kernel for each scenario
        $state->kernel = new \App\Shared\Infrastructure\Symfony\Kernel('test', false);
        $state->kernel->boot();

        $state->entityManager = $state->kernel->getContainer()->get('doctrine.orm.entity_manager');

        // Initialize Foundry
        if (!\Zenstruck\Foundry\Configuration::isBooted()) {
            \Zenstruck\Foundry\Configuration::boot($state->kernel);
        }
    }

    protected function shutdownKernel(): void
    {
        $state = $this->state();
        if (null !== $state->kernel) {
            $state->kernel->shutdown();
        }
        $state->reset();
        BehatState::resetInstance();
    }

    protected function handleRequest(\Symfony\Component\HttpFoundation\Request $request): void
    {
        $state = $this->state();

        // Attach the scenario cookie jar so the session persists across requests.
        // Skipped when the step set a Cookie header explicitly (e.g. "only the cookie").
        if (!$request->headers->has('Cookie') && [] !== $state->cookieJar) {
            $pairs = [];
            foreach ($state->cookieJar as $name => $value) {
                $pairs[] = $name.'='.$value;
                $request->cookies->set($name, $value);
            }
            $request->headers->set('Cookie', implode('; ', $pairs));
        }

        $response = $state->kernel->handle($request, HttpKernelInterface::MAIN_REQUEST);

        $this->captureResponse($response);
        $this->updateCookieJar($response);

        $state->kernel->terminate($request, $response);
    }

    private function updateCookieJar(Response $response): void
    {
        $state = $this->state();
        foreach ($response->headers->getCookies() as $cookie) {
            $value = $cookie->getValue();
            $expired = null !== $cookie->getExpiresTime() && $cookie->getExpiresTime() <= time();
            if (null === $value || '' === $value || $expired) {
                unset($state->cookieJar[$cookie->getName()]);
            } else {
                $state->cookieJar[$cookie->getName()] = $value;
            }
        }
    }

    protected function captureResponse(Response $response): void
    {
        $state = $this->state();
        $state->lastResponseStatus = $response->getStatusCode();
        $state->lastResponseContent = $response->getContent();
        $state->lastResponseHeaders = $response->headers->all();

        // Capture cookies from Set-Cookie headers
        $state->responseCookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $state->responseCookies[$cookie->getName()] = $cookie->getValue();
        }
    }
}
