<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\Http\Activity;
use Salon\Http\AdminController;
use Salon\Http\Flash;
use Salon\Http\Request;
use Salon\Http\Validator;
use Salon\Model\Service;

final class CatalogController extends AdminController
{
    public function index(Request $request): string
    {
        return $this->render('admin/services', 'Services', [
            'services' => Service::all(),
            'categories' => Service::categories(),
        ], 'services');
    }

    public function saveService(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('services');
        $this->requireCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $validator = Validator::make($_POST, [
            'name' => 'required|max:120',
            'price' => 'int',
            'deposit' => 'int',
            'duration' => 'max:40',
            'description' => 'max:500',
            'category_id' => 'int',
        ]);
        if ($validator->fails()) {
            Flash::push($validator->first(), 'err');
            redirect('admin/services');
        }
        $saved = Service::save($_POST, $id);
        Activity::log((int) $admin['id'], $id > 0 ? 'service_update' : 'service_create', $validator->value('name'), $request->ip);
        Flash::push($saved > 0 ? 'Service saved.' : 'Nothing changed.');
        redirect('admin/services');
    }

    public function deleteService(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('services');
        $this->requireCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $service = Service::find($id);
        if ($service !== null) {
            Service::delete($id);
            Activity::log((int) $admin['id'], 'service_delete', (string) $service['name'], $request->ip);
            Flash::push('Deleted ' . $service['name'] . '.');
        }
        redirect('admin/services');
    }

    public function saveCategory(Request $request): string
    {
        $this->requirePost($request);
        $this->guard('services');
        $this->requireCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $validator = Validator::make($_POST, ['name' => 'required|max:60', 'blurb' => 'max:255']);
        if ($validator->fails()) {
            Flash::push($validator->first(), 'err');
            redirect('admin/services');
        }
        try {
            Service::saveCategory($_POST, $id);
            Flash::push('Category saved.');
        } catch (\Throwable $t) {
            Flash::push('That category name is already used.', 'err');
        }
        redirect('admin/services');
    }

    public function deleteCategory(Request $request): string
    {
        $this->requirePost($request);
        $this->guard('services');
        $this->requireCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $removed = Service::deleteCategory($id);
        Flash::push($removed ? 'Category removed.' : 'Move or delete the services in that category first.',
            $removed ? 'ok' : 'err');
        redirect('admin/services');
    }
}
