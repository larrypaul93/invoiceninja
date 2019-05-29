<?php

namespace App\Ninja\Repositories;

use App\Models\Product;
use Modules\Category\Models\Category;
use Utils;
use DB;

class ProductRepository extends BaseRepository
{
    public function getClassName()
    {
        return 'App\Models\Product';
    }

    public function all()
    {
        return Product::scope()
                ->withTrashed()
                ->get();
    }

    public function find($accountId, $filter = null)
    {
        $query = DB::table('products')
                
                ->leftJoin("category","products.category_id","=","category.id")
                ->leftJoin("clients",function($query){
                    $query->on("products.supplier_id","=","clients.id")
                        ->where("clients.type","=","Supplier");
                })
                ->where('products.account_id', '=', $accountId)
                ->select(
                    'products.public_id',
                    'products.product_key',
                    'products.notes',
                    'products.status',
                    'products.sku',
                    'products.purchase_price',
                    'clients.name as supplier_name',
                    'products.upc',
                    'products.part_no',
                    'category.name as category',
                    'products.cost',
                    'products.tax_name1 as tax_name',
                    'products.tax_rate1 as tax_rate',
                    'products.deleted_at',
                    'products.is_deleted'
                );

        if ($filter) {
            $query->where(function ($query) use ($filter) {
                $query->where('products.product_key', 'like', '%'.$filter.'%')
                      ->orWhere('products.notes', 'like', '%'.$filter.'%');
            });
        }

        $this->applyFilters($query, ENTITY_PRODUCT);

        return $query;
    }

    public function save($data, $product = null)
    {
        $publicId = isset($data['public_id']) ? $data['public_id'] : false;

        if ($product) {
            // do nothing
        } elseif ($publicId) {
            $product = Product::scope($publicId)->firstOrFail();
            \Log::warning('Entity not set in product repo save');
        } else {
            $product = Product::createNew();
        }

        $product->fill($data);
        $product->product_key = isset($data['product_key']) ? trim($data['product_key']) : '';
        $product->notes = isset($data['notes']) ? trim($data['notes']) : '';
        $product->cost = isset($data['cost']) ? Utils::parseFloat($data['cost']) : 0;
        $product->qty = isset($data['qty']) ? Utils::parseFloat($data['qty']) : 1;
        $product->purchase_price = isset($data['purchase_price']) ? Utils::parseFloat($data['purchase_price']) : 0;
        $product->supplier_id = isset($data['supplier_id']) ? $data['supplier_id'] : 0;
        $this->getSku($product);

        $product->save();

        return $product;
    }

    public function getSku($product){

        if(!$product->sku) {
            if ($product->category_id && !$product->sub_category_id) {
                $category = Category::where("id", $product->category_id)->first();

                if ($category) {
                    if (!$category->sku_prefix) {
                        $_product = Product::where("category_id", $product->category_id)->orderBy("sku", "desc")->first();
                        if($_product) {
                            if ($_product->sku && is_numeric($_product->sku)) {
                                $product->sku = $_product->sku + 1;
                            } else if ($_product->sku) {
                                $sku = $_product->sku;
                                $arr = preg_split('/(?<=[A-Za-z])(?=[0-9]+)/i', $sku);
                                if (!empty($arr)) {
                                    $prefix = $arr[0];
                                    $number = $arr[1] + 1;
                                    $product->sku = $prefix . $number;
                                }
                            }
                        }
                    }
                    else {
                        $sku = $category->sku_prefix . $category->next_number;
                        $category->next_number += 1;
                        $category->save();
                        $product->sku =  $sku;
                    }

                }

            }
            if ($product->sub_category_id) {
                $category = Category::where("id", $product->sub_category_id)->first();

                if ($category) {
                    if (!$category->sku_prefix) {
                        $_product = Product::where("sub_category_id", $product->sub_category_id)->orderBy("sku", "desc")->first();
                        if($_product){
                            if ($_product->sku && is_numeric($_product->sku)) {
                                $product->sku = $_product->sku + 1;
                            }
                            else if ($_product->sku) {
                                $sku = $_product->sku;
                                $arr = preg_split('/(?<=[A-Za-z])(?=[0-9]+)/i',$sku);
                                if(!empty($arr)){
                                    $prefix  = $arr[0];
                                    $number = $arr[1] + 1;
                                    $product->sku = $prefix.$number;
                                }
                            }
                        }

                    }
                    else {
                        $sku = $category->sku_prefix . $category->next_number;
                        $category->next_number += 1;
                        $category->save();
                        $product->sku =  $sku;
                    }

                }

            }

        }

        $product->save();

    }
    public function findPhonetically($productName)
    {
        $productNameMeta = metaphone($productName);

        $map = [];
        $max = SIMILAR_MIN_THRESHOLD;
        $productId = 0;

        $products = Product::scope()->get();

        foreach ($products as $product) {
            if (! $product->product_key) {
                continue;
            }

            $map[$product->id] = $product;
            $similar = similar_text($productNameMeta, metaphone($product->product_key), $percent);

            if ($percent > $max) {
                $productId = $product->id;
                $max = $percent;
            }
        }

        return ($productId && isset($map[$productId])) ? $map[$productId] : null;
    }
    
}
