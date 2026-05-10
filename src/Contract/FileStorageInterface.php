<?php

declare(strict_types=1);

namespace Antmin\Contract;

use Psr\Http\Message\UploadedFileInterface;

interface FileStorageInterface
{
    public function storeUploadedFile(string $directory, string $filename, UploadedFileInterface $file): string;

    public function url(string $path): string;

    public function fullUrl(string $path): string;
}
