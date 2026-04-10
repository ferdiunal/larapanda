<?php

declare(strict_types=1);

namespace Ferdiunal\Larapanda\Runtime;

/**
 * Utility for masking sensitive command arguments before logging.
 */
final class CommandRedactor
{
    /** @var list<string> */
    private const SENSITIVE_FLAGS = [
        '--proxy-bearer-token',
    ];

    /**
     * @param  list<string>  $command
     * @return list<string>
     */
    public static function redact(array $command): array
    {
        $redacted = [];
        $maskNext = false;

        foreach ($command as $part) {
            if ($maskNext) {
                $redacted[] = '***';
                $maskNext = false;

                continue;
            }

            $redacted[] = $part;

            if (in_array($part, self::SENSITIVE_FLAGS, true)) {
                $maskNext = true;
            }
        }

        return $redacted;
    }
}
