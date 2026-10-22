<?php

declare(strict_types=1);

namespace Salon\Http;

use Salon\Model\Admin;

/** Shared plumbing for controllers: rendering, guards, JSON replies. */
abstract class Controller
{
    protected function view(string $template, array $data = [], ?string $layout = null): string
    {
        return View::render($template, $data, $layout);
    }

    protected function json(array $payload, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?: '{}';
    }

    /** Fail the request unless it is a POST with a valid session token. */
    protected function requirePost(Request $request): void
    {
        if (!$request->isPost()) {
            http_response_code(405);
            header('Allow: POST');
            exit($this->json(['ok' => false, 'msg' => 'Invalid request.'], 405));
        }
    }

    protected function requireCsrf(): void
    {
        if (!Csrf::check($_POST['csrf'] ?? null)) {
            Flash::push('Your session timed out. Please try again.', 'err');
            redirect('admin');
        }
    }

    /** Sign-in and forced-password checks. AdminController adds the ability check on top. */
    protected function guard(string $ability = ''): array
    {
        $admin = Auth::user();
        if ($admin === null) {
            redirect('admin/login');
        }
        if (Auth::mustChangePassword() && !in_array($ability, ['', 'theme', 'password'], true)) {
            Flash::push('Choose a new password before continuing.', 'err');
            redirect('admin/theme');
        }
        return $admin;
    }

    protected function actorId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    /** @return array<string,string> dropdown options for admin roles */
    protected function roles(): array
    {
        return Admin::ROLES;
    }
}
