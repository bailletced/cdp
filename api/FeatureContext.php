<?php

use App\Access\Domain\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Hook\BeforeScenario;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\When;
use Behat\Step\Then;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Uid\Uuid;

/**
 * FeatureContext for Behat 4.
 * Must be in global namespace for Behat 4 default context loading.
 * Creates and manages its own kernel instance.
 */
final class FeatureContext implements Context
{
    private ?KernelInterface $kernel = null;
    private ?EntityManagerInterface $entityManager = null;
    private array $generatedEntities = [];
    private ?string $lastResponseContent = null;
    private ?int $lastResponseStatus = null;
    private ?array $lastResponseHeaders = null;
    private ?Account $currentAccount = null;
    private ?array $loginLinkParams = null;

    #[BeforeScenario]
    public function setupScenario(BeforeScenarioScope $scope): void
    {
        // Clear state
        $this->generatedEntities = [];
        $this->lastResponseContent = null;
        $this->lastResponseStatus = null;
        $this->lastResponseHeaders = null;
        $this->currentAccount = null;
        $this->loginLinkParams = null;
        
        // Load environment variables
        if (class_exists(\Symfony\Component\Dotenv\Dotenv::class)) {
            $dotenv = new \Symfony\Component\Dotenv\Dotenv();
            $dotenv->loadEnv(__DIR__ . '/.env');
        }
        
        // Create fresh kernel for each scenario
        $this->kernel = new \App\Shared\Infrastructure\Symfony\Kernel('test', false);
        $this->kernel->boot();
        
        $this->entityManager = $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
        
        // Initialize Foundry
        if (!\Zenstruck\Foundry\Configuration::isBooted()) {
            \Zenstruck\Foundry\Configuration::boot($this->kernel);
        }
    }

    #[AfterScenario]
    public function teardownScenario(AfterScenarioScope $scope): void
    {
        // Clear state
        $this->generatedEntities = [];
        $this->currentAccount = null;
        $this->loginLinkParams = null;
        
        if ($this->kernel !== null) {
            $this->kernel->shutdown();
        }
    }

    #[Given('le système est initialisé')]
    public function systemIsInitialized(): void
    {
        // Already initialized in setupScenario
    }

    #[Given('un compte utilisateur existe')]
    public function accountExists(): void
    {
        // Use Foundry factory to create and persist account
        $account = \App\Tests\Factory\AccountFactory::new()->create();
        
        $this->generatedEntities[] = $account;
        $this->currentAccount = $account;
    }

    #[Given('un lien de connexion valide est généré pour ce compte avec cible :targetPath')]
    public function validLoginLinkGeneratedForAccountWithTarget(string $targetPath): void
    {
        if ($this->currentAccount === null) {
            throw new \RuntimeException('No account available. Create an account first.');
        }
        
        $container = $this->kernel->getContainer();
        
        // Use test.service_container to access private services
        $testContainer = $container->get('test.service_container');
        
        /** @var \App\Access\Infrastructure\Security\UserProvider $userProvider */
        $userProvider = $testContainer->get(\App\Access\Infrastructure\Security\UserProvider::class);
        $securityUser = $userProvider->loadUserByIdentifier($this->currentAccount->getId()->value->toRfc4122());
        
        /** @var \Symfony\Component\Security\Http\Authenticator\LoginLinkHandlerInterface $handler */
        $handler = $testContainer->get('security.authenticator.login_link_handler.main');
        $loginLinkDetails = $handler->createLoginLink($securityUser);
        
        $url = $loginLinkDetails->getUrl();
        parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
        
        $this->loginLinkParams = [
            'user' => $queryParams['user'] ?? '',
            'expires' => $queryParams['expires'] ?? '',
            'hash' => $queryParams['hash'] ?? '',
            '_target_path' => $targetPath,
        ];
    }

    #[Given('un lien de connexion valide est généré pour ce compte')]
    public function validLoginLinkGeneratedForAccountSimple(): void
    {
        $this->validLoginLinkGeneratedForAccountWithTarget('/mon-espace');
    }

    #[Given('un lien de connexion expiré est généré pour ce compte')]
    public function expiredLoginLinkGeneratedForAccount(): void
    {
        $this->validLoginLinkGeneratedForAccountWithTarget('/mon-espace');
        // Cast to string: LoginLinkHandler requires expires to be a numeric string
        $this->loginLinkParams['expires'] = (string) (time() - 3600);
    }

    #[Given('le hash du lien est altéré')]
    public function hashIsAltered(): void
    {
        if ($this->loginLinkParams === null) {
            throw new \RuntimeException('No login link params available.');
        }
        $this->loginLinkParams['hash'] = $this->loginLinkParams['hash'] . 'ALTERED';
    }

