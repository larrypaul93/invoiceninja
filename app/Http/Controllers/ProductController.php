<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\TaxRate;
use App\Ninja\Datatables\ProductDatatable;
use App\Ninja\Repositories\ProductRepository;
use App\Services\ProductService;
use Auth;
use Input;
use Modules\Category\Models\Category;
use Modules\Suppliers\Models\Suppliers;
use Redirect;
use Session;
use URL;
use Utils;
use View;

/**
 * Class ProductController.
 */
class ProductController extends BaseController
{
    /**
     * @var ProductService
     */
    protected $productService;

    /**
     * @var ProductRepository
     */
    protected $productRepo;

    /**
     * ProductController constructor.
     *
     * @param ProductService $productService
     */
    public function __construct(ProductService $productService, ProductRepository $productRepo)
    {
        //parent::__construct();

        $this->productService = $productService;
        $this->productRepo = $productRepo;
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        return View::make('list_wrapper', [
            'entityType' => ENTITY_PRODUCT,
            'datatable' => new ProductDatatable(),
            'title' => trans('texts.products'),
            'statuses' => Product::getStatuses(),
        ]);
    }

    public function show($publicId)
    {
        Session::reflash();

        return Redirect::to("products/$publicId/edit");
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDatatable()
    {
        return $this->productService->getDatatable(Auth::user()->account_id, Input::get('sSearch'));
    }

    /**
     * @param $publicId
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function edit($publicId)
    {
        $account = Auth::user()->account;
        $product = Product::scope($publicId)->withTrashed()->firstOrFail();

        $data = [
          'account' => $account,
          'categories' => Category::scope()->where("parent",0)->get(['id','name']),
          'taxRates' => $account->invoice_item_taxes ? TaxRate::scope()->whereIsInclusive(false)->get() : null,
          'product' => $product,
          'subCats'=>Category::scope()->where("parent",$product->category_id)->get(['id','name']),
          'suppliers'=>Suppliers::scope()->orderBy("name","asc")->get(['id','name']),
          'entity' => $product,
          'method' => 'PUT',
          'url' => 'products/'.$publicId,
          'title' => trans('texts.edit_product'),
        ];

        return View::make('accounts.product', $data);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $account = Auth::user()->account;

        $data = [
          'account' => $account,
          'categories' => Category::scope()->where("parent",0)->get(['id','name']),
          'taxRates' => $account->invoice_item_taxes ? TaxRate::scope()->whereIsInclusive(false)->get() : null,
          'product' => null,
          'suppliers'=> Suppliers::scope()->orderBy("name","asc")->get(['id','name']),
          'subCats'=>[],
          'method' => 'POST',
          'url' => 'products',
          'title' => trans('texts.create_product'),
        ];

        return View::make('accounts.product', $data);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        return $this->save();
    }

    public function duplicate($publicId){
        $account = Auth::user()->account;
        $product = Product::scope($publicId)->withTrashed()->firstOrFail();
        $product->id = $product->public_id = null;
        $product->sku = null;
        $data = [
          'account' => $account,
          'categories' => Category::scope()->where("parent",0)->get(['id','name']),
          'taxRates' => $account->invoice_item_taxes ? TaxRate::scope()->whereIsInclusive(false)->get(['id', 'name', 'rate']) : null,
          'product' => $product,
          'subCats'=>Category::scope()->where("parent",$product->category_id)->get(['id','name']),
          'suppliers'=>Suppliers::scope()->orderBy("name","asc")->get(['id','name']),
          'entity' => $product,
          'method' => 'POST',
          'url' => 'products',
          'title' => trans('texts.edit_product'),
        ];

        return View::make('accounts.product', $data);
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
            $product = Product::scope($productPublicId)->withTrashed()->firstOrFail();
        } else {
            $product = Product::createNew();
        }

        $this->productRepo->save(Input::all(), $product);

        $message = $productPublicId ? trans('texts.updated_product') : trans('texts.created_product');
        Session::flash('message', $message);

        if (in_array(request('action'), ['archive', 'delete', 'restore', 'invoice'])) {
            return self::bulk();
        }

        return Redirect::to("products/{$product->public_id}/edit");
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulk()
    {
        $action = Input::get('action');
        $ids = Input::get('public_id') ? Input::get('public_id') : Input::get('ids');
        if ($action == 'invoice') {
            $products = Product::scope($ids)->get();
            $data = [];
            foreach ($products as $product) {
                $data[] = $product->product_key;
            }
            return redirect("invoices/create")->with('selectedProducts', $data);
        } else {
            $count = $this->productService->bulk($ids, $action);
        }

        $message = Utils::pluralize($action.'d_product', $count);
        Session::flash('message', $message);

        return $this->returnBulk(ENTITY_PRODUCT, $action, $ids);
    }
}
