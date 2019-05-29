<?php

namespace App\Ninja\Datatables;

use Auth;
use Modules\Category\Models\Category;
use Str;
use URL;
use Utils;

class ProductDatatable extends EntityDatatable
{
    public $entityType = ENTITY_PRODUCT;
    public $sortCol = 4;

    public function columns()
    {
        return [
            [
                'sku',
                function ($model) {
                    return $model->sku;
                },
            ],
            [
                'cost',
                function ($model) {
                    return Utils::formatMoney($model->cost);
                },
            ],
            [
                'product_key',
                function ($model) {
                    return link_to('products/'.$model->public_id.'/edit', $model->product_key)->toHtml();
                },
            ],
            [
                'status',
                function ($model) {
                    return $model->status;
                },
            ],
            [
                'category',
                function ($model) {

                    return $model->category;


                },
            ],
            [
                'part_no',
                function ($model) {
                    return $model->part_no;
                },
            ],
            [
                'upc',
                function ($model) {
                    return $model->upc;
                },
            ],
            [
                'purchase_price',
                function ($model) {
                    return Utils::formatMoney($model->purchase_price);
                },
            ],
            [
                'supplier_name',
                function ($model) {
                    return $model->supplier_name;
                },
            ]

            /*,
            [
                'tax_rate',
                function ($model) {
                    return $model->tax_rate ? ($model->tax_name . ' ' . $model->tax_rate . '%') : '';
                },
                Auth::user()->account->invoice_item_taxes,
            ],*/
        ];
    }

    public function actions()
    {
        return [
            [
                uctrans('texts.edit_product'),
                function ($model) {
                    return URL::to("products/{$model->public_id}/edit");
                },
            ],
            [
                uctrans('texts.duplicate_product'),
                function ($model) {
                    return URL::to("products/{$model->public_id}/duplicate");
                },
            ],
        ];
    }

    public function rightAlignIndices()
    {
        return $this->alignIndices(['amount', 'balance', 'cost','purchase_price','rate']);
    }
}
