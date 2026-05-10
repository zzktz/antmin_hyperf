<?php

declare(strict_types=1);

namespace Antmin\Route;

use Antmin\Http\Middleware\Middleware;
use Hyperf\HttpServer\Router\Router;

class RouteRegistrar
{
    public static function register(string $prefix = 'api/adminconsole'): void
    {
        Router::addGroup('/' . trim($prefix, '/'), function () {
            Router::addRoute(['GET', 'POST', 'HEAD'], '/systemLogin', 'Antmin\\Http\\Controller\\AccountController@login');
            Router::addRoute(['GET', 'POST', 'HEAD'], '/systemRegister', 'Antmin\\Http\\Controller\\AccountController@register');
            Router::addRoute(['GET', 'POST', 'HEAD'], '/sendCodeByEmail', 'Antmin\\Http\\Controller\\AccountController@sendCodeByEmail');
            Router::addRoute(['GET', 'POST', 'HEAD'], '/systemResetPassword', 'Antmin\\Http\\Controller\\AccountController@systemResetPassword');
            Router::addRoute(['GET', 'POST', 'HEAD'], '/systemUploadEditor', 'Antmin\\Http\\Controller\\UploadController@editorUpload');
            Router::addRoute(['GET', 'POST', 'HEAD'], '/systemIndexOperate', 'Antmin\\Http\\Controller\\EnterController@operate');
            Router::addRoute(['GET', 'POST', 'HEAD'], '/systemUploadOperate', 'Antmin\\Http\\Controller\\UploadController@operate');
        }, [
            'middleware' => [
                Middleware::class,
            ],
        ]);
    }
}
