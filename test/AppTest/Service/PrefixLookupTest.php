<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Service\Callsign;
use App\Service\PrefixLookup;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PrefixLookupTest extends TestCase
{
    public function testReturnsAFullyPopulatedCallsign(): void
    {
        $result = (new PrefixLookup())->lookup('M7ABC');

        self::assertInstanceOf(Callsign::class, $result);
        self::assertSame('M7', $result->prefix);
        self::assertSame('England', $result->entity);
        self::assertSame('United Kingdom', $result->country);
        self::assertSame('Foundation', $result->licenceClass);
        self::assertSame('ABC', $result->suffix);
    }

    public function testIsValidAcceptsWellFormedCallsAndRejectsJunk(): void
    {
        $lookup = new PrefixLookup();

        self::assertTrue($lookup->isValid('M7ABC'));
        self::assertTrue($lookup->isValid('  vk2def  ')); // trimmed + upper-cased
        self::assertFalse($lookup->isValid('not a call'));
        self::assertFalse($lookup->isValid(''));
    }

    #[DataProvider('ukCallsignProvider')]
    public function testResolvesUkEntityAndLicenceClass(
        string $call,
        string $entity,
        string $licenceClass
    ): void {
        $result = (new PrefixLookup())->lookup($call);

        self::assertSame($entity, $result->entity);
        self::assertSame('United Kingdom', $result->country);
        self::assertSame($licenceClass, $result->licenceClass);
    }

    /** @return array<string, array{0: string, 1: string, 2: string}> */
    public static function ukCallsignProvider(): array
    {
        return [
            // Foundation: M3 / M6 / M7
            'M7 England Foundation'    => ['M7ABC', 'England', 'Foundation'],
            'M3 England Foundation'    => ['M3ABC', 'England', 'Foundation'],
            'MW7 Wales Foundation'     => ['MW7ABC', 'Wales', 'Foundation'],
            'MM6 Scotland Foundation'  => ['MM6ABC', 'Scotland', 'Foundation'],
            // Optional England 'E' locator, introduced 2024 (uncommon)
            'ME7 England Foundation'   => ['ME7ABC', 'England', 'Foundation'],
            'GE4 England Full'         => ['GE4ABC', 'England', 'Full'],
            // Intermediate: M8 / M9 / 2x0 / 2x1
            'M8 England Intermediate'   => ['M8ABC', 'England', 'Intermediate'],
            'M9 England Intermediate'   => ['M9ABC', 'England', 'Intermediate'],
            '2E0 England Intermediate'  => ['2E0ABC', 'England', 'Intermediate'],
            '2I0 N.Ireland Intermediate' => ['2I0ABC', 'Northern Ireland', 'Intermediate'],
            '2M1 Scotland Intermediate' => ['2M1ABC', 'Scotland', 'Intermediate'],
            // Full: M0 / M1 / M5 / G0-G8
            'M0 England Full'    => ['M0ABC', 'England', 'Full'],
            'M5 England Full'    => ['M5ABC', 'England', 'Full'],
            'MM0 Scotland Full'  => ['MM0ABC', 'Scotland', 'Full'],
            'G4 England Full'    => ['G4ABC', 'England', 'Full'],
            'GW3 Wales Full'     => ['GW3ABC', 'Wales', 'Full'],
            'GD2 Isle of Man Full' => ['GD2ABC', 'Isle of Man', 'Full'],
            'GJ0 Jersey Full'    => ['GJ0ABC', 'Jersey', 'Full'],
            'GU1 Guernsey Full'  => ['GU1ABC', 'Guernsey', 'Full'],
        ];
    }
}
