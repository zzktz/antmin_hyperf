<?php

declare(strict_types=1);

namespace Antmin\Common;

use Antmin\Exceptions\CommonException;
use Antmin\Support\HyperfContext;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class Base
{
    public function __construct(
        private readonly HyperfContext $context,
        private readonly ValidatorFactoryInterface $validatorFactory,
    ) {
    }

    public function errJson(string $msg, array $data = [], int $code = 0): ResponseInterface
    {
        return $this->context->response()->json([
            'useTime' => $this->getUseTime(),
            'status' => 'fail',
            'code' => $code,
            'message' => $msg,
            'data' => $data,
        ]);
    }

    public function sucJson(string $msg, array $data = [], int $code = 0): ResponseInterface
    {
        return $this->context->response()->json([
            'useTime' => $this->getUseTime(),
            'status' => 'success',
            'code' => $code,
            'message' => $msg,
            'data' => $data,
        ]);
    }

    public function getUseTime(): string
    {
        $request = $this->context->request();
        $start = (float) ($request->getAttribute('request_start_time', microtime(true)));
        return (string) intval((microtime(true) - $start) * 1000) . ' ms';
    }

    public function isMobile(string $mobile): bool
    {
        return preg_match('/^1([0-9]{10})$/', trim($mobile)) === 1;
    }

    public function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function listFormat(int $limit, mixed $query): array
    {
        $request = $this->context->request();
        $input = array_merge($request->getQueryParams(), is_array($request->getParsedBody()) ? $request->getParsedBody() : []);
        $page = max(1, (int) ($input['page'] ?? $input['pageNo'] ?? 1));
        $datas = $query->paginate($limit, ['*'], 'page', $page);

        return [
            'pageSize' => $limit,
            'pageNo' => $datas->currentPage(),
            'totalCount' => $datas->total(),
            'totalPage' => $datas->lastPage(),
            'data' => $datas->items(),
        ];
    }

    public function getValue(array $input, string $field, string $fieldName = '', string $validateRule = '', string $msg = ''): mixed
    {
        $fieldName = $fieldName !== '' ? $fieldName : $field;

        if ($validateRule !== '') {
            $validator = $this->validatorFactory->make($input, [$field => $this->normalizeRule($validateRule)], [], [$field => $fieldName]);
            if ($validator->fails()) {
                $message = $msg !== '' ? $msg : $validator->errors()->first();
                throw new CommonException($message);
            }
        }

        return $input[$field] ?? null;
    }

    public function fillUrl(array|string|null $value, ?string $url = null): array|string|null
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->fillUrl($item, $url), $value);
        }

        foreach (['http://', 'https://', 'ftp://', 'data:image/', '//'] as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return $value;
            }
        }

        $baseUrl = $url ?? (string) $this->context->config('antmin.upload.url', '');
        if ($baseUrl === '') {
            return $value;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($value, '/');
    }

    public function random(int $length, string $chars = '0123456789'): string
    {
        $max = strlen($chars) - 1;
        $hash = '';
        for ($i = 0; $i < $length; ++$i) {
            $hash .= $chars[random_int(0, $max)];
        }
        return $hash;
    }

    public function formatSizeUnits(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    private function normalizeRule(string $rule): string
    {
        return str_replace(
            ['letter', 'mobile', 'bool'],
            ['alpha', 'regex:/^1[3-9]\d{9}$/', 'boolean'],
            $rule,
        );
    }
}
