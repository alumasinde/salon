<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\Http\AdminController;
use Salon\Http\Request;
use Salon\Model\Booking;
use Salon\Model\Schedule;
use Salon\Model\Service;
use Salon\Model\Setting;
use Salon\Sms\Gateway;
use Salon\Sms\SmsService;

final class DashboardController extends AdminController
{
    public function index(Request $request): string
    {
        $today = date('Y-m-d');
        return $this->render('admin/dashboard', 'Dashboard', [
            'stats' => Booking::stats(),
            'todayList' => Booking::search(['view' => 'today', 'per_page' => 10])['rows'],
            'nextDays' => Booking::nextDays(6),
            'recent' => Booking::recent(6),
            'open' => Schedule::openState(),
            'hours' => Schedule::groupedWeek(),
            'smsToday' => SmsService::todayStats(),
            'smsReady' => Gateway::ready(),
            'smsMissing' => Gateway::missing(),
            'recipients' => SmsService::recipients(),
            'topServices' => Booking::stats()['top_services'],
            'capacity' => max(1, Setting::int('capacity', 2)),
            'serviceCount' => count(Service::active()),
        ]);
    }
}
