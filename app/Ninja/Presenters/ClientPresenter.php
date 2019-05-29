<?php

namespace App\Ninja\Presenters;
use App\Models\Payment;
use App\Models\Invoice;
use Auth;
use Utils;
use DB;

class ClientPresenter extends EntityPresenter
{
    public function country()
    {
        return $this->entity->country ? $this->entity->country->name : '';
    }

    public function shipping_country()
    {
        return $this->entity->shipping_country ? $this->entity->shipping_country->name : '';
    }


    public function balance()
    {
        $client = $this->entity;
        $account = $client->account;

        return $account->formatMoney($client->balance, $client);
    }

    public function websiteLink()
    {
        $client = $this->entity;

        if (! $client->website) {
            return '';
        }

        $link = Utils::addHttp($client->website);

        return link_to($link, $client->website, ['target' => '_blank']);
    }

    public function paid_to_date()
    {
        $client = $this->entity;
        $account = $client->account;

        return $account->formatMoney($client->paid_to_date, $client);
    }

    public function admin_paid_to_date(){
        $client = $this->entity;
        //$account = $client->account;
        
        $paid = Payment::where("client_id",$client->id)->where("account_id",Auth::user()->account_id)
            ->whereIn("payment_status_id",[PAYMENT_STATUS_COMPLETED,PAYMENT_STATUS_PRE_COMPLETED])
            ->sum("amount");
        return $paid;
    }
    public function admin_balance(){
        $client = $this->entity;
        //$account = $client->account;
        $paid = Invoice::where("client_id",$client->id)
            ->where("invoice_status_id",">",1)
            ->where("invoice_type_id",1)
            ->where("account_id",Auth::user()->account_id)
            ->sum("balance");
        $interest_balance = Invoice::where("client_id",$client->id)
            ->where("invoice_status_id",">",1)
            ->where("invoice_type_id",1)
            ->where("interest",1)
            ->where("account_id",Auth::user()->account_id)
            ->whereRaw("DATEDIFF(now(),invoice_date) > 30")
            ->sum(DB::raw("0.1 * balance / 100 * DATEDIFF(now(),invoice_date)"));
            
        return $paid+round($interest_balance,2);  
        
    }
    public function client_total_invoices(){
        $client = $this->entity;
        //$account = $client->account;
        $paid = Invoice::where("client_id",$client->id)
            ->where("invoice_status_id",">",1)
            ->where("invoice_type_id",1)
            ->sum("amount");
          
        return $paid;
    }
    public function client_balance(){
        $client = $this->entity;
        //$account = $client->account;
        $paid = Invoice::where("client_id",$client->id)
            ->where("invoice_status_id",">",1)
            ->where("invoice_type_id",1)
            ->sum("balance");
        $interest_balance = Invoice::where("client_id",$client->id)
        ->where("invoice_status_id",">",1)
        ->where("invoice_type_id",1)
        ->where("interest",1)
        ->whereRaw("DATEDIFF(now(),invoice_date) > 30")
        ->sum(DB::raw("0.1 * balance / 100 * DATEDIFF(now(),invoice_date)"));
        return $paid+round($interest_balance,2);
    }
    public function client_paid_to_date(){
        $client = $this->entity;
        //$account = $client->account;
        $paid = Payment::where("client_id",$client->id)
            ->whereIn("payment_status_id",[PAYMENT_STATUS_COMPLETED,PAYMENT_STATUS_PRE_COMPLETED])
            ->sum("amount");    
        return $paid;
    }
    public function paymentTerms()
    {
        $client = $this->entity;

        if (! $client->payment_terms) {
            return '';
        }

        return sprintf('%s: %s %s', trans('texts.payment_terms'), trans('texts.payment_terms_net'), $client->defaultDaysDue());
    }

    public function address($addressType = ADDRESS_BILLING)
    {
        $str = '';
        $prefix = $addressType == ADDRESS_BILLING ? '' : 'shipping_';
        $client = $this->entity;

        if ($address1 = $client->{$prefix . 'address1'}) {
            $str .= e($address1) . '<br/>';
        }
        if ($address2 = $client->{$prefix . 'address2'}) {
            $str .= e($address2) . '<br/>';
        }
        if ($cityState = $this->getCityState($addressType)) {
            $str .= e($cityState) . '<br/>';
        }
        if ($country = $client->{$prefix . 'country'}) {
            $str .= e($country->name) . '<br/>';
        }

        if ($str) {
            $str = '<b>' . trans('texts.' . $addressType) . '</b><br/>' . $str;
        }

        return $str;
    }

    /**
     * @return string
     */
    public function getCityState($addressType = ADDRESS_BILLING)
    {
        $client = $this->entity;
        $prefix = $addressType == ADDRESS_BILLING ? '' : 'shipping_';
        $swap = $client->{$prefix . 'country'} && $client->{$prefix . 'country'}->swap_postal_code;

        $city = e($client->{$prefix . 'city'});
        $state = e($client->{$prefix . 'state'});
        $postalCode = e($client->{$prefix . 'post_code'});

        if ($city || $state || $postalCode) {
            return Utils::cityStateZip($city, $state, $postalCode, $swap);
        } else {
            return false;
        }
    }


    /**
     * @return string
     */
    public function taskRate()
    {
      if ($this->entity->task_rate) {
          return Utils::roundSignificant($this->entity->task_rate);
      } else {
          return '';
      }
    }


}
