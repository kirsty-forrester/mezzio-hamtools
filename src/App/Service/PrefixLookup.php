<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;

use function preg_match;
use function sprintf;
use function strlen;
use function strtoupper;
use function substr;
use function trim;

/**
 * Parses a callsign and resolves its DXCC entity, country and
 * (for UK calls) a guessed licence class.
 */
final class PrefixLookup
{
    /**
     * Splits a callsign into: prefix letters | separating digit | suffix.
     * e.g. M7ABC -> ("M", "7", "ABC");  2E0XYZ -> ("2E", "0", "XYZ")
     */
    private const PATTERN = '/^([A-Z0-9]{1,3}?)(\d)([A-Z]{1,4})$/';

    /**
     * Alpha prefix => [DXCC entity, country]. Longest key wins, so list the
     * two-letter regional prefixes (2E, GM, GW...) before the single letters.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const PREFIXES = [
        '2E' => ['England', 'United Kingdom'],
        'MM' => ['Scotland', 'United Kingdom'],
        'M'  => ['England', 'United Kingdom'],
        'G'  => ['England', 'United Kingdom'],
        // Next: add Guernsey etc and USA (W, K, N, A) and Australia (VK).
    ];

    public function lookup(string $call): Callsign
    {
        $call = strtoupper(trim($call));

        if (preg_match(self::PATTERN, $call, $matches) !== 1) {
            throw new InvalidArgumentException(
                sprintf('"%s" is not a valid callsign', $call)
            );
        }

        [, $letters, $digit, $suffix] = $matches;
        $prefix = $letters . $digit;

        [$entity, $country] = $this->resolveEntity($letters);

        $licenceClass = $country === 'United Kingdom'
            ? $this->guessUkLicenceClass($letters, $digit)
            : null;

        return new Callsign(
            callsign: $call,
            prefix: $prefix,
            entity: $entity,
            country: $country,
            licenceClass: $licenceClass,
            suffix: $suffix,
        );
    }

    /**
     * Match the longest alpha prefix we know about (try 2 chars, then 1).
     *
     * @return array{0: string, 1: string} [entity, country]
     */
    private function resolveEntity(string $letters): array
    {
        for ($length = strlen($letters); $length >= 1; $length--) {
            $candidate = substr($letters, 0, $length);
            if (isset(self::PREFIXES[$candidate])) {
                return self::PREFIXES[$candidate];
            }
        }

        return ['Unknown', 'Unknown'];
    }

    /**
     * UK licence class guessed from the prefix. Cross-check against Ofcom/QRZ.
     * 
     * TODO: Finish this. This is just an initial attempt and need to check
     * the prefixes more closely. I am missing the newer intermediate prefixes
     * for sure
     */
    private function guessUkLicenceClass(string $letters, string $digit): ?string
    {
        // 2x = Intermediate; any G-call = Full; M-series decided by the digit.
        return match (true) {
            str_starts_with($letters, '2')          => 'Intermediate',
            str_starts_with($letters, 'G')          => 'Full',
            ! str_starts_with($letters, 'M')        => null,
            in_array($digit, ['0', '1', '5'], true) => 'Full',
            in_array($digit, ['3', '6', '7'], true) => 'Foundation',
            default                                 => null,
        };
    }
}
