<?php

namespace App\Ninja\Datatables;

use App\Models\Invoice;
use App\Models\Payment;
use Auth;
use URL;
use Utils;

class InvoiceDatatable extends EntityDatatable
{
    public $entityType = ENTITY_INVOICE;
    public $sortCol = 3;

    public function columns()
    {
        $entityType = $this->entityType;

        return [
            [
                $entityType == ENTITY_INVOICE ? 'invoice_number' : 'quote_number',
                function ($model) use ($entityType) {
                    if (! Auth::user()->can('viewByOwner', [ENTITY_INVOICE, $model->user_id]) && !InvoiceDatatable::isInTags(Auth::user()->id, $model->tags)) {
                        return $model->invoice_number;
                    }

                    return link_to("{$entityType}s/{$model->public_id}/edit", $model->invoice_number, ['class' => Utils::getEntityRowClass($model)])->toHtml();
                },
            ],
            [
                'client_name',
                function ($model) {
                    if (! Auth::user()->can('viewByOwner', [ENTITY_CLIENT, $model->client_user_id]) && false) {
                        return Utils::getClientDisplayName($model);
                    }

                    return link_to("clients/{$model->client_public_id}", Utils::getClientDisplayName($model))->toHtml();
                },
                ! $this->hideClient,
            ],
            [
                'date',
                function ($model) {
                    return Utils::fromSqlDate($model->invoice_date);
                },
            ],
            [
                'amount',
                function ($model) {
                    return Utils::formatMoney($model->amount+$this->calcInterest($model), $model->currency_id, $model->country_id);
                },
            ],
            [
                'balance',
                function ($model) {
                    return $model->partial > 0 ?
                        trans('texts.partial_remaining', [
                            'partial' => Utils::formatMoney($model->partial, $model->currency_id, $model->country_id),
                            'balance' => Utils::formatMoney($model->balance, $model->currency_id, $model->country_id), ]
                        ) :
                        Utils::formatMoney($model->balance+$this->calcInterest($model), $model->currency_id, $model->country_id);
                },
                $entityType == ENTITY_INVOICE,
            ],
            [
                $entityType == ENTITY_INVOICE ? 'due_date' : 'valid_until',
                function ($model) {
                    return Utils::fromSqlDate($model->due_date_sql);
                },
            ],
            [
                "po_number",
                function ($model) {
                    return $model->po_number;
                },
            ],
            [
                'status',
                function ($model) use ($entityType) {
                    return $model->quote_invoice_id ? link_to("invoices/{$model->quote_invoice_id}/edit", trans('texts.converted'))->toHtml() : self::getStatusLabel($model);
                },
            ],
        ];
    }

    
    public function actions()
    {
        $entityType = $this->entityType;

        return [
            [
                trans("texts.edit_{$entityType}"),
                function ($model) use ($entityType) {
                    return URL::to("{$entityType}s/{$model->public_id}/edit");
                },
                function ($model) {
                    
                    return (Auth::user()->can('editByOwner', [ENTITY_INVOICE, $model->user_id]) || self::isInTags(Auth::user()->id, $model->tags))  &&  ($model->invoice_status_id != INVOICE_STATUS_VOIDED) && ($model->invoice_status_id != INVOICE_STATUS_PAID);
                },
            ],
            [
                trans("texts.clone_{$entityType}"),
                function ($model) use ($entityType) {
                    return URL::to("{$entityType}s/{$model->public_id}/clone");
                },
                function ($model) {
                    return Auth::user()->can('create', ENTITY_INVOICE) &&  ($model->invoice_status_id != INVOICE_STATUS_VOIDED) && ($model->invoice_status_id != INVOICE_STATUS_PAID);
                },
            ],
            [
                trans('texts.view_history'),
                function ($model) use ($entityType) {
                    return URL::to("{$entityType}s/{$entityType}_history/{$model->public_id}");
                },
                function($model){
                    return ($model->invoice_status_id != INVOICE_STATUS_VOIDED);
                }
            ],
            [
                '--divider--', function () {
                    return false;
                },
                function ($model) {
                    return Auth::user()->can('editByOwner', [ENTITY_INVOICE, $model->user_id]) || Auth::user()->can('create', ENTITY_PAYMENT) &&  ($model->invoice_status_id != INVOICE_STATUS_VOIDED) && ($model->invoice_status_id != INVOICE_STATUS_PAID);
                },
            ],
            [
                trans('texts.online_payment'),
                function ($model) use ($entityType) {

                    return URL::to("online-payment/{$model->invitation_key}/credit_card");
                },
                function ($model) use ($entityType){
                    return !empty($model->invitation_key) && $entityType == ENTITY_INVOICE && $model->balance != 0 &&  (Auth::user()->can('editByOwner', [ENTITY_INVOICE, $model->user_id]) || InvoiceDatatable::isInTags(Auth::user()->id, $model->tags)) &&  ($model->invoice_status_id != INVOICE_STATUS_VOIDED) && ($model->invoice_status_id != INVOICE_STATUS_PAID);
                },
            ],
            [
                trans('texts.pre_auth_payment'),
                function ($model) use ($entityType) {

                    return URL::to("pre-auth/{$model->invitation_key}/credit_card");
                },
                function ($model) use ($entityType){
                    return !empty($model->invitation_key) && $entityType == ENTITY_INVOICE && $model->invoice_status_id != INVOICE_STATUS_PRE_AUTH && $model->balance != 0 &&  (Auth::user()->can('editByOwner', [ENTITY_INVOICE, $model->user_id]) || InvoiceDatatable::isInTags(Auth::user()->id, $model->tags)) &&  ($model->invoice_status_id != INVOICE_STATUS_VOIDED) && ($model->invoice_status_id != INVOICE_STATUS_PAID);
                },
            ],
            [
                trans('texts.pre_auth_completion'),
                function ($model) use ($entityType) {
                    $payment = Payment::where("invoice_id",$model->id)->where("payment_status_id",PAYMENT_STATUS_PRE_AUTH)->first();
                    
                    return URL::to("pre-completion/".$payment->public_id);
                },
                function ($model) use ($entityType){
                    $payment = Payment::where("invoice_id",$model->id)->where("payment_status_id",PAYMENT_STATUS_PRE_AUTH)->first();
                    
                    return !empty($model->invitation_key) && $entityType == ENTITY_INVOICE && $payment  && $model->invoice_status_id == INVOICE_STATUS_PRE_AUTH &&  (Auth::user()->can('editByOwner', [ENTITY_INVOICE, $model->user_id])|| InvoiceDatatable::isInTags(Auth::user()->id, $model->tags)) &&  ($model->invoice_status_id != INVOICE_STATUS_VOIDED) && ($model->invoice_status_id != INVOICE_STATUS_PAID);
                },
            ],
            [
                trans('texts.mark_sent'),
                function ($model) use ($entityType) {
                    return "javascript:submitForm_{$entityType}('markSent', {$model->public_id})";
                },
                function ($model) {
                    return $model->invoice_status_id < INVOICE_STATUS_SENT && (Auth::user()->can('editByOwner', [ENTITY_INVOICE, $model->user_id])|| InvoiceDatatable::isInTags(Auth::user()->id, $model->tags)) &&  ($model->invoice_status_id != INVOICE_STATUS_VOIDED) && ($model->invoice_status_id != INVOICE_STATUS_PAID);
                },
            ],
            /*[
                trans('texts.mark_paid'),
                function ($model) use ($entityType) {
                    return "javascript:submitForm_{$entityType}('markPaid', {$model->public_id})";
                },
                function ($model) use ($entityType) {
                    return $entityType == ENTITY_INVOICE && $model->balance != 0 && Auth::user()->can('editByOwner', [ENTITY_INVOICE, $model->user_id]);
                },
            ],*/
            [
                trans('texts.enter_payment'),
                function ($model) {
                    return URL::to("payments/create/{$model->client_public_id}/{$model->public_id}");
                },
                function ($model) use ($entityType) {
                    return $entityType == ENTITY_INVOICE && $model->balance > 0 && Auth::user()->can('create', ENTITY_PAYMENT) &&  ($model->invoice_status_id != INVOICE_STATUS_VOIDED) && ($model->invoice_status_id != INVOICE_STATUS_PAID);
                },
            ],
            [
                trans('texts.view_quote'),
                function ($model) {
                    return URL::to("quotes/{$model->quote_id}/edit");
                },
                function ($model) use ($entityType) {
                    return $entityType == ENTITY_INVOICE && $model->quote_id && (Auth::user()->can('editByOwner', [ENTITY_INVOICE, $model->user_id])|| InvoiceDatatable::isInTags(Auth::user()->id, $model->tags)) &&  ($model->invoice_status_id != INVOICE_STATUS_VOIDED) && ($model->invoice_status_id != INVOICE_STATUS_PAID);
                },
            ],
            [
                trans('texts.void_invoice'),
                function ($model) {
                    //return URL::to("invoices/{$model->public_id}/voided");
                    return "javascript:voidInvoice({$model->public_id})";
                },
                function ($model) use ($entityType) {
                    return $entityType == ENTITY_INVOICE  && Auth::user()->can('editByOwner', [ENTITY_INVOICE, $model->user_id]) &&  ($model->invoice_status_id != INVOICE_STATUS_VOIDED) && ($model->invoice_status_id != INVOICE_STATUS_PAID);
                },
            ],
            [
                trans('texts.view_invoice'),
                function ($model) {
                    return URL::to("invoices/{$model->quote_invoice_id}/edit");
                },
                function ($model) use ($entityType) {
                    return $entityType == ENTITY_QUOTE && $model->quote_invoice_id && (Auth::user()->can('editByOwner', [ENTITY_INVOICE, $model->user_id])|| InvoiceDatatable::isInTags(Auth::user()->id, $model->tags)) &&  ($model->invoice_status_id != INVOICE_STATUS_VOIDED);
                },
            ],
            
            [
                trans('texts.convert_to_invoice'),
                function ($model) {
                    return "javascript:submitForm_quote('convert', {$model->public_id})";
                },
                function ($model) use ($entityType) {
                    return $entityType == ENTITY_QUOTE && ! $model->quote_invoice_id && (Auth::user()->can('editByOwner', [ENTITY_INVOICE, $model->user_id])|| InvoiceDatatable::isInTags(Auth::user()->id, $model->tags)) &&  ($model->invoice_status_id != INVOICE_STATUS_VOIDED) && ($model->invoice_status_id != INVOICE_STATUS_PAID);
                },
            ],
        ];
    }