    #[Given('le compte est supprimé')]
    public function accountIsDeleted(): void
    {
        if ($this->currentAccount === null) {
            throw new \RuntimeException('No account available.');
        }
        
        // Reload the entity from database to ensure it's managed
        $accountId = $this->currentAccount->getId()->value->toRfc4122();
        $account = $this->entityManager->getRepository(Account::class)->find($accountId);
        
        if ($account !== null) {
            $this->entityManager->remove($account);
            $this->entityManager->flush();
        }
    }

    #[When('je soumets le lien de connexion via POST')]
    public function submitLoginLinkViaPost(): void
    {
        if ($this->loginLinkParams === null) {
            throw new \RuntimeException('No login link params available.');
        }
        
        $request = Request::create('/auth/connexion', 'POST', $this->loginLinkParams);
        
        $response = $this->kernel->handle($request, HttpKernelInterface::MAIN_REQUEST);
        
        $this->lastResponseStatus = $response->getStatusCode();
        $this->lastResponseContent = $response->getContent();
        $this->lastResponseHeaders = $response->headers->all();
        
        $this->kernel->terminate($request, $response);
    }

    #[When('je soumets le lien de connexion expiré via POST')]
    public function submitExpiredLoginLinkViaPost(): void
    {
        $this->submitLoginLinkViaPost();
    }

    #[When('je soumets le lien de connexion altéré via POST')]
    public function submitAlteredLoginLinkViaPost(): void
    {
        $this->submitLoginLinkViaPost();
    }

    #[When('je fais une requête GET sur :path')]
    public function makeGetRequest(string $path): void
    {
        $request = Request::create($path, 'GET');
        
        $response = $this->kernel->handle($request, HttpKernelInterface::MAIN_REQUEST);
        
        $this->lastResponseStatus = $response->getStatusCode();
        $this->lastResponseContent = $response->getContent();
        $this->lastResponseHeaders = $response->headers->all();
        
        $this->kernel->terminate($request, $response);
    }

    #[When('je soumets un lien de connexion invalide via POST')]
    public function submitInvalidLoginLinkViaPost(): void
    {
        $request = Request::create('/auth/connexion', 'POST', [
            'user' => 'invalid_user_id',
            'expires' => time() + 3600,
            'hash' => 'invalid_hash',
        ]);
        
        $response = $this->kernel->handle($request, HttpKernelInterface::MAIN_REQUEST);
        
        $this->lastResponseStatus = $response->getStatusCode();
        $this->lastResponseContent = $response->getContent();
        $this->lastResponseHeaders = $response->headers->all();
        
        $this->kernel->terminate($request, $response);
    }

    #[Then('la réponse doit être :expectedStatus')]
    public function responseMustBe(string $expectedStatus): void
    {
        $statusCode = $this->mapStatusStringToCode($expectedStatus);
        
        if ($this->lastResponseStatus !== $statusCode) {
            throw new \RuntimeException(
                sprintf('Expected status %d (%s), got %d', $statusCode, $expectedStatus, $this->lastResponseStatus)
            );
        }
    }

    #[Then('la réponse doit contenir JSON avec :key égal à :expectedValue')]
    public function responseMustContainJsonWithEqualTo(string $key, string $expectedValue): void
    {
        if ($this->lastResponseContent === null) {
            throw new \RuntimeException('No response content available');
        }
        
        $data = json_decode($this->lastResponseContent, true);
        
        if (!isset($data[$key])) {
            throw new \RuntimeException("JSON response does not contain key '$key'");
        }
        
        if ($data[$key] !== $expectedValue) {
            throw new \RuntimeException(
                sprintf("Expected '%s' for key '$key', got '%s'", $expectedValue, $data[$key])
            );
        }
    }

    #[Then('la réponse doit rediriger vers :expectedPath')]
    public function responseMustRedirectTo(string $expectedPath): void
    {
        if (!isset($this->lastResponseHeaders['location'])) {
            throw new \RuntimeException('Response does not have Location header');
        }
        
        $location = $this->lastResponseHeaders['location'][0] ?? '';
        if (!str_contains($location, $expectedPath)) {
            throw new \RuntimeException(
                sprintf("Expected redirect to contain '%s', got '%s'", $expectedPath, $location)
            );
        }
    }

    private function mapStatusStringToCode(string $statusString): int
    {
        $map = [
            '200 OK' => Response::HTTP_OK,
            '401 Unauthorized' => Response::HTTP_UNAUTHORIZED,
            '302 Found' => Response::HTTP_FOUND,
            '400 Bad Request' => Response::HTTP_BAD_REQUEST,
            '404 Not Found' => Response::HTTP_NOT_FOUND,
            '500 Internal Server Error' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ];
        
        return $map[$statusString] ?? 0;
    }
}
