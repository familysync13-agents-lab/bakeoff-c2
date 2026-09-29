<?php

namespace App\Support;

use Sentry\Event;
use Sentry\EventHint;

/**
 * Sentry `before_send` hook: removes server-side secrets (V0_SECRET_CANARY, the share-link signing key) from every string
 * in an error event before it leaves the server.
 */
class SentryScrubber
{
    public const REDACTED = '[Filtered]';

    public static function beforeSend(Event $event, ?EventHint $hint = null): Event
    {
        $secrets = self::secrets();
        if ($secrets === []) {
            return $event;
        }

        $text = static fn (string $value): string => str_ireplace($secrets, self::REDACTED, $value);
        $data = static fn (array $value): array => self::scrubArray($value, $secrets);

        $message = $event->getMessage();
        if ($message !== null) {
            $formatted = $event->getMessageFormatted();
            $event->setMessage($text($message), $data($event->getMessageParams()), $formatted === null ? null : $text($formatted));
        }

        foreach ($event->getExceptions() as $exception) {
            $exception->setValue($text($exception->getValue()));

            foreach ($exception->getStacktrace()?->getFrames() ?? [] as $frame) {
                $contextLine = $frame->getContextLine();
                $frame->setVars($data($frame->getVars()))
                    ->setPreContext(array_map($text, $frame->getPreContext()))
                    ->setContextLine($contextLine === null ? null : $text($contextLine))
                    ->setPostContext(array_map($text, $frame->getPostContext()));
            }
        }

        $breadcrumbs = [];
        foreach ($event->getBreadcrumbs() as $breadcrumb) {
            $message = $breadcrumb->getMessage();
            if ($message !== null) {
                $breadcrumb = $breadcrumb->withMessage($text($message));
            }
            $metadata = $breadcrumb->getMetadata();
            foreach (array_keys($metadata) as $name) {
                $breadcrumb = $breadcrumb->withoutMetadata((string) $name);
            }
            foreach ($data($metadata) as $name => $value) {
                $breadcrumb = $breadcrumb->withMetadata((string) $name, $value);
            }
            $breadcrumbs[] = $breadcrumb;
        }
        $event->setBreadcrumb($breadcrumbs);

        $event->setRequest($data($event->getRequest()));
        $event->setExtra($data($event->getExtra()));
        $event->setTags(array_map($text, $event->getTags()));
        foreach ($event->getContexts() as $name => $context) {
            $event->setContext($name, $data($context));
        }

        return $event;
    }

    /**
     * @return list<string>
     */
    private static function secrets(): array
    {
        $key = config('services.share_links.key');

        return is_string($key) && $key !== '' ? [$key] : [];
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @param  list<string>  $secrets
     * @return array<array-key, mixed>
     */
    private static function scrubArray(array $value, array $secrets): array
    {
        $scrubbed = [];
        foreach ($value as $key => $item) {
            $key = is_string($key) ? str_ireplace($secrets, self::REDACTED, $key) : $key;
            $scrubbed[$key] = match (true) {
                is_string($item) => str_ireplace($secrets, self::REDACTED, $item),
                is_array($item) => self::scrubArray($item, $secrets),
                default => $item,
            };
        }

        return $scrubbed;
    }
}
