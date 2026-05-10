<?php

declare(strict_types=1);

namespace Antmin\Http\Controller;

use Psr\Http\Message\ResponseInterface;

class PermissionsController extends AbstractController
{
    public function __construct(
        private readonly \Antmin\Http\Service\PermissionsService $permissionsService,
        \Antmin\Common\Base $base,
        \Psr\Http\Message\ServerRequestInterface $request,
        \Hyperf\Validation\Contract\ValidatorFactoryInterface $validatorFactory,
    ) {
        parent::__construct($base, $request, $validatorFactory);
    }

    public function permissionsList(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $limit = (int) ($this->base->getValue($input, 'pageSize', '', 'integer') ?? 10);
        return $this->success('成功', $this->permissionsService->ruleList($limit, $accountId));
    }

    public function permissionsTree(): ResponseInterface
    {
        return $this->success('成功', $this->permissionsService->ruleListTree());
    }

    public function permissionsAdd(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $add = [
            'vid' => (string) $this->base->getValue($input, 'vid', '', 'required|letter|max:30'),
            'title' => (string) $this->base->getValue($input, 'title', '', 'required|max:50'),
            'pid' => (int) $this->base->getValue($input, 'pid', '', 'required|integer'),
            'status' => 1,
        ];
        $this->permissionsService->ruleAdd($add, $accountId);
        return $this->success('成功');
    }

    public function permissionsEdit(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $up = [
            'vid' => (string) $this->base->getValue($input, 'vid', '', 'required|letter|max:30'),
            'title' => (string) $this->base->getValue($input, 'title', '', 'required|max:50'),
            'pid' => (int) ($this->base->getValue($input, 'pid', '', 'integer') ?? 0),
        ];
        $this->permissionsService->ruleEdit($up, $id, $accountId);
        return $this->success('成功');
    }

    public function permissionsEditStatus(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $this->permissionsService->ruleEditStatus($id, $accountId);
        return $this->success('成功');
    }

    public function permissionsDel(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $this->permissionsService->ruleDel($id, $accountId);
        return $this->success('成功');
    }
}
