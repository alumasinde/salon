<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\Http\Activity;
use Salon\Http\AdminController;
use Salon\Http\Auth;
use Salon\Http\Flash;
use Salon\Http\Request;
use Salon\Http\TenantResolver;
use Salon\Http\Validator;
use Salon\Model\Admin;
use Salon\Model\Tenant;

/** Team management: accounts, roles, SMS opt-in. Owners only. */
final class TeamController extends AdminController
{
    public function index(Request $request): string
    {
        $actor = $this->guard('admins');
        return $this->render('admin/team', 'Team', [
            'rows' => Admin::all(),
            'roles' => $this->roleOptions($actor),
        ], 'admins');
    }

    /** Only a platform account may hand out the platform role. */
    private function roleOptions(array $actor): array
    {
        $roles = Admin::ROLES;
        if (!Admin::canManage($actor, 'tenants')) {
            unset($roles['platform']);
        }
        return $roles;
    }

    public function save(Request $request): string
    {
        $this->requirePost($request);
        $actor = $this->guard('admins');
        $this->requireCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $target = $id > 0 ? Admin::find($id) : null;
        $roles = $this->roleOptions($actor);
        $validator = Validator::make($_POST, [
            'first_name' => 'required|max:60',
            'last_name' => 'max:60',
            'username' => 'required|max:60',
            'email' => 'email|max:160',
            'phone' => 'phone',
            'role' => 'in:' . implode(',', array_keys($roles)),
        ]);
        if ($validator->fails()) {
            Flash::push($validator->first(), 'err');
            redirect('admin/team');
        }
        if ($id === 0 && !Tenant::canAddAdmin(TenantResolver::getId())) {
            Flash::push('This salon has reached its limit of admin accounts.', 'err');
            redirect('admin/team');
        }
        if ($id === 0 && strlen((string) ($_POST['password'] ?? '')) < 10) {
            Flash::push('Give new accounts a password of at least 10 characters.', 'err');
            redirect('admin/team');
        }
        if ($target === null && Admin::findByUsername($validator->value('username')) !== null) {
            Flash::push('That username is taken.', 'err');
            redirect('admin/team');
        }
        if ($id > 0 && $target === null) {
            Flash::push('That account no longer exists.', 'err');
            redirect('admin/team');
        }
        $privileged = in_array($validator->value('role'), ['owner', 'platform'], true);
        if ($id > 0 && (int) $actor['id'] === $id && (!$privileged || empty($_POST['active']))
            && Admin::countOwners() <= 1) {
            Flash::push('You are the last account that can manage the team. Promote someone else first.', 'err');
            redirect('admin/team');
        }
        if ($id > 0) {
            Admin::update($id, $_POST);
        } else {
            $id = Admin::create($_POST);
        }
        if (strlen((string) ($_POST['password'] ?? '')) >= 10) {
            Admin::setPassword($id, (string) $_POST['password'], true);
        }
        Activity::log((int) $actor['id'], $target === null ? 'admin_create' : 'admin_update',
            $validator->value('username'), $request->ip);
        Flash::push('Account saved.');
        redirect('admin/team');
    }

    public function delete(Request $request): string
    {
        $this->requirePost($request);
        $actor = $this->guard('admins');
        $this->requireCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $actor['id']) {
            Flash::push('You cannot delete the account you are logged in with.', 'err');
            redirect('admin/team');
        }
        $target = Admin::find($id);
        $removed = $target !== null && Admin::delete($id);
        if ($removed) {
            Activity::log((int) $actor['id'], 'admin_delete', (string) $target['username'], $request->ip);
        }
        Flash::push($removed ? 'Account deleted.' : 'Cannot delete the last active account.', $removed ? 'ok' : 'err');
        redirect('admin/team');
    }

    public function unlock(Request $request): string
    {
        $this->requirePost($request);
        $actor = $this->guard('admins');
        $this->requireCsrf();
        \Salon\DB\Database::exec('DELETE FROM login_attempts WHERE (identifier = ? OR ip = ?) AND tenant_id = ?',
            [mb_strtolower(trim((string) ($_POST['username'] ?? ''))), (string) ($_POST['ip'] ?? ''),
                TenantResolver::getId()]);
        Activity::log((int) $actor['id'], 'login_unlock', (string) ($_POST['username'] ?? ''), $request->ip);
        Flash::push('Login attempts cleared.');
        redirect('admin/team');
    }
}
