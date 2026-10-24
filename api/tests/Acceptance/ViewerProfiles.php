<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Access\Domain\Entity\Account;
use App\Access\Domain\Entity\DevicePass;
use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\ValueObject\AccountId;
use App\Access\Infrastructure\Symfony\Security\UserProvider;
use App\Chain\Domain\Entity\Edition;
use App\Chain\Domain\Entity\Inscription;
use App\Chain\Domain\Entity\Priant;
use App\Chain\Domain\ValueObject\DisplayName;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Factory\Access\OrganizerFactory;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Symfony\Component\BrowserKit\Cookie;

/**
 * The five profiles of the rights matrix (F10-T09, US-10.4), for the
 * acceptance tests of every resource whose fields depend on the viewer:
 *
 * - « visiteur » : no pass, no session
 * - « pass » : the device that registered Marie, no session (RG-IN-6)
 * - « priant vérifié » : Marie, signed in, registered in the edition
 * - « autre priant » : Jean, signed in, registered in no slot of the edition
 * - « organisateur » : Anne, signed in, registered nowhere
 *
 * The using test case owns $client (HTTPS, as in production: the cookies
 * are Secure) and $em.
 */
trait ViewerProfiles
{
    private const string DEVICE_TOKEN = 'device-token-of-maries-phone';

    private Inscription $mariesInscription;

    /**
     * @return iterable<string, array{string}>
     */
    public static function viewerProfiles(): iterable
    {
        foreach (['visiteur', 'pass', 'priant vérifié', 'autre priant', 'organisateur'] as $profile) {
            yield $profile => [$profile];
        }
    }

    /**
     * Marie registered on the first slot of the edition (from her phone),
     * Jean known but not registered, Anne organizer.
     */
    private function givenTheProfilesOf(Edition $edition): void
    {
        $marie = new Priant(new Email('marie@bailletced.org'), new DisplayName('Marie Dupont'));
        $jean = new Priant(new Email('jean@bailletced.org'), new DisplayName('Jean Martin'));
        $this->mariesInscription = new Inscription($edition->getId(), $edition->getSlots()[0]->getId(), $marie->getId());
        foreach ([$marie, $jean, $this->mariesInscription] as $entity) {
            $this->em->persist($entity);
        }

        $this->em->persist(new Account(new AccountId(), new Email('marie@bailletced.org'), 1, (string) $marie->getId()));
        $this->em->persist(new Account(new AccountId(), new Email('jean@bailletced.org'), 1, (string) $jean->getId()));
        $anne = new Account(new AccountId(), new Email('anne@bailletced.org'));
        $this->em->persist($anne);
        $this->em->persist(OrganizerFactory::create($anne->getId()->value, $anne->getId()->value));

        $this->em->persist(new DevicePass(self::DEVICE_TOKEN, [(string) $this->mariesInscription->getId()], $edition->getEndsAt()->modify('+7 days'), new \DateTimeImmutable()));
        $this->em->flush();
    }

    private function actAs(string $profile): void
    {
        match ($profile) {
            'visiteur' => null,
            'pass' => $this->client->getCookieJar()->set(new Cookie('cdp_pass', self::DEVICE_TOKEN, null, '/', 'localhost', true, true)),
            'priant vérifié' => $this->logInAs('marie@bailletced.org'),
            'autre priant' => $this->logInAs('jean@bailletced.org'),
            'organisateur' => $this->logInAs('anne@bailletced.org'),
            default => throw new \LogicException(\sprintf('Unknown profile « %s ».', $profile)),
        };
    }

    /**
     * Authenticate through the login-link flow: generate a signed link, POST
     * it, keep the session cookie.
     */
    private function logInAs(string $email): void
    {
        $container = self::getContainer();
        $account = $container->get(AccountRepositoryInterface::class)->findByEmail(new Email($email));
        \assert($account instanceof Account);

        $securityUser = $container->get(UserProvider::class)->loadUserByIdentifier((string) $account->getId()->value);
        $url = $container->get('security.authenticator.login_link_handler.main')->createLoginLink($securityUser)->getUrl();

        $parts = parse_url($url);
        \assert(\is_string($parts['path'] ?? null));
        parse_str($parts['query'] ?? '', $params);
        $params['_target_path'] = '/my-space';

        $this->client->request('POST', $parts['path'], $params);
    }

    /**
     * Validates a decoded JSON body against tests/Acceptance/Schema/<name>.json.
     * The schemas forbid additional properties: a new field fails the matrix
     * until its visibility is decided (US-10.4).
     */
    private static function assertMatchesJsonSchema(string $json, string $name): void
    {
        $data = json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        $validator = new Validator();
        $validator->validate($data, (object) ['$ref' => 'file://'.__DIR__.'/Schema/'.$name.'.json'], Constraint::CHECK_MODE_VALIDATE_SCHEMA);

        self::assertTrue($validator->isValid(), \sprintf(
            "The response does not match the « %s » schema:\n%s",
            $name,
            implode("\n", array_map(static fn (array $error): string => \sprintf('- %s: %s', $error['property'], $error['message']), $validator->getErrors())),
        ));
    }
}
