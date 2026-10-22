<?php

declare(strict_types=1);

use Salon\Controller\Admin\ActivityController;
use Salon\Controller\Admin\AuthController;
use Salon\Controller\Admin\BookingAdminController;
use Salon\Controller\Admin\CatalogController;
use Salon\Controller\Admin\ContentController;
use Salon\Controller\Admin\DashboardController;
use Salon\Controller\Admin\ScheduleController;
use Salon\Controller\Admin\SettingController;
use Salon\Controller\Admin\SmsController;
use Salon\Controller\Admin\StatusController;
use Salon\Controller\Admin\TeamController;
use Salon\Controller\Admin\ThemeController;
use Salon\Controller\Admin\TenantController;
use Salon\Controller\BookingController;
use Salon\Controller\SiteController;
use Salon\Http\Request;
use Salon\Http\Router;
use Salon\Http\View;

/** Every HTTP route in the application, in one readable list. */
function salon_routes(): Router
{
    $router = new Router();
    $site = new SiteController();
    $booking = new BookingController();
    $auth = new AuthController();
    $dash = new DashboardController();
    $adminBookings = new BookingAdminController();
    $catalog = new CatalogController();
    $statuses = new StatusController();
    $schedule = new ScheduleController();
    $content = new ContentController();
    $sms = new SmsController();
    $settings = new SettingController();
    $team = new TeamController();
    $theme = new ThemeController();
    $activity = new ActivityController();
    $tenants = new TenantController();

    // Public site
    $router->get('/', fn (Request $r) => $site->home($r));
    $router->get('/slots', fn (Request $r) => $site->slots($r));
    $router->get('/health', fn (Request $r) => $site->health($r));
    $router->post('/book', fn (Request $r) => $booking->store($r));

    // Admin sign in
    $router->get('/admin/login', fn (Request $r) => $auth->loginPage($r));
    $router->post('/admin/login', fn (Request $r) => $auth->login($r));
    $router->post('/admin/logout', fn (Request $r) => $auth->logout($r));
    $router->get('/admin', fn (Request $r) => $dash->index($r));

    // Bookings
    $router->get('/admin/bookings', fn (Request $r) => $adminBookings->index($r));
    $router->get('/admin/bookings/export', fn (Request $r) => $adminBookings->export($r));
    $router->get('/admin/bookings/new', fn (Request $r) => $adminBookings->create($r));
    $router->post('/admin/bookings/new', fn (Request $r) => $adminBookings->store($r));
    $router->post('/admin/bookings/status', fn (Request $r) => $adminBookings->status($r));
    $router->post('/admin/bookings/delete', fn (Request $r) => $adminBookings->delete($r));
    $router->post('/admin/bookings/{id}/sms', fn (Request $r, string $id) => $sms->resend($r, $id));
    $router->get('/admin/bookings/{id}', fn (Request $r, string $id) => $adminBookings->edit($r, $id));
    $router->post('/admin/bookings/{id}', fn (Request $r, string $id) => $adminBookings->store($r, $id));

    // Catalogue
    $router->get('/admin/services', fn (Request $r) => $catalog->index($r));
    $router->post('/admin/services/save', fn (Request $r) => $catalog->saveService($r));
    $router->post('/admin/services/delete', fn (Request $r) => $catalog->deleteService($r));
    $router->post('/admin/categories/save', fn (Request $r) => $catalog->saveCategory($r));
    $router->post('/admin/categories/delete', fn (Request $r) => $catalog->deleteCategory($r));

    // Workflow and calendar
    $router->get('/admin/statuses', fn (Request $r) => $statuses->index($r));
    $router->post('/admin/statuses/save', fn (Request $r) => $statuses->save($r));
    $router->post('/admin/statuses/delete', fn (Request $r) => $statuses->delete($r));
    $router->get('/admin/schedule', fn (Request $r) => $schedule->index($r));
    $router->post('/admin/schedule/hours', fn (Request $r) => $schedule->saveHours($r));
    $router->post('/admin/schedule/special', fn (Request $r) => $schedule->saveSpecial($r));
    $router->post('/admin/schedule/special-delete', fn (Request $r) => $schedule->deleteSpecial($r));

    // Website copy
    $router->get('/admin/content', fn (Request $r) => $content->index($r));
    $router->post('/admin/content/text', fn (Request $r) => $content->saveText($r));
    $router->post('/admin/content/block', fn (Request $r) => $content->saveBlock($r));
    $router->post('/admin/content/block-delete', fn (Request $r) => $content->deleteBlock($r));
    $router->post('/admin/content/review', fn (Request $r) => $content->saveReview($r));
    $router->post('/admin/content/review-delete', fn (Request $r) => $content->deleteReview($r));

    // SMS
    $router->get('/admin/sms', fn (Request $r) => $sms->index($r));
    $router->post('/admin/sms/save', fn (Request $r) => $sms->save($r));
    $router->post('/admin/sms/test', fn (Request $r) => $sms->test($r));
    $router->post('/admin/sms/retry', fn (Request $r) => $sms->retry($r));

    // Settings, team, appearance
    $router->get('/admin/settings', fn (Request $r) => $settings->index($r));
    $router->post('/admin/settings/save', fn (Request $r) => $settings->save($r));
    $router->get('/admin/team', fn (Request $r) => $team->index($r));
    $router->post('/admin/team/save', fn (Request $r) => $team->save($r));
    $router->post('/admin/team/delete', fn (Request $r) => $team->delete($r));
    $router->post('/admin/team/unlock', fn (Request $r) => $team->unlock($r));
    $router->get('/admin/theme', fn (Request $r) => $theme->edit($r));
    $router->post('/admin/theme/save', fn (Request $r) => $theme->save($r));
    $router->post('/admin/theme/password', fn (Request $r) => $theme->password($r));
    $router->get('/admin/activity', fn (Request $r) => $activity->index($r));

    // Tenant management (platform admin only)
    $router->get('/admin/tenants', fn (Request $r) => $tenants->index($r));
    $router->get('/admin/tenants/new', fn (Request $r) => $tenants->create($r));
    $router->post('/admin/tenants/new', fn (Request $r) => $tenants->create($r));
    $router->post('/admin/tenants/delete', fn (Request $r) => $tenants->delete($r));
    $router->get('/admin/tenants/{id}', fn (Request $r, string $id) => $tenants->edit($r, $id));
    $router->post('/admin/tenants/{id}', fn (Request $r, string $id) => $tenants->edit($r, $id));
    $router->get('/admin/tenants/{id}/stats', fn (Request $r, string $id) => $tenants->stats($r, $id));

    $router->notFound(fn (Request $r, string $message) => View::render('site/not_found',
        ['message' => $message, 'settings' => \Salon\Model\Setting::all(), 'text' => [], 'nonce' => SALON_NONCE],
        'layout_site'));

    return $router;
}
