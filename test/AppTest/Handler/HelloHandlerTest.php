<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HelloHandler;
use Laminas\Diactoros\Response\TextResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class HelloHandlerTest extends TestCase
{
    public function testResponse(): void
    {
        $helloHandler = new HelloHandler();
        $response = $helloHandler->handle(
            $this->createMock(ServerRequestInterface::class)
        );

        self::assertInstanceOf(TextResponse::class, $response);
        self::assertSame('Hello, world!', (string) $response->getBody());
    }
}