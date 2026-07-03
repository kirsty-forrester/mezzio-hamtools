<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Immutable result of parsing a callsign.
 *
 * `licenceClass` is a best-effort guess and is null when we can't infer one
 * (e.g. non-UK callsigns).
 */
final class Callsign
{
    public function __construct(
        public readonly string $callsign,
        public readonly string $prefix,
        public readonly string $entity,
        public readonly string $country,
        public readonly ?string $licenceClass,
        public readonly string $suffix,
    ) {
    }
}
