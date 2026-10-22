<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\Http\Activity;
use Salon\Http\AdminController;
use Salon\Http\Flash;
use Salon\Http\Request;
use Salon\Http\Validator;
use Salon\Model\Content;

/** Website copy, feature cards and testimonials. */
final class ContentController extends AdminController
{
    public function index(Request $request): string
    {
        return $this->render('admin/content', 'Website', [
            'texts' => Content::rows(),
            'blocks' => Content::allBlocks(),
            'reviews' => Content::allReviews(),
        ], 'content');
    }

    public function saveText(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('content');
        $this->requireCsrf();
        $key = (string) ($_POST['key'] ?? '');
        $value = mb_substr(trim((string) ($_POST['value'] ?? '')), 0, 4000);
        if ($key === '' || !preg_match('/^[a-z0-9_]{1,64}$/', $key)) {
            Flash::push('Unknown text field.', 'err');
            redirect('admin/content');
        }
        Content::setText($key, $value);
        Activity::log((int) $admin['id'], 'content_text', $key, $request->ip);
        Flash::push('Saved "' . $key . '".');
        redirect('admin/content');
    }

    public function saveBlock(Request $request): string
    {
        $this->requirePost($request);
        $this->guard('content');
        $this->requireCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $validator = Validator::make($_POST, [
            'block_type' => 'required|max:30',
            'title' => 'required|max:120',
            'body' => 'max:500',
            'sort' => 'int',
        ]);
        if ($validator->fails()) {
            Flash::push($validator->first(), 'err');
            redirect('admin/content');
        }
        Content::saveBlock($_POST, $id);
        Flash::push('Card saved.');
        redirect('admin/content');
    }

    public function deleteBlock(Request $request): string
    {
        $this->requirePost($request);
        $this->guard('content');
        $this->requireCsrf();
        Content::deleteBlock((int) ($_POST['id'] ?? 0));
        Flash::push('Card removed.');
        redirect('admin/content');
    }

    public function saveReview(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('content');
        $this->requireCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $validator = Validator::make($_POST, [
            'author' => 'required|max:80',
            'location' => 'max:80',
            'rating' => 'int',
            'body' => 'required|max:600',
            'sort' => 'int',
        ]);
        if ($validator->fails()) {
            Flash::push($validator->first(), 'err');
            redirect('admin/content');
        }
        if ((int) ($_POST['rating'] ?? 5) < 1 || (int) ($_POST['rating'] ?? 5) > 5) {
            $_POST['rating'] = 5;
        }
        $saved = Content::saveReview($_POST, $id);
        Activity::log((int) $admin['id'], $id > 0 ? 'review_update' : 'review_create', $validator->value('author'), $request->ip);
        Flash::push($saved > 0 ? 'Review saved.' : 'Fill in the author and the text.');
        redirect('admin/content');
    }

    public function deleteReview(Request $request): string
    {
        $this->requirePost($request);
        $this->guard('content');
        $this->requireCsrf();
        Content::deleteReview((int) ($_POST['id'] ?? 0));
        Flash::push('Review removed.');
        redirect('admin/content');
    }
}
