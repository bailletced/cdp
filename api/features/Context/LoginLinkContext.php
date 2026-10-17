<?php

declare(strict_types=1);

namespace App\Features\Context;

use App\Access\Domain\Account;
use App\Access\Domain\ValueObject\AccountId;
use App\Shared\Domain\ValueObject\Email;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Contexte pour les tests de login_link (F02-T08).
 * 
 * Contient les步骤 spécifiques à l'authentification par lien personnel.
 */
class LoginLinkContext implements Context
{
    private FeatureContext $featureContext;
    private ?string $lastResponseContent = null;
    private ?int $lastResponseStatus = null;
    private ?array $lastResponseHeaders = null;

    public function __construct(FeatureContext $featureContext)
    {
        $this->featureContext = $featureContext;
    }

    /**
     * @Given un compte utilisateur existe
     */
    public function accountExists(): Account
    {
        $account = new Account(
            new AccountId(Uuid::v7()),
            new Email('test-' . Uuid::v7()->toRfc4122() . '@example.com'),
            1
        );
        
        $em = $this->featureContext->getEntityManager();
        $em->persist($account);
        $em->flush();
        
        $this->featureContext->registerEntityForCleanup($account);
        
        return $account;
    }

    /**
     * @Given un lien de connexion valide est généré pour ce compte avec cible :targetPath
     */
    public function validLoginLinkGeneratedForAccountWithTarget(Account $account, string $targetPath): array
    {
        $container = $this->featureContext->getContainer();
        
        $userProvider = $container->get('App\\Access\\Infrastructure\\Security\\UserProvider');
        $securityUser = $userProvider->loadUserByIdentifier($account->getId()->value->toRfc4122());
        
        $handler = $container->get('security.authenticator.login_link_handler.main');
        $loginLinkDetails = $handler->createLoginLink($securityUser);
        
        $url = $loginLinkDetails->getUrl();
        parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
        
        return [
            'user' => $queryParams['user'] ?? '',
            'expires' => $queryParams['expires'] ?? '',
            'hash' => $queryParams['hash'] ?? '',
            '_target_path' => $targetPath,
        ];
    }

    /**
     * @Given un lien de connexion expiré est généré pour ce compte
     */
    public function expiredLoginLinkGeneratedForAccount(Account $account): array
    {
        $params = $this->validLoginLinkGeneratedForAccountWithTarget($account, '/mon-espace');
        // Forcer l'expiration en mettant un timestamp dans le passé
        $params['expires'] = time() - 3600; // Il y a 1 heure
        return $params;
    }

    /**
     * @Given le hash du lien est altéré
     */
    public function hashIsAltered(array &$params): void
    {
        $params['hash'] = $params['hash'] . 'ALTERED';
    }

    /**
     * @Given le compte est supprimé
     */
    public function accountIsDeleted(Account $account): void
    {
        $em = $this->featureContext->getEntityManager();
        $em->remove($account);
        $em->flush();
    }

    /**
     * @When je soumets le lien de connexion via POST
     */
    public function submitLoginLinkViaPost(array $params): void
    {
        $container = $this->featureContext->getContainer();
        $kernel = $container->get('kernel');
        
        $request = Request::create('/auth/connexion', 'POST', $params);
        
        $response = $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST);
        
        $this->lastResponseStatus = $response->getStatusCode();
        $this->lastResponseContent = $response->getContent();
        $this->lastResponseHeaders = $response->headers->all();
        
        $response->send();
        $kernel->terminate($request, $response);
    }

    /**
     * @When je fais une requête GET sur :path
     */
    public function makeGetRequest(string $path): void
    {
        $container = $this->featureContext->getContainer();
        $kernel = $container->get('kernel');
        
        $request = Request::create($path, 'GET');
        
        $response = $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST);
        
        $this->lastResponseStatus = $response->getStatusCode();
        $this->lastResponseContent = $response->getContent();
        $this->lastResponseHeaders = $response->headers->all();
        
        $response->send();
        $kernel->terminate($request, $response);
    }

    /**
     * @When je soumets un lien de connexion invalide via POST
     */
    public function submitInvalidLoginLinkViaPost(): void
    {
        $this->submitLoginLinkViaPost([
            'user' => 'invalid_user_id',
            'expires' => time() + 3600,
            'hash' => 'invalid_hash',
        ]);
    }

    /**
     * @Then la réponse doit être :expectedStatus
     */
    public function responseMustBe(string $expectedStatus): void
    {
        $statusCode = $this->mapStatusStringToCode($expectedStatus);
        
        if ($this->lastResponseStatus !== $statusCode) {
            throw new \RuntimeException(
                \sprintf('Expected status %d (%s), got %d', $statusCode, $expectedStatus, $this->lastResponseStatus)
            );
        }
    }

    /**
     * @Then la réponse doit contenir JSON avec :key égal à :expectedValue
     */
    public function responseMustContainJsonWithEqualTo(string $key, string $expectedValue): void
    {
        $data = json_decode($this->lastResponseContent, true);
        
        if (!isset($data[$key])) {
            throw new \RuntimeException("JSON response does not contain key '$key'");
        }
        
        if ($data[$key] !== $expectedValue) {
            throw new \RuntimeException(
                \sprintf("Expected '%s' for key '$key', got '%s'", $expectedValue, $data[$key])
            );
        }
    }

    /**
     * @Then la réponse doit rediriger vers :expectedPath
     */
    public function responseMustRedirectTo(string $expectedPath): void
    {
        if (!isset($this->lastResponseHeaders['location'])) {
            throw new \RuntimeException('Response does not have Location header');
        }
        
        $location = $this->lastResponseHeaders['location'][0] ?? '';
        if (!str_contains($location, $expectedPath)) {
            throw new \RuntimeException(
                \sprintf("Expected redirect to contain '%s', got '%s'", $expectedPath, $location)
            );
        }
    }

    /**
     * Mappe le nom du statut HTTP à son code.
     */
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
