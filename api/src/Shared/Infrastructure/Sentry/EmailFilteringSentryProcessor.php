<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Sentry;

use Sentry\Event;

/**
 * Sentry event processor that filters email addresses (F03-T19).
 * Removes or masks any email found in user data, extra, tags
 * and exception messages before the event is sent to Sentry.
 */
final class EmailFilteringSentryProcessor
{
    private const EMAIL_PATTERN = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';

    private const FILTERED_PLACEHOLDER = '[FILTERED]';

    public function __invoke(Event $event): Event
    {
        $this->filterUser($event);
        $this->filterExtra($event);
        $this->filterTags($event);
        $this->filterExceptions($event);

        return $event;
    }

    private function filterUser(Event $event): void
    {
        $user = $event->getUser();
        if (null === $user) {
            return;
        }

        // Remove the email entirely (it's PII).
        $user->setEmail(null);

        // Mask any email found in other user fields.
        $username = $user->getUsername();
        if (null !== $username) {
            $user->setUsername($this->maskEmails($username));
        }

        $id = $user->getId();
        if (\is_string($id)) {
            $user->setId($this->maskEmails($id));
        }

        $metadata = $user->getMetadata();
        foreach ($metadata as $key => $value) {
            if (\is_string($value)) {
                $user->setMetadata($key, $this->maskEmails($value));
            }
        }
    }

    private function filterExtra(Event $event): void
    {
        $extra = $event->getExtra();
        if ([] === $extra) {
            return;
        }

        foreach ($extra as $key => $value) {
            $extra[$key] = $this->filterValue($value);
        }

        $event->setExtra($extra);
    }

    private function filterTags(Event $event): void
    {
        $tags = $event->getTags();
        if ([] === $tags) {
            return;
        }

        foreach ($tags as $key => $value) {
            if ($this->containsEmail($value)) {
                $tags[$key] = self::FILTERED_PLACEHOLDER;
            }
        }

        $event->setTags($tags);
    }

    private function filterExceptions(Event $event): void
    {
        $exceptions = $event->getExceptions();
        if ([] === $exceptions) {
            return;
        }

        foreach ($exceptions as $exception) {
            $exception->setValue($this->maskEmails($exception->getValue()));
            $exception->setType($this->maskEmails($exception->getType()));
        }
    }

    /**
     * Filters a single value: a string containing an email is replaced or
     * masked, an array is filtered recursively, anything else passes through.
     */
    private function filterValue(mixed $value): mixed
    {
        if (\is_string($value)) {
            return $this->containsEmail($value)
                ? self::FILTERED_PLACEHOLDER
                : $this->maskEmails($value);
        }

        if (\is_array($value)) {
            $filtered = [];
            foreach ($value as $key => $item) {
                $filtered[$key] = $this->filterValue($item);
            }

            return $filtered;
        }

        return $value;
    }

    private function containsEmail(string $value): bool
    {
        return 1 === preg_match(self::EMAIL_PATTERN, $value);
    }

    private function maskEmails(string $value): string
    {
        return (string) preg_replace(self::EMAIL_PATTERN, self::FILTERED_PLACEHOLDER, $value);
    }
}
