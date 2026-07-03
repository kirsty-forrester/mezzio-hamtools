<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Service\Callsign;
use App\Service\PrefixLookup;
use PHPUnit\Framework\TestCase;

final class PrefixLookupTest extends TestCase
{
    public function testResolvesUkFoundationCall(): void
    {
        $lookup = new PrefixLookup();

        $result = $lookup->lookup('M7ABC');

        self::assertInstanceOf(Callsign::class, $result);
        self::assertSame('M7', $result->prefix);
        self::assertSame('United Kingdom', $result->country);
        self::assertSame('Foundation', $result->licenceClass);
        self::assertSame('ABC', $result->suffix);
    }

    public function testResolvesScottishFoundationCall(): void
    {
        $result = (new PrefixLookup())->lookup('MM7ABC');
        self::assertSame('Scotland', $result->entity);
        self::assertSame('Foundation', $result->licenceClass);
    }
}
