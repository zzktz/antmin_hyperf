<?php

declare(strict_types=1);

namespace Antmin\Http\Controller;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionMethod;

abstract class AbstractController
{
    public function __construct(
        protected readonly Base $base,
        protected readonly ServerRequestInterface $request,
        protected readonly ValidatorFactoryInterface $validatorFactory,
    ) {
    }

    protected function input(): array
    {
        return array_merge(
            $this->request->getQueryParams(),
            is_array($this->request->getParsedBody()) ? $this->request->getParsedBody() : []
        );
    }

    protected function validate(array $rules, array $messages = [], array $attributes = []): array
    {
        $input = $this->input();
        $normalized = [];
        foreach ($rules as $field => $rule) {
            $normalized[$field] = str_replace(
                ['letter', 'mobile', 'bool'],
                ['alpha', 'regex:/^1[3-9]\d{9}$/', 'boolean'],
                $rule,
            );
        }

        $validator = $this->validatorFactory->make($input, $normalized, $messages, $attributes);
        if ($validator->fails()) {
            throw new CommonException($validator->errors()->first());
        }

        return $input;
    }

    protected function resolveOperateAction(array $allowedActions): string
    {
        $action = (string) ($this->input()['action'] ?? '');
        if ($action === '') {
            throw new CommonException('action不能为空');
        }

        if (! in_array($action, $allowedActions, true)) {
            throw new CommonException('System Not Find Action');
        }

        if (! method_exists($this, $action)) {
            throw new CommonException('System Not Find Action');
        }

        $method = new ReflectionMethod($this, $action);
        if ($method->getDeclaringClass()->getName() !== static::class) {
            throw new CommonException('System Not Find Action');
        }

        return $action;
    }

    protected function success(string $message, array $data = [], int $code = 0): ResponseInterface
    {
        return $this->base->sucJson($message, $data, $code);
    }
}
