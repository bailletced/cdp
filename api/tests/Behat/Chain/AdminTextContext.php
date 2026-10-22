<?php

declare(strict_types=1);

namespace App\Tests\Behat\Chain;

use App\Chain\Domain\Repository\TextRepositoryInterface;
use App\Tests\Behat\BehatKernelTrait;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Symfony\Component\HttpFoundation\Request;

/**
 * Behat context for the /api/admin/texts endpoints (F06-T08, F06-T09).
 */
final class AdminTextContext implements Context
{
    use BehatKernelTrait;

    #[Given('a text exists with title :title and source :source')]
    public function aTextExistsWithTitleAndSource(string $title, string $source): void
    {
        $state = $this->state();
        $container = $state->kernel?->getContainer()->get('test.service_container');

        /** @var TextRepositoryInterface $textRepository */
        $textRepository = $container?->get(TextRepositoryInterface::class);

        $text = \App\Chain\Domain\Entity\Text::create(
            new \App\Chain\Domain\ValueObject\TextTitle($title),
            new \App\Chain\Domain\ValueObject\TextSource($source),
            new \App\Chain\Domain\ValueObject\RichText('<p>Corps du texte.</p>'),
        );

        $textRepository?->add($text);
        $state->entityManager?->flush();

        $state->capturedTextId = $text->getId()->value->toRfc4122();
    }

    #[When('I PATCH JSON-LD to :path with body:')]
    public function iPatchJsonLdToPathWithBody(string $path, \Behat\Gherkin\Node\PyStringNode $body): void
    {
        $request = Request::create($path, 'PATCH', [], [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $body->getRaw());
        $this->handleRequest($request);
    }

    #[When('I PATCH JSON-LD to the last created text with body:')]
    public function iPatchJsonLdToTheLastCreatedTextWithBody(\Behat\Gherkin\Node\PyStringNode $body): void
    {
        $state = $this->state();

        if (null === $state->capturedTextId) {
            throw new \RuntimeException('No text id captured. Create a text first.');
        }

        $request = Request::create('/api/admin/texts/'.$state->capturedTextId, 'PATCH', [], [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $body->getRaw());
        $this->handleRequest($request);
    }

    #[When('I DELETE the last created text')]
    public function iDeleteTheLastCreatedText(): void
    {
        $state = $this->state();

        if (null === $state->capturedTextId) {
            throw new \RuntimeException('No text id captured. Create a text first.');
        }

        $request = Request::create('/api/admin/texts/'.$state->capturedTextId, 'DELETE');
        $this->handleRequest($request);
    }

    #[Then('the response should contain a text id')]
    public function theResponseShouldContainATextId(): void
    {
        $state = $this->state();
        $data = json_decode($state->lastResponseContent ?? '', true);

        $id = $data['id'] ?? null;
        if (null === $id || !\is_string($id)) {
            throw new \RuntimeException('Response does not contain a text id');
        }

        $state->capturedTextId = $id;
    }

    #[Then('the response should contain a violation on :property')]
    public function theResponseShouldContainAViolationOn(string $property): void
    {
        $state = $this->state();
        $data = json_decode($state->lastResponseContent ?? '', true);

        $violations = $data['violations'] ?? [];
        foreach ($violations as $violation) {
            if (($violation['propertyPath'] ?? null) === $property) {
                return;
            }
        }

        throw new \RuntimeException(\sprintf('No violation found on property "%s"', $property));
    }

    #[Then('the text :title should not exist')]
    public function theTextShouldNotExist(string $title): void
    {
        $state = $this->state();

        // Query the database directly: the HTTP DELETE ran in its own
        // transaction, and the entity manager may hold stale entities.
        $connection = $state->entityManager?->getConnection();
        $count = $connection?->fetchOne(
            'SELECT COUNT(*) FROM text WHERE title = :title',
            ['title' => $title],
        );

        if ((int) $count > 0) {
            throw new \RuntimeException(\sprintf('Text "%s" still exists', $title));
        }
    }
}
