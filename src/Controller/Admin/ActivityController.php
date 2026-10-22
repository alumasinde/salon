<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\Http\Activity;
use Salon\Http\AdminController;
use Salon\Http\Request;

final class ActivityController extends AdminController
{
    public function index(Request $request): string
    {
        return $this->render('admin/activity', 'Activity', [
            'rows' => Activity::recent(60),
        ], 'activity');
    }
}
