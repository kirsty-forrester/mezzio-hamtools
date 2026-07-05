<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;

use function in_array;
use function preg_match;
use function sprintf;
use function strlen;
use function strtoupper;
use function substr;
use function trim;

/**
 * Parses a callsign and resolves its DXCC entity, country and
 * the licence class for UK callsigns
 */
final class PrefixLookup
{
    /**
     * Splits a callsign into: prefix letters | separating digit | suffix.
     * e.g. M7ABC -> ("M", "7", "ABC");  2E0XYZ -> ("2E", "0", "XYZ")
     */
    private const PATTERN = '/^([A-Z0-9]{1,3}?)(\d)([A-Z]{1,4})$/';

    /**
     * UK national prefixes always start G, M or 2. The letter after
     * that is the regional locator.
     *
     * @var array<string, string>
     */
    private const UK_LOCATORS = [
        'E' => 'England',
        'M' => 'Scotland',
        'W' => 'Wales',
        'I' => 'Northern Ireland',
        'D' => 'Isle of Man',
        'J' => 'Jersey',
        'U' => 'Guernsey',
    ];

    /**
     * Non-UK DXCC prefixes. Longest key wins; list two-letter prefixes first.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const PREFIXES = [
        'VK' => ['Australia', 'Australia'],
        'W'  => ['United States', 'USA'],
        'K'  => ['United States', 'USA'],
        'N'  => ['United States', 'USA'],
        'A'  => ['United States', 'USA'],
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

        $uk = $this->resolveUk($letters, $digit);

        if ($uk !== null) {
            [$entity, $licenceClass] = $uk;
            $country                 = 'United Kingdom';
        } else {
            [$entity, $country] = $this->resolveEntity($letters);
            $licenceClass       = null;
        }

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
     * Structural check only — is this shaped like a callsign at all? This is
     * the single source of truth for "valid callsign", reused by the validation
     * middleware so the gatekeeper and the parser can never drift apart.
     */
    public function isValid(string $call): bool
    {
        return preg_match(self::PATTERN, strtoupper(trim($call))) === 1;
    }

    /**
     * Recognise a UK call and resolve its home nation + licence class.
     *
     * @return array{0: string, 1: ?string}|null [entity, licenceClass], or null
     *                                            if this isn't a UK callsign.
     */
    private function resolveUk(string $letters, string $digit): ?array
    {
        $national = $letters[0];          // G, M or 2
        $locator  = substr($letters, 1);  // '', or one of E M W I D J U

        if (! in_array($national, ['G', 'M', '2'], true)) {
            return null;
        }

        $entity = $this->ukEntity($national, $locator);
        if ($entity === null) {
            return null; // e.g. "2A" or "MX" — not a real UK locator
        }

        return [$entity, $this->ukLicenceClass($national, $digit)];
    }

    /**
     * The 2-series always carries a locator letter (England is 2E, and the
     * locator is required). The G/M series omit the letter for England, though
     * since 2024 an optional 'E' locator may be used there too (uncommon).
     */
    private function ukEntity(string $national, string $locator): ?string
    {
        if ($national === '2') {
            return self::UK_LOCATORS[$locator] ?? null;
        }

        if ($locator === '') {
            return 'England';
        }

        return self::UK_LOCATORS[$locator] ?? null;
    }

    /**
     * Licence class is decided by the national letter + the separating digit;
     * the regional locator has no bearing on it.
     */
    private function ukLicenceClass(string $national, string $digit): ?string
    {
        return match ($national) {
            'G'     => in_array($digit, ['0', '1', '2', '3', '4', '5', '6', '7', '8'], true)
                ? 'Full'
                : null,
            '2'     => in_array($digit, ['0', '1'], true) ? 'Intermediate' : null,
            'M'     => match ($digit) {
                '3', '6', '7' => 'Foundation',
                '8', '9'      => 'Intermediate',
                '0', '1', '5' => 'Full',
                default       => null,
            },
            default => null,
        };
    }

    /**
     * Match the longest non-UK prefix we know about (try 2 chars, then 1).
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
}
