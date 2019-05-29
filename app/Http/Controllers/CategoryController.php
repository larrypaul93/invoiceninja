<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Ninja\Datatables\CategoryDatatable;
use App\Ninja\Repositories\CategoryRepository;
use App\Services\CategoryService;
use Auth;
use Input;
use Redirect;
use Session;
use URL;
use Utils;
use View;
class CategoryController extends Controller
{
    /**
     * @var CategoryService
     */
    protected $categoryService;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepo;

    /**
     * ProductController constructor.
     *
     * @param CategoryService $categoryService
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(CategoryService $categoryService, CategoryRepository $categoryRepository)
    {
        //parent::__construct();

        $this->categoryService = $categoryService;
        $this->categoryRepo = $categoryRepository;
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        return View::make('list_wrapper', [
            'entityType' => ENTITY_CATEGORY,
            'datatable' => new CategoryDatatable(),
            'title' => trans('texts.categories'),
            'statuses' => Category::getStatuses(),
        ]);
    }

    public function show($publicId)
    {
        Session::reflash();

        return Redirect::to("category/$publicId/edit");
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDatatable()
    {
        return $this->categoryService->getDatatable(Auth::user()->account_id, Input::get('sSearch'));
    }

    /**
     * @param $publicId
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function edit($publicId)
    {
        $account = Auth::user()->account;
        $category = Category::scope($publicId)->withTrashed()->firstOrFail();

        $data = [
            'account' => $account,
            'category' => $category,
            'categories' => Category::scope()->where("parent_id",0)->get(['id','name']),
            'entity' => $category,
            'method' => 'PUT',
            'url' => 'category/'.$publicId,
            'title' => trans('texts.edit_category'),
        ];

        return View::make('category.edit', $data);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $account = Auth::user()->account;

        $data = [
            'account' => $account,
            'category' => null,
            'categories' => Category::scope()->where("parent_id",0)->get(['id','name']),
            'method' => 'POST',
            'url' => 'category',
            'title' => trans('texts.create_category'),
        ];

        return View::make('category.edit', $data);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        return $this->save();
    }

    /**
     * @param $publicId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($publicId)
    {
        return $this->save($publicId);
    }

    /**
     * @param bool $productPublicId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    private function save($productPublicId = false)
    {
        if ($productPublicId) {
            $product = Category::scope($productPublicId)->withTrashed()->firstOrFail();
        } else {
            $product = Category::createNew();
        }

        $this->categoryRepo->save(Input::all(), $product);

        $message = $productPublicId ? trans('texts.updated_category') : trans('texts.created_category');
        Session::flash('message', $message);

        return Redirect::to("category/{$product->public_id}/edit");
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulk()
    {
        $action = Input::get('action');
        $ids = Input::get('public_id') ? Input::get('public_id') : Input::get('ids');
        $count = $this->categoryService->bulk($ids, $action);

        $message = Utils::pluralize($action.'d_product', $count);
        Session::flash('message', $message);

        return $this->returnBulk(ENTITY_CATEGORY, $action, $ids);
    }
}
