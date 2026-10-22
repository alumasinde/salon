<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\Http\Activity;
use Salon\Http\AdminController;
use Salon\Http\Flash;
use Salon\Http\Request;
use Salon\Http\Validator;
use Salon\Model\Schedule;

/** Opening hours, closed days and calendar overrides. */
final class ScheduleController extends AdminController
{
    public function index(Request $request): string
    {
        $byDay = Schedule::byDay();
        $rows = [];
        for ($day = 1; $day <= 6; $day++) {
            $rows[] = $byDay[$day];
        }
        $rows[] = $byDay[0];
        return $this->render('admin/schedule', 'Opening hours', [
            'rows' => $rows,
            'specials' => Schedule::specials(),
            'grouped' => Schedule::groupedWeek(),
            'open' => Schedule::openState(),
        ], 'schedule');
    }

    public function saveHours(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('schedule');
        $this->requireCsrf();
        $days = $_POST['day'] ?? [];
        if (!is_array($days)) {
            redirect('admin/schedule');
        }
        foreach ($days as $day => $values) {
            $day = (int) $day;
            if ($day < 0 || $day > 6 || !is_array($values)) {
                continue;
            }
            $validator = Validator::make([
                'opens' => (string) ($values['opens'] ?? ''),
                'closes' => (string) ($values['closes'] ?? ''),
            ], ['opens' => 'required', 'closes' => 'required']);
            if ($validator->fails()) {
                Flash::push($validator->first(), 'err');
                redirect('admin/schedule');
            }
            Schedule::save($day, [
                'opens' => $validator->value('opens'),
                'closes' => $validator->value('closes'),
                'closed' => !empty($values['closed']),
                'note' => (string) ($values['note'] ?? ''),
            ]);
        }
        Activity::log((int) $admin['id'], 'hours_update', '', $request->ip);
        Flash::push('Opening hours saved.');
        redirect('admin/schedule');
    }

    public function saveSpecial(Request $request): string
    {
        $this->requirePost($request);
        $this->guard('schedule');
        $this->requireCsrf();
        $validator = Validator::make($_POST, ['date' => 'required|date', 'label' => 'max:80',
            'opens' => 'max:8', 'closes' => 'max:8']);
        if ($validator->fails()) {
            Flash::push($validator->first(), 'err');
            redirect('admin/schedule');
        }
        Schedule::saveSpecial($_POST, (int) ($_POST['id'] ?? 0));
        Flash::push('Saved for ' . $validator->value('date') . '.');
        redirect('admin/schedule');
    }

    public function deleteSpecial(Request $request): string
    {
        $this->requirePost($request);
        $this->guard('schedule');
        $this->requireCsrf();
        Schedule::deleteSpecial((int) ($_POST['id'] ?? 0));
        Flash::push('Override removed.');
        redirect('admin/schedule');
    }
}
