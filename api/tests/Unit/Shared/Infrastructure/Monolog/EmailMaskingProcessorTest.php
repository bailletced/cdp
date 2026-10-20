<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Monolog;

use App\Shared\Infrastructure\Monolog\EmailMaskingProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EmailMaskingProcessor (F03-T21).
 * Done when: a log record containing an email is masked.
 */
final class EmailMaskingProcessorTest extends TestCase
{
    private EmailMaskingProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new EmailMaskingProcessor();
    }

    public function testItMasksEmailInMessage(): void
    {
        $record = $this->createRecord('Sending personal link to priant@bailletced.org');

        $masked = ($this->processor)($record);

        $this->assertSame('Sending personal link to [FILTERED]', $masked->message);
        $this->assertStringNotContainsString('priant@bailletced.org', $masked->message);
    }

    public function testItMasksEmailInContext(): void
    {
        $record = $this->createRecord('Link requested', [
            'email' => 'marie@example.org',
            'name' => 'Marie',
        ]);

        $masked = ($this->processor)($record);

        $this->assertSame('[FILTERED]', $masked->context['email']);
        $this->assertSame('Marie', $masked->context['name']);
    }

    public function testItMasksEmailInNestedContext(): void
    {
        $record = $this->createRecord('Request', [
            'user' => ['email' => 'thomas@example.net'],
        ]);

        $masked = ($this->processor)($record);

        $this->assertSame('[FILTERED]', $masked->context['user']['email']);
    }

    public function testItMasksEmailInExtra(): void
    {
        $record = $this->createRecord('Event', [], ['email' => 'admin@bailletced.org']);

        $masked = ($this->processor)($record);

        $this->assertSame('[FILTERED]', $masked->extra['email']);
    }

    public function testItMasksMultipleEmailsInOneString(): void
    {
        $record = $this->createRecord('From jean@bailletced.org to marie@example.org');

        $masked = ($this->processor)($record);

        $this->assertSame('From [FILTERED] to [FILTERED]', $masked->message);
    }

    public function testItLeavesRecordUnchangedWhenNoEmail(): void
    {
        $record = $this->createRecord('Plain message', ['key' => 'value']);

        $masked = ($this->processor)($record);

        $this->assertSame('Plain message', $masked->message);
        $this->assertSame(['key' => 'value'], $masked->context);
        $this->assertSame($record->channel, $masked->channel);
        $this->assertSame($record->level, $masked->level);
        $this->assertSame($record->datetime, $masked->datetime);
    }

    /**
     * @param array<mixed> $context
     * @param array<mixed> $extra
     */
    private function createRecord(string $message, array $context = [], array $extra = []): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'app',
            level: Level::Info,
            message: $message,
            context: $context,
            extra: $extra,
        );
    }
}
