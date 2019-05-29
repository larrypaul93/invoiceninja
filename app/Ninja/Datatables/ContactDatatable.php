<?php

namespace App\Ninja\Datatables;

use Auth;
use URL;
use Utils;

class ContactDatatable extends EntityDatatable
{
    public $entityType = ENTITY_CONTACT;
    public $sortCol = 1;

    public function columns()
    {
        return [
            [
                'name',
                function ($model) {
                    return link_to("contacts/{$model->public_id}/edit", $model->first_name.' '.$model->last_name)->toHtml();
                    
                },
            ],
            [
                'email',
                function ($model) {
                    return $model->email;
                },
            ],
            [
                'phone_business',
                function($model){
                    return $model->phone_business;
                }
            ],
            [
                'phone_cell',
                function($model){
                    return $model->phone_cell;
                }
            ],
            [
                'address',
                function($model){
                    return $model->address;
                }
            ],
            [
                'city',
                function($model){
                    return $model->city;
                }
            ]/*,
            [
                'state',
                function($model){
                    return $model->state;
                }
            ],
            [
                'zip',
                function($model){
                    return $model->zip;
                }
            ]*/
        ];
    }

    public function actions()
    {
        return [
            [
                trans('texts.view_client_portal'),
                function ($model) {
                    return ["url"=>URL::to("/client/dashboard/{$model->contact_key}"),"attributes"=>'target="_blank"'];
                },
                function ($model) {
                    return Auth::user()->confirmed;
                },
            ],

        ];
    }
}
