<?php

namespace App\Ninja\Datatables;

use Auth;
use Str;
use URL;
use Utils;

class CategoryDatatable extends EntityDatatable
{
    public $entityType = ENTITY_CATEGORY;
    public $sortCol = 1;

    public function columns()
    {
        return [
            [
                'name',
                function ($model) {
                    return link_to('category/'.$model->public_id.'/edit', $model->name)->toHtml();
                },
            ],
            [
                'parent',
                function ($model) {
                    return link_to('category/'.$model->parent_id.'/edit', $model->parent->name)->toHtml();
                },
            ],

        ];
    }

    public function actions()
    {
        return [
            [
                uctrans('texts.edit_category'),
                function ($model) {
                    return URL::to("category/{$model->public_id}/edit");
                },
            ],
        ];
    }
}
