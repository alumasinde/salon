<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\Http\Activity;
use Salon\Http\Auth;
use Salon\Http\Controller;
use Salon\Http\Csrf;
use Salon\Http\Flash;
use Salon\Http\Request;
use Salon\Model\Admin;

final class AuthController extends Controller
{
    public function loginPage(Request $request): string
    {
        if (Auth::check()) {
            redirect('admin');
        }
        return $this->loginView($request, '');
    }

    public function login(Request $request): string
    {
        $this->requirePost($request);
        if (!Csrf::check($_POST['csrf'] ?? null)) {
            return $this->loginView($request, 'That form expired. Try again.');
        }
        $username = (string) ($_POST['user'] ?? '');
        $password = (string) ($_POST['pass'] ?? '');
        if (Auth::attempt($username, $password, $request->ip)) {
            Flash::push('Welcome back, ' . (Auth::user()['first_name'] ?: Auth::user()['username']) . '.');
            redirect('admin');
        }
        if (Auth::throttled(mb_strtolower(trim($username)), $request->ip)) {
            Activity::log(null, 'login_throttled', $username, $request->ip);
            return $this->loginView($request, 'Too many failed attempts. Wait 15 minutes or use a different network.');
        }
        return $this->loginView($request, 'Wrong username or password.');
    }

    public function logout(Request $request): string
    {
        $this->requirePost($request);
        if (Csrf::check($_POST['csrf'] ?? null)) {
            Auth::logout();
        }
        redirect('admin/login');
    }

    private function loginView(Request $request, string $error): string
    {
        return $this->view('admin/login', [
            'error' => $error,
            'theme' => Admin::theme(null),
            'flashes' => Flash::take(),
            'nonce' => SALON_NONCE,
            'siteName' => \Salon\Model\Setting::get('salon_name', 'Salon'),
        ]);
    }
}
