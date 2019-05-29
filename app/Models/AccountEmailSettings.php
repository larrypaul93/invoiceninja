<?php

namespace App\Models;

use Eloquent;

/**
 * Class Account.
 */
class AccountEmailSettings extends Eloquent
{
    /**
     * @var array
     */
    protected $fillable = [
        'bcc_email',
        'reply_to_email',
        'email_subject_invoice',
        'email_subject_quote',
        'email_subject_payment',
        'email_template_invoice',
        'email_template_quote',
        'email_template_payment',
        'email_subject_reminder1',
        'email_subject_reminder2',
        'email_subject_reminder3',
        'email_template_reminder1',
        'email_template_reminder2',
        'email_template_reminder3',

        'email_template_admin_invoice',
        'email_template_admin_quote',
        'email_template_admin_payment',
        'email_template_admin_pre_auth',
        'email_template_pre_auth',
        'email_template_pre_completion',
        'email_template_admin_pre_completion',
        'email_template_client_invoice',
        'email_template_client_quote',
        'email_template_client_report',
        'late_fee1_amount',
        'late_fee1_percent',
        'late_fee2_amount',
        'late_fee2_percent',
        'late_fee3_amount',
        'late_fee3_percent',
    ];

    public static $templates = [
        TEMPLATE_INVOICE,
        TEMPLATE_QUOTE,
        //TEMPLATE_PARTIAL,
        TEMPLATE_PAYMENT,
        TEMPLATE_REMINDER1,
        TEMPLATE_REMINDER2,
        TEMPLATE_REMINDER3,
    ];

}
