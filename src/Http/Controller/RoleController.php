<?php

declare(strict_types=1);

namespace Antmin\Http\Controller;

use Psr\Http\Message\ResponseInterface;

class RoleController extends AbstractController
{
    public function __construct(
        private readonly \Antmin\Http\Service\RoleService $roleService,
        \Antmin\Common\Base $base,
        \Psr\Http\Message\ServerRequestInterface $request,
        \Hyperf\Validation\Contract\ValidatorFactoryInterface $validatorFactory,
    ) {
        parent::__construct($base, $request, $validatorFactory);
    }

    public function roleList(): ResponseInterface
    {
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        return $this->success('成功', $this->roleService->index(99, $accountId));
    }

    public function roleAdd(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $vid = (string) $this->base->getValue($input, 'vid', '', 'required|letter|max:50');
        $name = (string) $this->base->getValue($input, 'name', '', 'required|max:50');
        $this->roleService->add($vid, $name, $accountId);
        return $this->success('添加成功');
    }

    public function roleEdit(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $name = (string) $this->base->getValue($input, 'name', '', 'required|max:50');
        $this->roleService->edit(['name' => $name], $id, $accountId);
        return $this->success('编辑成功');
    }

    public function roleRuleEdit(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $rules = (array) ($this->base->getValue($input, 'rules', '', 'array') ?? []);
        $this->roleService->roleRuleEdit($rules, $id, $accountId);
        return $this->success('编辑成功');
    }

    public function roleEditStatus(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $this->roleService->editStatus($id, $accountId);
        return $this->success('状态更新成功');
    }

    public function roleDel(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $this->roleService->del($id, $accountId);
        return $this->success('删除成功');
    }
}
