<?php

declare(strict_types=1);

namespace Salon\Http;

use Salon\Model\Admin;
use Salon\Model\Booking;
use Salon\Model\BookingStatus;

/** Base for admin panel controllers: shared chrome, navigation and guards. */
abstract class AdminController extends Controller
{
    protected function render(string $template, string $title, array $data = [], string $ability = ''): string
    {
        $admin = $this->guard($ability);
        return View::render($template, $data + [
            'admin' => $admin,
            'theme' => Admin::theme($admin),
            'title' => $title,
            'nav' => $this->nav(),
            'flashes' => Flash::take(),
            'statuses' => BookingStatus::active(),
            'nonce' => SALON_NONCE,
            'current' => $template,
        ], 'layout_admin');
    }

    /** Refuses an ability the signed-in account does not hold, rendered with the panel chrome. */
    protected function guard(string $ability = ''): array
    {
        $admin = parent::guard($ability);
        if ($ability !== '' && !Auth::can($ability)) {
            http_response_code(403);
            exit($this->render('admin/denied', 'Access denied', [], ''));
        }
        return $admin;
    }

    /** @return array<int, array{label:string,path:string,key:string,badge?:int}> */
    private function nav(): array
    {
        $awaiting = 0;
        try {
            $awaiting = Booking::awaitingCount();
        } catch (\Throwable) {
            $awaiting = 0;
        }
        $items = [
            ['label' => 'Bookings', 'path' => 'admin/bookings', 'key' => 'admin/bookings', 'ability' => 'bookings', 'badge' => $awaiting],
            ['label' => 'Services', 'path' => 'admin/services', 'key' => 'admin/services', 'ability' => 'services'],
            ['label' => 'Statuses', 'path' => 'admin/statuses', 'key' => 'admin/statuses', 'ability' => 'statuses'],
            ['label' => 'Hours', 'path' => 'admin/schedule', 'key' => 'admin/schedule', 'ability' => 'schedule'],
            ['label' => 'Website', 'path' => 'admin/content', 'key' => 'admin/content', 'ability' => 'content'],
            ['label' => 'SMS', 'path' => 'admin/sms', 'key' => 'admin/sms', 'ability' => 'sms'],
            ['label' => 'Settings', 'path' => 'admin/settings', 'key' => 'admin/settings', 'ability' => 'settings'],
            ['label' => 'Team', 'path' => 'admin/team', 'key' => 'admin/team', 'ability' => 'admins'],
            ['label' => 'Activity', 'path' => 'admin/activity', 'key' => 'admin/activity', 'ability' => 'activity'],
            ['label' => 'Tenants', 'path' => 'admin/tenants', 'key' => 'admin/tenants', 'ability' => 'tenants'],
        ];
        $nav = [];
        foreach ($items as $item) {
            if (Admin::canManage($this->actor(), $item['ability'])) {
                unset($item['ability']);
                $nav[] = $item;
            }
        }
        return $nav;
    }

    private function actor(): ?array
    {
        return Auth::user();
    }
}
