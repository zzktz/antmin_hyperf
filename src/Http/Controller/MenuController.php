<?php

declare(strict_types=1);

namespace Antmin\Http\Controller;

use Psr\Http\Message\ResponseInterface;

class MenuController extends AbstractController
{
    public function __construct(
        private readonly \Antmin\Http\Service\MenuService $menuService,
        \Antmin\Common\Base $base,
        \Psr\Http\Message\ServerRequestInterface $request,
        \Hyperf\Validation\Contract\ValidatorFactoryInterface $validatorFactory,
    ) {
        parent::__construct($base, $request, $validatorFactory);
    }

    public function getMenuNav(): ResponseInterface
    {
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        return $this->success('成功', $this->menuService->getMenuNav($accountId));
    }

    public function menuList(): ResponseInterface
    {
        $input = $this->input();
        $parentId = (int) ($this->base->getValue($input, 'parentId', '', 'integer') ?? 0);
        return $this->success('成功', $this->menuService->menuList($parentId));
    }

    public function menuAdd(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $info = [
            'parentId' => (int) ($this->base->getValue($input, 'parentId', '', 'integer') ?? 0),
            'title' => (string) $this->base->getValue($input, 'title', '', 'required|max:100'),
            'icon' => (string) ($this->base->getValue($input, 'icon', '', 'max:100') ?? ''),
            'pageName' => (string) $this->base->getValue($input, 'pageName', '', 'required|max:100'),
            'routePath' => (string) $this->base->getValue($input, 'routePath', '', 'required|max:100'),
            'component' => (string) $this->base->getValue($input, 'component', '', 'required|max:100'),
            'redirect' => (string) ($this->base->getValue($input, 'redirect', '', 'max:200') ?? ''),
            'permissionIds' => (array) ($this->base->getValue($input, 'roles', '', 'array') ?? []),
        ];
        $this->menuService->menuAdd($info, $accountId);
        return $this->success('成功');
    }

    public function menuEdit(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $info = [
            'parentId' => (int) ($this->base->getValue($input, 'parentId', '', 'integer') ?? 0),
            'title' => (string) $this->base->getValue($input, 'title', '', 'required|max:100'),
            'icon' => (string) ($this->base->getValue($input, 'icon', '', 'max:100') ?? ''),
            'pageName' => (string) $this->base->getValue($input, 'pageName', '', 'required|max:100'),
            'routePath' => (string) $this->base->getValue($input, 'routePath', '', 'required|max:100'),
            'component' => (string) $this->base->getValue($input, 'component', '', 'required|max:100'),
            'redirect' => (string) ($this->base->getValue($input, 'redirect', '', 'max:200') ?? ''),
            'permissionIds' => (array) ($this->base->getValue($input, 'roles', '', 'array') ?? []),
        ];
        $this->menuService->menuEdit($info, $id, $accountId);
        return $this->success('成功');
    }

    public function menuDel(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $this->menuService->menuDel($id, $accountId);
        return $this->success('成功');
    }

    public function menuEditListorder(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $listorder = (int) $this->base->getValue($input, 'listorder', '', 'required|integer');
        $this->menuService->menuEditListorder($listorder, $id, $accountId);
        return $this->success('成功');
    }

    public function menuEditIsShow(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $this->menuService->menuEditIsShow($id, $accountId);
        return $this->success('成功');
    }

    public function menuEditIsHideChildren(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $this->menuService->menuEditIsHideChildren($id, $accountId);
        return $this->success('成功');
    }
}
