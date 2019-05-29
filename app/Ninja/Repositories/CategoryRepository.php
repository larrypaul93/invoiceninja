<?php

namespace App\Ninja\Repositories;

use App\Models\Category;
use App\Models\Product;
use Utils;
use DB;

class CategoryRepository extends BaseRepository
{
    public function getClassName()
    {
        return 'App\Models\Category';
    }

    public function all()
    {
        return Category::scope()
                ->withTrashed()
                ->get();
    }

    public function find($accountId, $filter = null)
    {
        $query = DB::table('categories as categorys')
                ->select(
                    'id',
                    'name'
                )
            ->where('account_id', '=', $accountId);



        $this->applyFilters($query, ENTITY_CATEGORY);

        return $query;
    }

    public function save($data, $category = null)
    {
        $publicId = isset($data['public_id']) ? $data['public_id'] : false;

        if ($category) {
            // do nothing
        } elseif ($publicId) {
            $category = Category::scope($publicId)->firstOrFail();
            \Log::warning('Entity not set in Category repo save');
        } else {
            $category = Category::createNew();
        }

        $category->fill($data);
        $category->name = isset($data['name']) ? trim($data['name']) : '';
        $category->save();

        return $category;
    }


}
