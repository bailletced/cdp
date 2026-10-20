<?php

declare(strict_types=1);

namespace App\Tests\Integration\Access\Infrastructure\Console;

use App\Access\Domain\Repository\AccountRepositoryInterface;
use App\Access\Domain\Repository\OrganizerRepositoryInterface;
use App\Access\Infrastructure\Console\CreateOrganizerCliCommand;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class CreateOrganizerCliCommandIntegrationTest extends KernelTestCase
{
    private AccountRepositoryInterface $accountRepository;

    private OrganizerRepositoryInterface $organizerRepository;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->accountRepository = $container->get(AccountRepositoryInterface::class);
        $this->organizerRepository = $container->get(OrganizerRepositoryInterface::class);
        $this->em = $container->get('doctrine')->getManager();

        // Clean up test data
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE organizer CASCADE');
        $connection->executeStatement('TRUNCATE account CASCADE');
        $this->em->clear();
    }

    public function testCommandCreatesOrganizer(): void
    {
        $container = self::getContainer();
        $command = $container->get(CreateOrganizerCliCommand::class);

        $input = new ArrayInput([
            'email' => 'test-create@bailletced.org',
        ]);

        $output = new BufferedOutput();
        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
        $outputString = $output->fetch();
        $this->assertStringContainsString('Created new account', $outputString);
        $this->assertStringContainsString('Successfully created organizer', $outputString);

        // Verify in database
        $this->em->clear();
        $account = $this->accountRepository->findByEmail(new Email('test-create@bailletced.org'));
        $this->assertNotNull($account);
        $this->assertTrue($this->organizerRepository->isOrganizer($account->getId()->value));
    }

    public function testCommandIsIdempotent(): void
    {
        $container = self::getContainer();
        $command = $container->get(CreateOrganizerCliCommand::class);

        // First call
        $input1 = new ArrayInput([
            'email' => 'test-idempotent@bailletced.org',
        ]);
        $output1 = new BufferedOutput();
        $command->run($input1, $output1);

        // Second call
        $input2 = new ArrayInput([
            'email' => 'test-idempotent@bailletced.org',
        ]);
        $output2 = new BufferedOutput();
        $exitCode = $command->run($input2, $output2);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('already an organizer', $output2->fetch());
    }

    public function testCommandValidatesEmail(): void
    {
        $container = self::getContainer();
        $command = $container->get(CreateOrganizerCliCommand::class);

        $input = new ArrayInput([
            'email' => 'invalid-email',
        ]);

        $output = new BufferedOutput();
        $exitCode = $command->run($input, $output);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Invalid email address', $output->fetch());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE organizer CASCADE');
        $connection->executeStatement('TRUNCATE account CASCADE');
        $this->em->clear();
    }
}
