<?php

namespace App\Services;

use App\Ninja\Datatables\CategoryDatatable;
use App\Ninja\Datatables\ProductDatatable;
use App\Ninja\Repositories\CategoryRepository;
use App\Ninja\Repositories\ProductRepository;
use Auth;
use Utils;

class CategoryService extends BaseService
{
    /**
     * @var DatatableService
     */
    protected $datatableService;

    /**
     * @var ProductRepository
     */
    protected $categoryRepo;

    /**
     * ProductService constructor.
     *
     * @param DatatableService  $datatableService
     * @param CategoryRepository $categoryRepo
     */
    public function __construct(DatatableService $datatableService, CategoryRepository $categoryRepo)
    {
        $this->datatableService = $datatableService;
        $this->categoryRepo = $categoryRepo;
    }

    /**
     * @return ProductRepository
     */
    protected function getRepo()
    {
        return $this->categoryRepo;
    }

    /**
     * @param $accountId
     * @param mixed $search
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDatatable($accountId, $search)
    {
        $datatable = new CategoryDatatable(true);
        $query = $this->categoryRepo->find($accountId, $search);

        if (! Utils::hasPermission('view_all')) {
            $query->where('categories.user_id', '=', Auth::user()->id);
        }

        return $this->datatableService->createDatatable($datatable, $query);
    }
}
