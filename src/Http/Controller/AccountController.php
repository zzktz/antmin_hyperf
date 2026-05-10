<?php

declare(strict_types=1);

namespace Antmin\Http\Controller;

use Antmin\Exceptions\CommonException;
use Antmin\Http\Service\AccountService;
use Antmin\Http\Service\LoginService;
use Psr\Http\Message\ResponseInterface;

class AccountController extends AbstractController
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly LoginService $loginService,
        \Antmin\Common\Base $base,
        \Psr\Http\Message\ServerRequestInterface $request,
        \Hyperf\Validation\Contract\ValidatorFactoryInterface $validatorFactory,
    ) {
        parent::__construct($base, $request, $validatorFactory);
    }

    public function login(): ResponseInterface
    {
        try {
            $input = $this->input();
            $username = (string) ($input['username'] ?? '');
            if ($username === '') {
                throw new CommonException('登录账号/手机号/邮件地址不能空');
            }

            if ($this->base->isMobile($username)) {
                $this->validate([
                    'captcha' => 'required|max:6',
                ]);
                $token = $this->loginService->mobileLogin($username, (string) ($input['captcha'] ?? ''));
            } else {
                $token = $this->loginService->accountLogin($username, (string) ($input['password'] ?? ''));
            }

            return $this->success('成功', ['token' => $token]);
        } catch (CommonException $exception) {
            throw new CommonException('登录失败: ' . $exception->getMessage());
        }
    }

    public function register(): ResponseInterface
    {
        $input = $this->input();
        $email = (string) $this->base->getValue($input, 'email', '', 'email');
        $captcha = (string) $this->base->getValue($input, 'captcha', '', 'required|min:6');
        $password = (string) $this->base->getValue($input, 'password', '', 'required|min:8');
        $token = $this->loginService->register($email, $captcha, $password);
        return $this->success('成功', ['token' => $token]);
    }

    public function sendCodeByEmail(): ResponseInterface
    {
        $input = $this->input();
        $email = (string) $this->base->getValue($input, 'email', '', 'required|email');
        $type = (string) ($this->base->getValue($input, 'type', '', 'max:20') ?? '');
        $this->loginService->sendCodeByEmail($email, $type);
        return $this->success('邮件验证码已发送');
    }

    public function systemResetPassword(): ResponseInterface
    {
        $input = $this->input();
        $email = (string) $this->base->getValue($input, 'email', '', 'email');
        $password = (string) $this->base->getValue($input, 'password', '', 'required');
        $captcha = (string) $this->base->getValue($input, 'captcha', '', 'required|min:6');
        $this->loginService->systemResetPassword($email, $password, $captcha);
        return $this->success('密码修改成功！');
    }

    public function personalInfoEdit(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $email = (string) ($this->base->getValue($input, 'email', '', 'email') ?? '');
        $mobile = (string) ($this->base->getValue($input, 'mobile', '', 'mobile') ?? '');
        $nickname = (string) ($this->base->getValue($input, 'nickname', '', 'max:20') ?? '');

        if ($mobile !== '') {
            $field = 'mobile';
            $value = $mobile;
        } elseif ($nickname !== '') {
            $field = 'nickname';
            $value = $nickname;
        } elseif ($email !== '') {
            $field = 'email';
            $value = $email;
        } else {
            $field = '';
            $value = '';
        }

        $this->accountService->personalEdit($field, $value, $accountId);
        return $this->success('成功');
    }

    public function accountList(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $limit = (int) ($this->base->getValue($input, 'pageSize', '', 'integer') ?? 10);
        return $this->success('成功', $this->accountService->accountList($limit, $accountId));
    }

    public function accountAdd(): ResponseInterface
    {
        $input = $this->validate([
            'username' => 'required|max:30',
            'mobile' => 'required|mobile',
            'roles' => 'required|array',
            'email' => 'nullable|email',
            'password' => 'nullable|min:8',
        ]);
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $payload = [
            'nickname' => (string) $input['username'],
            'email' => (string) ($input['email'] ?? ($input['mobile'] . '@163.com')),
            'mobile' => (string) $input['mobile'],
            'password' => (string) ($input['password'] ?? ''),
            'roles' => (array) $input['roles'],
        ];
        $userId = $this->accountService->accountAdd($payload, $accountId);
        return $this->success('账号添加成功', ['id' => $userId]);
    }

    public function accountEdit(): ResponseInterface
    {
        $input = $this->validate([
            'id' => 'required|integer',
            'username' => 'required|max:50',
            'email' => 'required|email',
            'mobile' => 'required|regex:/^1[3-9]\d{9}$/',
            'roles' => 'required|array',
        ]);
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $payload = [
            'nickname' => (string) $input['username'],
            'email' => (string) $input['email'],
            'mobile' => (string) $input['mobile'],
            'roles' => (array) $input['roles'],
        ];
        $this->accountService->accountEdit($payload, (int) $input['id'], $accountId);
        return $this->success('账号编辑成功');
    }

    public function accountEditStatus(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $this->accountService->editStatus($id, $accountId);
        return $this->success('状态更新成功');
    }

    public function accountDel(): ResponseInterface
    {
        $input = $this->input();
        $accountId = (int) ($this->request->getAttribute('accountId') ?? 0);
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $this->accountService->accountDel($id, $accountId);
        return $this->success('删除成功');
    }

    public function reInitPassword(): ResponseInterface
    {
        $input = $this->input();
        $id = (int) $this->base->getValue($input, 'id', '', 'required|integer');
        $this->accountService->reInitPassword($id);
        return $this->success('密码重置成功');
    }
}
