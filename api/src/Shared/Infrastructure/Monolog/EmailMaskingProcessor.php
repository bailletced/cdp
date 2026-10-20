<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monolog;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Monolog processor that masks email addresses in log records (F03-T21).
 * Applied to message, context and extra so no email leaks into the
 * JSON logs written to stderr in production.
 */
final class EmailMaskingProcessor implements ProcessorInterface
{
    private const EMAIL_PATTERN = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';

    private const MASKED_PLACEHOLDER = '[FILTERED]';

    public function __invoke(LogRecord $record): LogRecord
    {
        return new LogRecord(
            datetime: $record->datetime,
            channel: $record->channel,
            level: $record->level,
            message: $this->mask($record->message),
            context: $this->maskArray($record->context),
            extra: $this->maskArray($record->extra),
        );
    }

    private function mask(string $value): string
    {
        return (string) preg_replace(self::EMAIL_PATTERN, self::MASKED_PLACEHOLDER, $value);
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private function maskArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (\is_string($value)) {
                $data[$key] = $this->mask($value);
            } elseif (\is_array($value)) {
                $data[$key] = $this->maskArray($value);
            }
        }

        return $data;
    }
}
