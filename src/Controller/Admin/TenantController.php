<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\Http\Activity;
use Salon\Http\AdminController;
use Salon\Http\Flash;
use Salon\Http\Request;
use Salon\Http\Validator;
use Salon\Model\Tenant;
use RuntimeException;

/** The salon directory. Only a platform account holds the "tenants" ability. */
final class TenantController extends AdminController
{
    private const ABILITY = 'tenants';

    public function index(Request $request): string
    {
        return $this->render('admin/tenants', 'Tenants', [
            'tenants' => Tenant::all(),
        ], self::ABILITY);
    }

    public function create(Request $request): string
    {
        $actor = $this->guard(self::ABILITY);

        if ($request->isPost()) {
            $this->requirePost($request);
            $this->requireCsrf();
            $data = $this->validated();
            if ($data === null) {
                redirect('admin/tenants/new');
            }
            try {
                Tenant::create($data);
            } catch (RuntimeException $e) {
                Flash::push($e->getMessage(), 'err');
                redirect('admin/tenants/new');
            }
            Activity::log((int) $actor['id'], 'tenant_create', $data['domain'], $request->ip);
            Flash::push('Tenant added. Sign in to its domain to set it up.');
            redirect('admin/tenants');
        }

        return $this->render('admin/tenant_form', 'New tenant', ['tenant' => null], self::ABILITY);
    }

    public function edit(Request $request, string $id): string
    {
        $actor = $this->guard(self::ABILITY);
        $tenant = Tenant::findById((int) $id);
        if ($tenant === null) {
            Flash::push('That tenant no longer exists.', 'err');
            redirect('admin/tenants');
        }

        if ($request->isPost()) {
            $this->requirePost($request);
            $this->requireCsrf();
            $data = $this->validated();
            if ($data === null) {
                redirect('admin/tenants/' . (int) $id);
            }
            try {
                Tenant::update((int) $id, $data);
            } catch (RuntimeException $e) {
                Flash::push($e->getMessage(), 'err');
                redirect('admin/tenants/' . (int) $id);
            }
            Activity::log((int) $actor['id'], 'tenant_update', (string) $tenant['domain'], $request->ip);
            Flash::push('Tenant saved.');
            redirect('admin/tenants');
        }

        return $this->render('admin/tenant_form', 'Edit tenant', ['tenant' => $tenant], self::ABILITY);
    }

    public function delete(Request $request): string
    {
        $this->requirePost($request);
        $actor = $this->guard(self::ABILITY);
        $this->requireCsrf();

        $id = (int) ($_POST['id'] ?? 0);
        $tenant = Tenant::findById($id);
        if ($tenant === null) {
            Flash::push('That tenant no longer exists.', 'err');
            redirect('admin/tenants');
        }
        if ($id === (int) ($actor['tenant_id'] ?? 0)) {
            Flash::push('You cannot delete the tenant you are signed in to.', 'err');
            redirect('admin/tenants');
        }
        try {
            Tenant::delete($id);
        } catch (RuntimeException $e) {
            Flash::push($e->getMessage(), 'err');
            redirect('admin/tenants');
        }
        Activity::log((int) $actor['id'], 'tenant_delete', (string) $tenant['domain'], $request->ip);
        Flash::push('Tenant deleted.');
        redirect('admin/tenants');
    }

    public function stats(Request $request, string $id): string
    {
        $this->guard(self::ABILITY);
        $tenant = Tenant::findById((int) $id);
        if ($tenant === null) {
            Flash::push('That tenant no longer exists.', 'err');
            redirect('admin/tenants');
        }

        return $this->render('admin/tenant_stats', 'Tenant usage', [
            'tenant' => $tenant,
            'stats' => [
                'admin_count' => Tenant::countAdmins((int) $id),
                'booking_count' => Tenant::countBookingsThisMonth((int) $id),
                'can_add_admin' => Tenant::canAddAdmin((int) $id),
                'can_add_booking' => Tenant::canAddBooking((int) $id),
            ],
        ], self::ABILITY);
    }

    /** @return array<string,mixed>|null cleaned input, null when the form was rejected */
    private function validated(): ?array
    {
        $validator = Validator::make($_POST, [
            'name' => 'required|max:120',
            'domain' => 'required|max:255',
            'status' => 'in:active,pending,suspended',
            'plan' => 'in:basic,standard,premium',
            'max_admins' => 'required|int',
            'max_bookings_per_month' => 'required|int',
            'primary_color' => 'hex',
            'accent_color' => 'hex',
            'logo_url' => 'max:255',
            'favicon_url' => 'max:255',
        ]);
        if ($validator->fails()) {
            Flash::push($validator->first(), 'err');
            return null;
        }

        $domain = strtolower(trim($validator->value('domain')));
        $domain = (string) preg_replace('#^https?://#', '', $domain);
        $domain = rtrim((string) preg_replace('#^www\.#', '', $domain), '/');
        if (preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain) !== 1) {
            Flash::push('Use a plain domain name, like salon-name.co.ke.', 'err');
            return null;
        }

        return [
            'name' => $validator->value('name'),
            'domain' => $domain,
            'status' => $validator->value('status', 'pending'),
            'plan' => $validator->value('plan', 'basic'),
            'max_admins' => min(100, max(1, $validator->int('max_admins', 5))),
            'max_bookings_per_month' => min(100000, max(1, $validator->int('max_bookings_per_month', 500))),
            'primary_color' => $validator->value('primary_color', '#3a1233'),
            'accent_color' => $validator->value('accent_color', '#d6336c'),
            'logo_url' => $validator->value('logo_url'),
            'favicon_url' => $validator->value('favicon_url'),
            // Angle brackets would let a stylesheet close its own tag and write page markup.
            'custom_css' => str_replace(['<', '>'], '', mb_substr(trim((string) ($_POST['custom_css'] ?? '')), 0, 20000)),
        ];
    }
}
