<?php

declare(strict_types=1);

namespace Antmin\Http\Controller;

use Antmin\Exceptions\CommonException;
use Antmin\Http\Service\AccountService;
use Antmin\Http\Service\PermissionsService;
use Psr\Http\Message\ResponseInterface;

class EnterController extends AbstractController
{
    private const ACTIONS = [
        'logout',
        'step2Code',
        'getUserInfo',
        'getMenuNav',
        'menuList',
        'menuAdd',
        'menuEdit',
        'menuDel',
        'menuEditListorder',
        'menuEditIsShow',
        'menuEditIsHideChildren',
        'personalInfoEdit',
        'accountList',
        'accountAdd',
        'accountEdit',
        'accountEditStatus',
        'accountDel',
        'reInitPassword',
        'roleList',
        'roleAdd',
        'roleEdit',
        'roleRuleEdit',
        'roleEditStatus',
        'roleDel',
        'permissionsList',
        'permissionsTree',
        'permissionsAdd',
        'permissionsEdit',
        'permissionsEditStatus',
        'permissionsDel',
    ];

    public function __construct(
        private readonly AccountService $accountService,
        private readonly PermissionsService $permissionsService,
        private readonly AccountController $accountController,
        private readonly MenuController $menuController,
        private readonly RoleController $roleController,
        private readonly PermissionsController $permissionsController,
        \Antmin\Common\Base $base,
        \Psr\Http\Message\ServerRequestInterface $request,
        \Hyperf\Validation\Contract\ValidatorFactoryInterface $validatorFactory,
    ) {
        parent::__construct($base, $request, $validatorFactory);
    }

    public function operate(): ResponseInterface
    {
        $action = $this->resolveOperateAction(self::ACTIONS);

        return $this->{$action}();
    }

    protected function logout(): ResponseInterface
    {
        return $this->success('成功');
    }

    protected function step2Code(): ResponseInterface
    {
        return $this->success('成功');
    }

    protected function getUserInfo(): ResponseInterface
    {
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $res = $this->accountService->getAccountBaseInfo($accountId);
        $permissions = $this->permissionsService->handleGetPermissionByAccountId($accountId);
        $res['role'] = $permissions;
        return $this->success('成功', $res);
    }

    protected function getMenuNav(): ResponseInterface
    {
        return $this->menuController->getMenuNav();
    }

    protected function menuList(): ResponseInterface
    {
        return $this->menuController->menuList();
    }

    protected function menuAdd(): ResponseInterface
    {
        return $this->menuController->menuAdd();
    }

    protected function menuEdit(): ResponseInterface
    {
        return $this->menuController->menuEdit();
    }

    protected function menuDel(): ResponseInterface
    {
        return $this->menuController->menuDel();
    }

    protected function menuEditListorder(): ResponseInterface
    {
        return $this->menuController->menuEditListorder();
    }

    protected function menuEditIsShow(): ResponseInterface
    {
        return $this->menuController->menuEditIsShow();
    }

    protected function menuEditIsHideChildren(): ResponseInterface
    {
        return $this->menuController->menuEditIsHideChildren();
    }

    protected function personalInfoEdit(): ResponseInterface
    {
        return $this->accountController->personalInfoEdit();
    }

    protected function accountList(): ResponseInterface
    {
        return $this->accountController->accountList();
    }

    protected function accountAdd(): ResponseInterface
    {
        return $this->accountController->accountAdd();
    }

    protected function accountEdit(): ResponseInterface
    {
        return $this->accountController->accountEdit();
    }

    protected function accountEditStatus(): ResponseInterface
    {
        return $this->accountController->accountEditStatus();
    }

    protected function accountDel(): ResponseInterface
    {
        return $this->accountController->accountDel();
    }

    protected function reInitPassword(): ResponseInterface
    {
        return $this->accountController->reInitPassword();
    }

    protected function roleList(): ResponseInterface
    {
        return $this->roleController->roleList();
    }

    protected function roleAdd(): ResponseInterface
    {
        return $this->roleController->roleAdd();
    }

    protected function roleEdit(): ResponseInterface
    {
        return $this->roleController->roleEdit();
    }

    protected function roleRuleEdit(): ResponseInterface
    {
        return $this->roleController->roleRuleEdit();
    }

    protected function roleEditStatus(): ResponseInterface
    {
        return $this->roleController->roleEditStatus();
    }

    protected function roleDel(): ResponseInterface
    {
        return $this->roleController->roleDel();
    }

    protected function permissionsList(): ResponseInterface
    {
        return $this->permissionsController->permissionsList();
    }

    protected function permissionsTree(): ResponseInterface
    {
        return $this->permissionsController->permissionsTree();
    }

    protected function permissionsAdd(): ResponseInterface
    {
        return $this->permissionsController->permissionsAdd();
    }

    protected function permissionsEdit(): ResponseInterface
    {
        return $this->permissionsController->permissionsEdit();
    }

    protected function permissionsEditStatus(): ResponseInterface
    {
        return $this->permissionsController->permissionsEditStatus();
    }

    protected function permissionsDel(): ResponseInterface
    {
        return $this->permissionsController->permissionsDel();
    }
}
