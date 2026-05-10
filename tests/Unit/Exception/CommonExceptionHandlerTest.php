<?php

declare(strict_types=1);

namespace AntminTest\Unit\Exception;

use Antmin\Common\Base;
use Antmin\Exception\Handler\CommonExceptionHandler;
use Antmin\Exceptions\CommonException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swow\Psr7\Message\ResponsePlusInterface;

class CommonExceptionHandlerTest extends TestCase
{
    public function testHandleMapsCommonExceptionToErrorResponse(): void
    {
        $exception = new CommonException('bad request', ['field' => 'email'], 123, 422);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('withStatus')
            ->with(422)
            ->willReturnSelf();

        $base = $this->createMock(Base::class);
        $base->expects($this->once())
            ->method('errJson')
            ->with('bad request', ['field' => 'email'], 123)
            ->willReturn($response);

        $handler = new CommonExceptionHandler($base);
        $fallbackResponse = $this->createMock(ResponsePlusInterface::class);

        $handled = $handler->handle($exception, $fallbackResponse);

        $this->assertSame($response, $handled);
        $this->assertTrue($handler->isValid($exception));
    }

    public function testIsValidRejectsOtherExceptions(): void
    {
        $handler = new CommonExceptionHandler($this->createMock(Base::class));

        $this->assertFalse($handler->isValid(new \RuntimeException('other')));
    }
}
