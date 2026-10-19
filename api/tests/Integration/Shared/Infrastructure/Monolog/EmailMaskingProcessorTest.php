<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Monolog;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration test for the Monolog email masking wiring (F03-T21).
 * Done when: a journal entry containing an email is masked on disk.
 */
final class EmailMaskingProcessorTest extends KernelTestCase
{
    public function testLoggedEmailIsMaskedInLogFile(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $logger = $container->get('logger');
        $this->assertInstanceOf(LoggerInterface::class, $logger);

        // Unique email so the assertion is not polluted by previous runs.
        $email = 'priant-'.bin2hex(random_bytes(6)).'@example.com';
        $logger->error('Sending personal link to '.$email, ['email' => $email]);

        $logFile = $container->getParameter('kernel.logs_dir')
            .'/'.$container->getParameter('kernel.environment').'.log';
        $this->assertFileExists($logFile);

        $content = (string) file_get_contents($logFile);
        $this->assertStringNotContainsString($email, $content);
        $this->assertStringContainsString('[FILTERED]', $content);
    }
}
