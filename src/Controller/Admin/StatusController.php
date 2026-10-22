<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\DB\Database;
use Salon\Http\Activity;
use Salon\Http\AdminController;
use Salon\Http\Flash;
use Salon\Http\Request;
use Salon\Http\Validator;
use Salon\Model\BookingStatus;

/** Booking workflow states: name, colour, whether they hold a slot. */
final class StatusController extends AdminController
{
    public function index(Request $request): string
    {
        $counts = Database::all('SELECT status_id, COUNT(*) AS total FROM bookings GROUP BY status_id');
        $map = array_column($counts, 'total', 'status_id');
        $rows = BookingStatus::all();
        foreach ($rows as &$row) {
            $row['total'] = (int) ($map[$row['id']] ?? 0);
        }
        unset($row);
        return $this->render('admin/statuses', 'Statuses', ['rows' => $rows], 'statuses');
    }

    public function save(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('statuses');
        $this->requireCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $validator = Validator::make($_POST, [
            'code' => 'required|max:30',
            'name' => 'required|max:60',
            'color' => 'hex',
            'text_color' => 'hex',
            'sort' => 'int',
        ]);
        if ($validator->fails()) {
            Flash::push($validator->first(), 'err');
            redirect('admin/statuses');
        }
        try {
            $saved = BookingStatus::save($_POST, $id);
        } catch (\Throwable) {
            Flash::push('That status code is already used.', 'err');
            redirect('admin/statuses');
        }
        if ($saved > 0 && !empty($_POST['is_default'])) {
            Database::exec('UPDATE booking_statuses SET is_default = 0 WHERE id <> ?', [$saved]);
        }
        Activity::log((int) $admin['id'], $id > 0 ? 'status_update' : 'status_create', $validator->value('code'), $request->ip);
        Flash::push('Status saved.');
        redirect('admin/statuses');
    }

    public function delete(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('statuses');
        $this->requireCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $removed = BookingStatus::delete($id);
        if ($removed) {
            Activity::log((int) $admin['id'], 'status_delete', (string) $id, $request->ip);
        }
        Flash::push($removed ? 'Status removed.' : 'Cannot remove a status that bookings still use.',
            $removed ? 'ok' : 'err');
        redirect('admin/statuses');
    }
}
