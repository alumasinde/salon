<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\Http\Activity;
use Salon\Http\AdminController;
use Salon\Http\Auth;
use Salon\Http\Flash;
use Salon\Http\Request;
use Salon\Http\Validator;
use Salon\Model\Admin;
use Salon\Support\Color;

/** Personal appearance (colours) and password for the signed-in admin. */
final class ThemeController extends AdminController
{
    public const SWATCH_KEYS = ['primary', 'accent', 'bg', 'surface', 'text'];

    public function edit(Request $request): string
    {
        return $this->render('admin/theme', 'My account', [
            'presets' => Admin::PRESETS,
            'current' => Admin::theme($this->guard('theme')),
            'attempts' => 0,
        ], 'theme');
    }

    public function save(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('theme');
        $this->requireCsrf();
        $preset = (string) ($_POST['preset'] ?? 'house');
        $theme = ['preset' => $preset];
        if ($preset === 'custom') {
            $validator = Validator::make($_POST, array_fill_keys(
                ['primary', 'accent', 'bg', 'surface', 'text'], 'required|hex'));
            if ($validator->fails()) {
                Flash::push($validator->first(), 'err');
                redirect('admin/theme');
            }
            foreach (self::SWATCH_KEYS as $key) {
                $theme[$key] = Color::normalize((string) $_POST[$key]);
            }
        } else {
            $known = Admin::PRESETS[$preset] ?? Admin::PRESETS['house'];
            foreach (self::SWATCH_KEYS as $key) {
                $theme[$key] = $known[$key];
            }
            $theme['preset'] = Admin::presetKey($preset);
        }
        Admin::saveTheme((int) $admin['id'], $theme);
        Activity::log((int) $admin['id'], 'theme_save', $theme['preset'], $request->ip);
        Flash::push('Appearance saved. Your panel now uses those colours.');
        redirect('admin/theme');
    }

    public function password(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('theme');
        $this->requireCsrf();
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if (!password_verify($current, (string) $admin['pass_hash'])) {
            Flash::push('Your current password is not right.', 'err');
            redirect('admin/theme');
        }
        if (strlen($new) < 10) {
            Flash::push('New password needs at least 10 characters.', 'err');
            redirect('admin/theme');
        }
        if ($new !== $confirm) {
            Flash::push('The two new passwords do not match.', 'err');
            redirect('admin/theme');
        }
        Admin::setPassword((int) $admin['id'], $new);
        Activity::log((int) $admin['id'], 'password_change', (string) $admin['username'], $request->ip);
        Flash::push('Password changed.');
        redirect('admin/theme');
    }
}
