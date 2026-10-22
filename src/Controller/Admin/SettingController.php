<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\DB\Database;
use Salon\Http\Activity;
use Salon\Http\AdminController;
use Salon\Http\Flash;
use Salon\Http\Request;
use Salon\Http\Validator;
use Salon\Model\Setting;

/** Salon identity and booking rules. SMS keys live on the SMS page. */
final class SettingController extends AdminController
{
    public const RULES = [
        'salon_name' => 'required|max:120',
        'whatsapp' => 'phone',
        'phone' => 'max:24',
        'email' => 'email|max:160',
        'address' => 'max:255',
        'map_query' => 'max:160',
        'social_instagram' => 'max:60',
        'social_tiktok' => 'max:60',
        'meta_description' => 'max:255',
        'capacity' => 'int',
        'slot_step' => 'int',
        'max_days_ahead' => 'int',
        'lead_time_minutes' => 'int',
        'deposit_note' => 'max:255',
        'booking_success_text' => 'max:255',
    ];

    public function index(Request $request): string
    {
        $rows = array_values(array_filter(Setting::rows(),
            static fn(array $r): bool => !str_starts_with($r['key'], 'sms_')));
        return $this->render('admin/settings', 'Settings', [
            'rows' => $rows,
            'counts' => [
                'bookings' => (int) (Database::value('SELECT COUNT(*) FROM bookings') ?? 0),
                'services' => (int) (Database::value('SELECT COUNT(*) FROM services') ?? 0),
                'admins' => (int) (Database::value('SELECT COUNT(*) FROM admins') ?? 0),
            ],
            'migrationCount' => (int) (Database::value('SELECT COUNT(*) FROM schema_migrations') ?? 0),
        ], 'settings');
    }

    public function save(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('settings');
        $this->requireCsrf();
        $validator = Validator::make($_POST, self::RULES);
        if ($validator->fails()) {
            Flash::push($validator->first(), 'err');
            redirect('admin/settings');
        }
        $stored = 0;
        foreach (array_keys(self::RULES) as $key) {
            if (!array_key_exists($key, $_POST)) {
                continue;
            }
            $value = mb_substr(trim((string) $_POST[$key]), 0, 255);
            if ($key === 'capacity' || $key === 'slot_step') {
                $value = (string) max($key === 'capacity' ? 1 : 5, min(200, (int) $value));
            }
            if ($key === 'max_days_ahead') {
                $value = (string) max(1, min(365, (int) $value));
            }
            if ($key === 'lead_time_minutes') {
                $value = (string) max(0, min(1440, (int) $value));
            }
            Setting::set($key, $value);
            $stored++;
        }
        Activity::log((int) $admin['id'], 'settings_update', (string) $stored, $request->ip);
        Flash::push('Settings saved.');
        redirect('admin/settings');
    }
}
