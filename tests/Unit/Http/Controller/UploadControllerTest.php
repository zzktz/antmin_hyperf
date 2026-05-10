<?php

declare(strict_types=1);

namespace AntminTest\Unit\Http\Controller;

use Antmin\Common\Base;
use Antmin\Contract\FileStorageInterface;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Controller\UploadController;
use Antmin\Http\Repository\AccountRepository;
use Antmin\Support\HyperfContext;
use Hyperf\HttpServer\Contract\ResponseInterface as HyperfResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadControllerTest extends TestCase
{
    public function testOperateRejectsUnknownAction(): void
    {
        $controller = new UploadController(
            $this->createMock(AccountRepository::class),
            $this->createMock(HyperfContext::class),
            $this->createMock(FileStorageInterface::class),
            $this->createMock(Base::class),
            $this->createRequest(['action' => 'missingAction']),
            $this->createMock(ValidatorFactoryInterface::class),
        );

        $this->expectException(CommonException::class);
        $this->expectExceptionMessage('System Not Find Action');

        $controller->operate();
    }

    public function testEditorUploadConfigReturnsJsonResponse(): void
    {
        $response = $this->createMock(HyperfResponseInterface::class);
        $response->expects($this->once())
            ->method('json')
            ->with($this->callback(function (array $payload): bool {
                return ($payload['imageActionName'] ?? null) === 'uploadimage'
                    && ($payload['fileActionName'] ?? null) === 'uploadfile'
                    && ($payload['videoActionName'] ?? null) === 'uploadvideo'
                    && ($payload['imageManagerListSize'] ?? null) === 20;
            }))
            ->willReturn($this->createMock(ResponseInterface::class));

        $context = $this->createMock(HyperfContext::class);
        $context->expects($this->once())
            ->method('response')
            ->willReturn($response);

        $request = $this->createRequest(['action' => 'config']);
        $request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $controller = new UploadController(
            $this->createMock(AccountRepository::class),
            $context,
            $this->createMock(FileStorageInterface::class),
            $this->createMock(Base::class),
            $request,
            $this->createMock(ValidatorFactoryInterface::class),
        );

        $result = $controller->editorUpload();

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testEditorUploadUsesFileStorageFullUrl(): void
    {
        $responseObject = $this->createMock(ResponseInterface::class);
        $response = $this->createMock(HyperfResponseInterface::class);
        $response->expects($this->once())
            ->method('json')
            ->with($this->callback(function (array $payload): bool {
                return $payload['state'] === 'SUCCESS'
                    && $payload['url'] === 'https://cdn.example.com/upload/file/20260510/file.png'
                    && is_string($payload['title'])
                    && str_ends_with($payload['title'], '.png')
                    && $payload['original'] === 'original.png'
                    && $payload['type'] === 'png'
                    && $payload['size'] === '128 B';
            }))
            ->willReturn($responseObject);

        $context = $this->createMock(HyperfContext::class);
        $context->expects($this->once())
            ->method('response')
            ->willReturn($response);

        $fileStorage = $this->createMock(FileStorageInterface::class);
        $fileStorage->expects($this->once())
            ->method('storeUploadedFile')
            ->with($this->stringStartsWith('upload/file/'), $this->stringEndsWith('.png'), $this->isInstanceOf(UploadedFileInterface::class))
            ->willReturn('upload/file/20260510/file.png');
        $fileStorage->expects($this->once())
            ->method('fullUrl')
            ->with('upload/file/20260510/file.png')
            ->willReturn('https://cdn.example.com/upload/file/20260510/file.png');

        $base = $this->createMock(Base::class);
        $base->expects($this->once())
            ->method('formatSizeUnits')
            ->with(128)
            ->willReturn('128 B');

        $file = $this->createMock(UploadedFileInterface::class);
        $file->method('getError')->willReturn(UPLOAD_ERR_OK);
        $file->method('getSize')->willReturn(128);
        $file->method('getClientFilename')->willReturn('original.png');
        $file->method('getStream')->willReturn($this->createMock(StreamInterface::class));

        $request = $this->createRequest(['action' => 'uploadimage']);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getUploadedFiles')->willReturn(['upfile' => $file]);

        $controller = new UploadController(
            $this->createMock(AccountRepository::class),
            $context,
            $fileStorage,
            $base,
            $request,
            $this->createMock(ValidatorFactoryInterface::class),
        );

        $result = $controller->editorUpload();

        $this->assertSame($responseObject, $result);
    }

    private function createRequest(array $queryParams = [], ?array $parsedBody = null): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);
        $request->method('getParsedBody')->willReturn($parsedBody);

        return $request;
    }
}
