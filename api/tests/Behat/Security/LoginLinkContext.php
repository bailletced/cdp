<?php

declare(strict_types=1);

namespace App\Tests\Behat\Security;

use App\Access\Domain\Entity\Account;
use App\Tests\Behat\BehatKernelTrait;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\When;
use Symfony\Component\HttpFoundation\Request;

/**
 * Behat context for the login_link bounded context (F02-T07, F02-T08).
 */
final class LoginLinkContext implements Context
{
    use BehatKernelTrait;

    #[Given('an account exists')]
    public function accountExists(): void
    {
        // Use Foundry factory to create and persist account
        $account = \App\Tests\Factory\AccountFactory::new()->create();

        $this->state()->currentAccount = $account;
    }

    #[Given('a valid login link is generated for this account with target :targetPath')]
    public function validLoginLinkGeneratedForAccountWithTarget(string $targetPath): void
    {
        $state = $this->state();
        if ($state->currentAccount === null) {
            throw new \RuntimeException('No account available. Create an account first.');
        }

        $container = $state->kernel->getContainer();

        // Use test.service_container to access private services
        $testContainer = $container->get('test.service_container');

        /** @var \App\Access\Infrastructure\Security\UserProvider $userProvider */
        $userProvider = $testContainer->get(\App\Access\Infrastructure\Security\UserProvider::class);
        $securityUser = $userProvider->loadUserByIdentifier($state->currentAccount->getId()->value->toRfc4122());

        /** @var \Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface $handler */
        $handler = $testContainer->get('security.authenticator.login_link_handler.main');
        $loginLinkDetails = $handler->createLoginLink($securityUser);

        $url = $loginLinkDetails->getUrl();
        parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);

        $state->loginLinkParams = [
            'user' => $queryParams['user'] ?? '',
            'expires' => $queryParams['expires'] ?? '',
            'hash' => $queryParams['hash'] ?? '',
            '_target_path' => $targetPath,
        ];
    }

    #[Given('a valid login link is generated for this account')]
    public function validLoginLinkGeneratedForAccountSimple(): void
    {
        $this->validLoginLinkGeneratedForAccountWithTarget('/mon-espace');
    }

    #[Given('an expired login link is generated for this account')]
    public function expiredLoginLinkGeneratedForAccount(): void
    {
        $this->validLoginLinkGeneratedForAccountWithTarget('/mon-espace');
        // Cast to string: LoginLinkHandler requires expires to be a numeric string
        $this->state()->loginLinkParams['expires'] = (string) (time() - 3600);
    }

    #[Given('the link hash is tampered')]
    public function hashIsAltered(): void
    {
        $state = $this->state();
        if ($state->loginLinkParams === null) {
            throw new \RuntimeException('No login link params available.');
        }
        $state->loginLinkParams['hash'] = $state->loginLinkParams['hash'] . 'ALTERED';
    }

    #[Given('the account is deleted')]
    public function accountIsDeleted(): void
    {
        $state = $this->state();
        if ($state->currentAccount === null) {
            throw new \RuntimeException('No account available.');
        }

        // Reload the entity from database to ensure it's managed
        $accountId = $state->currentAccount->getId()->value->toRfc4122();
        $account = $state->entityManager->getRepository(Account::class)->find($accountId);

        if ($account !== null) {
            $state->entityManager->remove($account);
            $state->entityManager->flush();
        }
    }

    #[When('I submit the login link via POST')]
    public function submitLoginLinkViaPost(): void
    {
        $state = $this->state();
        if ($state->loginLinkParams === null) {
            throw new \RuntimeException('No login link params available.');
        }

        $request = Request::create('/auth/connexion', 'POST', $state->loginLinkParams);
        $this->handleRequest($request);
    }

    #[When('I submit the expired login link via POST')]
    public function submitExpiredLoginLinkViaPost(): void
    {
        $this->submitLoginLinkViaPost();
    }

    #[When('I submit the tampered login link via POST')]
    public function submitAlteredLoginLinkViaPost(): void
    {
        $this->submitLoginLinkViaPost();
    }

    #[When('I submit an invalid login link via POST')]
    public function submitInvalidLoginLinkViaPost(): void
    {
        $request = Request::create('/auth/connexion', 'POST', [
            'user' => 'invalid_user_id',
            'expires' => time() + 3600,
            'hash' => 'invalid_hash',
        ]);

        $this->handleRequest($request);
    }

    #[When('I submit the login link via POST with Origin :origin')]
    public function submitLoginLinkViaPostWithOrigin(string $origin): void
    {
        $state = $this->state();
        if ($state->loginLinkParams === null) {
            throw new \RuntimeException('No login link params available.');
        }

        $request = Request::create('/auth/connexion', 'POST', $state->loginLinkParams);
        $request->headers->set('Origin', $origin);
        $this->handleRequest($request);
    }

    #[When('I submit the login link via POST with Sec-Fetch-Site :secFetchSite')]
    public function submitLoginLinkViaPostWithSecFetchSite(string $secFetchSite): void
    {
        $state = $this->state();
        if ($state->loginLinkParams === null) {
            throw new \RuntimeException('No login link params available.');
        }

        $request = Request::create('/auth/connexion', 'POST', $state->loginLinkParams);
        $request->headers->set('Sec-Fetch-Site', $secFetchSite);
        $this->handleRequest($request);
    }
}
