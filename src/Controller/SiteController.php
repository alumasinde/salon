<?php

declare(strict_types=1);

namespace Salon\Controller;

use Salon\Http\Controller;
use Salon\Http\Csrf;
use Salon\Http\Request;
use Salon\Model\Content;
use Salon\Model\Schedule;
use Salon\Model\Service;
use Salon\Model\Setting;
use Salon\Model\BookingStatus;
use Salon\Model\Booking;

final class SiteController extends Controller
{
    public function home(Request $request): string
    {
        $settings = Setting::all();
        $services = Service::grouped();
        $categories = [];
        foreach (Service::categories(true) as $category) {
            $categories[] = ['name' => $category['name'], 'blurb' => $category['blurb']];
        }
        $statusColors = [];
        foreach (BookingStatus::active() as $status) {
            $statusColors[$status['code']] = $status['color'];
        }
        return $this->view('site/home', [
            'settings' => $settings,
            'text' => array_column(Content::rows(), 'value', 'key'),
            'categories' => $categories,
            'services' => $services,
            'why' => Content::blocks('why'),
            'rules' => Content::blocks('rule'),
            'reviews' => Content::reviews(3),
            'hours' => Schedule::groupedWeek(),
            'open' => Schedule::openState(),
            'capacity' => max(1, Setting::int('capacity', 2)),
            'maxDate' => Schedule::maxDate(),
            'today' => date('Y-m-d'),
            'token' => Csrf::issue('booking'),
            'statuses' => $statusColors,
            'nonce' => SALON_NONCE,
        ], 'layout_site');
    }

    /** Live availability for one date, used by the booking form. */
    public function slots(Request $request): string
    {
        $date = $request->query('date', date('Y-m-d'));
        $valid = \DateTime::createFromFormat('!Y-m-d', $date);
        if ($valid === false || $valid->format('Y-m-d') !== $date) {
            return $this->json(['ok' => false, 'slots' => [], 'message' => 'Pick a valid date.'], 422);
        }
        if ($date < date('Y-m-d') || $date > Schedule::maxDate()) {
            return $this->json(['ok' => false, 'slots' => [], 'message' => 'That date is outside our booking window.'], 422);
        }
        $day = Schedule::forDate($date);
        $slots = Schedule::slots($date, max(1, Setting::int('capacity', 2)), Setting::int('lead_time_minutes', 60));
        return $this->json([
            'ok' => true,
            'closed' => $day['closed'],
            'label' => $day['label'],
            'capacity' => max(1, Setting::int('capacity', 2)),
            'slots' => array_map(static fn(array $s): array => [
                'time' => $s['time'],
                'left' => $s['left'],
                'full' => $s['left'] <= 0,
            ], $slots),
        ]);
    }

    public function health(Request $request): string
    {
        $tables = 0;
        try {
            $tables = count(\Salon\DB\Database::all('SHOW TABLES'));
        } catch (\Throwable) {
            return $this->json(['ok' => false, 'database' => 'unreachable',
                'hint' => 'php bin/migrate.php up --create-database'], 500);
        }
        return $this->json(['ok' => true, 'database' => 'connected', 'tables' => $tables,
            'time' => date('c')]);
    }
}
