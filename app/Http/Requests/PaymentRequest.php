<?php

namespace App\Http\Requests;

class PaymentRequest extends EntityRequest
{
    protected $entityType = ENTITY_PAYMENT;

    public function entity()
    {
        $payment = parent::entity();

        // eager load the documents
        if ($payment && method_exists($payment, 'documents') && ! $payment->relationLoaded('documents')) {
            $payment->load('documents');
        }

        return $payment;
    }
}