    private function getStatusLabel($model)
    {
        $class = Invoice::calcStatusClass($model->invoice_status_id, $model->balance, $model->due_date_sql, $model->is_recurring);
        $label = Invoice::calcStatusLabel($model->invoice_status_name, $class, $this->entityType, $model->quote_invoice_id);

        return "<h4><div class=\"label label-{$class}\">$label</div></h4>";
    }

    public function bulkActions()
    {
        $actions = parent::bulkActions();

        if ($this->entityType == ENTITY_INVOICE || $this->entityType == ENTITY_QUOTE) {
            
            $actions[] = [
                'label' => mtrans($this->entityType, 'download_' . $this->entityType),
                'url' => 'javascript:submitForm_'.$this->entityType.'("download")',
            ];
            $actions[] = \DropdownButton::DIVIDER;
            $actions[] = [
                'label' => mtrans($this->entityType, 'email_' . $this->entityType),
                'url' => 'javascript:submitForm_'.$this->entityType.'("emailInvoice")',
            ];
            $actions[] = [
                'label' => mtrans($this->entityType, 'mark_sent'),
                'url' => 'javascript:submitForm_'.$this->entityType.'("markSent")',
            ];
        }

        if ($this->entityType == ENTITY_INVOICE) {
          //  $actions[] = [
                //'label' => mtrans($this->entityType, 'mark_paid'),
          //     // 'url' => 'javascript:submitForm_'.$this->entityType.'("markPaid")',
          //  ];
        }

        return $actions;
    }

    private function calcInterest($model){
        $balance = 0;
        $interest = 0;
        if ($model->partial > 0) {
            $balance = $model->partial;
        } else {
            $balance =  $model->balance;
        }
        if ($model->interest == 1 && $balance > 0) {
            $invoice_date = new \DateTime($model->invoice_date);
            $due_date = new \DateTime($model->due_date_sql);
            $today_date = new \DateTime("now");
            $interval = $invoice_date->diff($today_date);
            $days = $interval->days;
            //var_dump($days,$interval);die();  
            if ($days > 30) {
                $interest = 0.1 * ($balance) / 100 * $days;
            }
        }
        
        return round($interest,2);
    }
}
