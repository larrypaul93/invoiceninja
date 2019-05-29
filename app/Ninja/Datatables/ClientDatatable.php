<?php

namespace App\Ninja\Datatables;

use Auth;
use URL;
use Utils;
use App\Models\Invoice;
use DB;

class ClientDatatable extends EntityDatatable
{
    public $entityType = ENTITY_CLIENT;
    public $sortCol = 4;

    public function columns()
    {
        return [
            [
                'account',
                function ($model) {
                    if($model->suffix && $model->suffix != ''){
                        return link_to("clients/{$model->public_id}", $model->account ?$model->account." ({$model->suffix})": '')->toHtml();
                    }
                    else
                    return link_to("clients/{$model->public_id}", $model->account ?: '')->toHtml();
                },
            ],
            [
                'work_phone',
                function($model){
                    return $model->work_phone;
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
            ],
            /*[
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
            ],*/
            [
                'balance',
                function ($model) {
                    $paid = Invoice::where("client_id",$model->id)
                        ->where("invoice_status_id",">",1)
                        ->where("invoice_type_id",1)
                        ->where("account_id",Auth::user()->account_id)
                        ->sum("balance");
                    $interest_balance = Invoice::where("client_id",$model->id)
                    ->where("invoice_status_id",">",1)
                    ->where("invoice_type_id",1)
                    ->where("interest",1)
                    ->where("account_id",Auth::user()->account_id)
                    ->whereRaw("DATEDIFF(now(),invoice_date) > 30")
                    ->sum(DB::raw("0.1 * balance / 100 * DATEDIFF(now(),invoice_date)"));
                    return Utils::formatMoney($paid+round($interest_balance,2), $model->currency_id, $model->country_id);
                },
            ],
        ];
    }

    public function actions()
    {
        return [
            [
                trans('texts.edit_client'),
                function ($model) {
                    return URL::to("clients/{$model->public_id}/edit");
                    //'http://crm.greaseducks.com/admin/accounts/{{$client->id}}/edit?redirect={{ URL::to('clients/' . $client->public_id ) }}';
                   // return 'http://crm.greaseducks.com/admin/accounts/'.$model->id.'/edit?redirect='.URL::to('clients/');       
                },
                function ($model) {
                    return Auth::user()->can('editByOwner', [ENTITY_CLIENT, $model->user_id]);
                },
            ],
            [
                '--divider--', function () {
                    return false;
                },
                function ($model) {
                    $user = Auth::user();

                    return $user->can('editByOwner', [ENTITY_CLIENT, $model->user_id]) && ($user->can('create', ENTITY_TASK) || $user->can('create', ENTITY_INVOICE));
                },
            ],
            [
                trans('texts.new_task'),
                function ($model) {
                    return URL::to("tasks/create/{$model->public_id}");
                },
                function ($model) {
                    return Auth::user()->can('create', ENTITY_TASK);
                },
            ],
            [
                trans('texts.new_invoice'),
                function ($model) {
                    return URL::to("invoices/create/{$model->public_id}");
                },
                function ($model) {
                    return Auth::user()->can('create', ENTITY_INVOICE);
                },
            ],
            [
                trans('texts.new_quote'),
                function ($model) {
                    return URL::to("quotes/create/{$model->public_id}");
                },
                function ($model) {
                    return Auth::user()->hasFeature(FEATURE_QUOTES) && Auth::user()->can('create', ENTITY_QUOTE);
                },
            ],
            [
                '--divider--', function () {
                    return false;
                },
                function ($model) {
                    $user = Auth::user();

                    return ($user->can('create', ENTITY_TASK) || $user->can('create', ENTITY_INVOICE)) && ($user->can('create', ENTITY_PAYMENT) || $user->can('create', ENTITY_CREDIT) || $user->can('create', ENTITY_EXPENSE));
                },
            ],
            [
                trans('texts.enter_payment'),
                function ($model) {
                    return URL::to("payments/create/{$model->public_id}");
                },
                function ($model) {
                    return Auth::user()->can('create', ENTITY_PAYMENT);
                },
            ],
            [
                trans('texts.enter_credit'),
                function ($model) {
                    return URL::to("credits/create/{$model->public_id}");
                },
                function ($model) {
                    return Auth::user()->can('create', ENTITY_CREDIT);
                },
            ],
            [
                trans('texts.enter_expense'),
                function ($model) {
                    return URL::to("expenses/create/0/{$model->public_id}");
                },
                function ($model) {
                    return Auth::user()->can('create', ENTITY_EXPENSE);
                },
            ],
        ];
    }
}
