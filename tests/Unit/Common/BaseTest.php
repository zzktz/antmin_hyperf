<?php

declare(strict_types=1);

namespace AntminTest\Unit\Common;

use Antmin\Common\Base;
use Antmin\Support\HyperfContext;
use Hyperf\HttpServer\Contract\ResponseInterface as HyperfResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BaseTest extends TestCase
{
    public function testSucJsonBuildsSuccessEnvelope(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('request_start_time', $this->anything())->willReturn(microtime(true) - 0.01);

        $responseObject = $this->createMock(ResponseInterface::class);
        $response = $this->createMock(HyperfResponseInterface::class);
        $response->expects($this->once())
            ->method('json')
            ->with($this->callback(function (array $payload): bool {
                return $payload['status'] === 'success'
                    && $payload['code'] === 7
                    && $payload['message'] === 'done'
                    && $payload['data'] === ['x' => 1]
                    && is_string($payload['useTime'])
                    && str_ends_with($payload['useTime'], ' ms');
            }))
            ->willReturn($responseObject);

        $context = $this->createMock(HyperfContext::class);
        $context->method('request')->willReturn($request);
        $context->method('response')->willReturn($response);

        $base = new Base($context, $this->createMock(ValidatorFactoryInterface::class));

        $result = $base->sucJson('done', ['x' => 1], 7);

        $this->assertSame($responseObject, $result);
    }

    public function testFillUrlLeavesAbsoluteValuesUntouchedAndPrefixesRelativeValues(): void
    {
        $context = $this->createMock(HyperfContext::class);
        $context->expects($this->exactly(2))
            ->method('config')
            ->with('antmin.upload.url', '')
            ->willReturn('https://cdn.example.com/base');

        $base = new Base($context, $this->createMock(ValidatorFactoryInterface::class));

        $this->assertSame('https://example.com/a.png', $base->fillUrl('https://example.com/a.png'));
        $this->assertSame('https://cdn.example.com/base/images/a.png', $base->fillUrl('images/a.png'));
        $this->assertSame(
            ['https://cdn.example.com/base/a.png', 'https://static.example.com/b.png'],
            $base->fillUrl(['a.png', 'https://static.example.com/b.png'])
        );
    }
}
