<?php

declare(strict_types=1);

namespace Antmin\Support;

use Antmin\Contract\FileStorageInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Psr\Http\Message\UploadedFileInterface;

class FileStorage implements FileStorageInterface
{
    public function __construct(private readonly HyperfContext $context)
    {
    }

    public function storeUploadedFile(string $directory, string $filename, UploadedFileInterface $file): string
    {
        $filesystem = $this->filesystem();
        $path = $this->buildPath($directory, $filename);
        $stream = $file->getStream();
        $resource = $stream->detach();
        if (! is_resource($resource)) {
            throw new \RuntimeException('上传文件流不可用');
        }

        try {
            $filesystem->writeStream($path, $resource);
        } finally {
            fclose($resource);
        }

        return $path;
    }

    public function url(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    public function fullUrl(string $path): string
    {
        $relativePath = $this->url($path);
        $baseUrl = (string) $this->context->config('antmin.upload.url', '');
        if ($baseUrl === '') {
            return $relativePath;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($relativePath, '/');
    }

    private function filesystem(): Filesystem
    {
        return new Filesystem(new LocalFilesystemAdapter(BASE_PATH . '/runtime/antmin'));
    }

    private function buildPath(string $directory, string $filename): string
    {
        return trim($directory, '/') . '/' . ltrim($filename, '/');
    }
}
