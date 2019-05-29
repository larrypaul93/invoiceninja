<?php

namespace App\Ninja\Mailers;

use App\Events\InvoiceWasEmailed;
use App\Events\QuoteWasEmailed;
use App\Models\Invitation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ClientPortalEmail;
use App\Services\TemplateService;
use Modules\Templates\Models\Templates;
use Event;
use Utils;

class ContactMailer extends Mailer
{
    /**
     * @var TemplateService
     */
    protected $templateService;

    /**
     * ContactMailer constructor.
     *
     * @param TemplateService $templateService
     */
    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * @param Invoice $invoice
     * @param bool    $reminder
     * @param bool    $pdfString
     *
     * @return bool|null|string
     */
    public function sendInvoice(Invoice $invoice, $reminder = false, $template = false)
    {
        if ($invoice->is_recurring) {
            return false;
        }

        $invoice->load('invitations', 'client.language', 'account');
        $entityType = $invoice->getEntityType();

        $client = $invoice->client;
        $account = $invoice->account;

        $response = null;

        if ($client->trashed()) {
            return trans('texts.email_error_inactive_client');
        } elseif ($invoice->trashed()) {
            return trans('texts.email_error_inactive_invoice');
        }

        $account->loadLocalizationSettings($client);
        if(empty($template['body'])){
            if($reminder && !is_numeric($reminder)){
               $emailTemplate =  $account->getEmailTemplate($reminder ?: $entityType);

            }
            elseif($reminder) {
                $_template = $account->getEmailTemplateById($reminder);
                 $emailTemplate = $_template->content;

            }
        }
        else
            $emailTemplate = $template['body'];
            
        if(empty($template['subject'])){
            if($reminder && !is_numeric($reminder)){
               $emailSubject =  $account->getEmailSubject($reminder ?: $entityType);

            }
            elseif($reminder) {
                $_template = $account->getEmailTemplateById($reminder);
                 $emailSubject = $_template->subject;
                 
            }
        }
        else 
            $emailSubject =  $template['subject'];
        //$emailTemplate = !empty($template['body']) ? $template['body'] : $account->getEmailTemplate($reminder ?: $entityType);
        //$emailSubject = !empty($template['subject']) ? $template['subject'] : $account->getEmailSubject($reminder ?: $entityType);

        $sent = false;
        $pdfString = false;

        if ($invoice->attach_pdf != 0) {
            $pdfString = $invoice->getPDFString();
        }

        $documentStrings = [];
        if ($account->document_email_attachment && $invoice->hasDocuments()) {
            
            $documents = $invoice->allDocuments();
            $documents = $documents->sortBy('size');

            $size = 0;
            $maxSize = MAX_EMAIL_DOCUMENTS_SIZE * 1000;
            foreach ($documents as $document) {
                $size += $document->size;
                if ($size > $maxSize) {
                    break;
                }

                $documentStrings[] = [
                    'name' => $document->name,
                    'data' => $document->getRaw(),
                ];
            }
        }

        $isFirst = true;
        foreach ($invoice->invitations as $invitation) {
            $response = $this->sendInvitation($invitation, $invoice, $emailTemplate, $emailSubject, $pdfString, $documentStrings, $reminder, $isFirst);
            $isFirst = false;
            if ($response === true) {
                $sent = true;
            }
        }

        $account->loadLocalizationSettings();

        if ($sent === true) {
            if ($invoice->isType(INVOICE_TYPE_QUOTE)) {
                event(new QuoteWasEmailed($invoice, $reminder));
            } else {
                event(new InvoiceWasEmailed($invoice, $reminder));
            }
        }

        return $response;
    }

    /**
     * @param Invitation $invitation
     * @param Invoice    $invoice
     * @param $body
     * @param $subject
     * @param $pdfString
     * @param $documentStrings
     * @param mixed $reminder
     *
     * @throws \Laracasts\Presenter\Exceptions\PresenterException
     *
     * @return bool|string
     */
    private function sendInvitation(
        Invitation $invitation,
        Invoice $invoice,
        $body,
        $subject,
        $pdfString,
        $documentStrings,
        $reminder,
        $isFirst
    ) {
        $client = $invoice->client;
        $account = $invoice->account;
        $user = $invitation->user;

        if ($invitation->user->trashed()) {
            $user = $account->users()->orderBy('id')->first();
        }

        if (! $user->email || ! $user->registered) {
            return trans('texts.email_error_user_unregistered');
        } elseif (! $user->confirmed) {
            return trans('texts.email_error_user_unconfirmed');
        } elseif (! $invitation->contact->email) {
            return trans('texts.email_error_invalid_contact_email');
        } elseif ($invitation->contact->trashed()) {
            return trans('texts.email_error_inactive_contact');
        }

        $variables = [
            'account' => $account,
            'client' => $client,
            'invitation' => $invitation,
            'amount' => $invoice->getRequestedAmount(),
        ];

        // Let the client know they'll be billed later
        if ($client->autoBillLater()) {
            $variables['autobill'] = $invoice->present()->autoBillEmailMessage();
        }

        if (empty($invitation->contact->password) && $account->isClientPortalPasswordEnabled() && $account->send_portal_password) {
            // The contact needs a password
            $variables['password'] = $password = $this->generatePassword();
            $invitation->contact->password = bcrypt($password);
            $invitation->contact->save();
        }
        //$_template = Templates::where("id",$reminder)->first();
        //if($_template) $reminder = $_template->name;
        $_template = false;
        if($reminder && is_numeric($reminder)){
            $_template = $account->getEmailTemplateById($reminder);
            $reminder = $_template->name;

        }
        $data = [
            'body' => $this->templateService->processVariables($body, $variables),
            'link' => $invitation->getLink(),
            'entityType' => $invoice->getEntityType(),
            'invoiceId' => $invoice->id,
            'invitation' => $invitation,
            'account' => $account,
            'client' => $client,
            'invoice' => $invoice,
            'documents' => $documentStrings,
            'notes' => $reminder,
            'bccEmail' => $isFirst ? $account->getBccEmail() : false,
            'ccEmail'  => !empty($invoice->cc_email)?explode(",",$invoice->cc_email):false,
            'fromEmail' => $account->getFromEmail(),
        ];

        if ($invoice->attach_pdf != 0) {
            $data['pdfString'] = $pdfString;
            $data['pdfFileName'] = $invoice->getFileName();
        }

        $subject = $this->templateService->processVariables($subject, $variables);
        $fromEmail = $account->getReplyToEmail() ?: $user->email;
        if($_template) $view = $_template->view;
        else $view = $account->getTemplateView($reminder);

        $response = $this->sendTo($invitation->contact->email, $fromEmail, $account->getDisplayName(), $subject, $view, $data);

        if ($response === true) {
            return true;
        } else {
            return $response;
        }
    }


    public function sendClientInvoiceEmail($contact,$invoice,$options){
        
        $random = md5(uniqid());
        ClientPortalEmail::create([
            "type" => $options['type'],
            "email_key" => $random,
            "contact_id" => $contact->id,
            "entity_id" => $invoice->id,
            
        ]);
        
        $variables = [
            
            '$message'=> $options['message'],
            '$receiverName'=>$options['name'],
            '$senderName'=>$contact->getDisplayName(),
            '$viewLink' => url($options['type']."/".$random."/view")
        ];
        $_data = [
            'account' => $contact->account,
            'client' => $contact->client,
            'amount' => $invoice->getRequestedAmount(),
            'contact' => $contact,
            'invoice'   =>$invoice
        ] ;

        
        //$_template = Templates::where("id",$reminder)->first();
        //if($_template) $reminder = $_template->name;
        $_template = false;
        
        $_template = $contact->account->getEmailTemplateObj("client_".$options['template']);
        $reminder = $_template->name;

        $data = [
            'body' => $this->templateService->processClientInvoiceVariables($_template->content, $_data,$variables),
            'link' => url($options['type']."/".$random."/view"),
            'entityType' => $invoice->getEntityType(),
            'invoiceId' => $invoice->id,
            'account' => $contact->account,
            'client' => $contact->client,
            'invoice' => $invoice,
            'notes' => $reminder,
            'fromEmail' => $contact->email,
        ];

        

        $subject = $this->templateService->processClientInvoiceVariables($_template->subject, $_data,$variables);
        $fromEmail = $contact->email;
        $view = $_template->view;
        

        $response = $this->sendTo($options['email'], $fromEmail, $contact->account->getDisplayName(), $subject, $view, $data);

        if ($response === true) {
            return true;
        } else {
            return $response;
        }
    }


    public function sendClientReportEmail($contact,$options){
        
        $random = md5(uniqid());
        ClientPortalEmail::create([
            "type" => $options['type'],
            "email_key" => $random,
            "contact_id" => $contact->id,
            "entity_id" => $options['id'],
            "data" => $options['id'],
            
        ]);
        
        $variables = [
            
            '$message'=> $options['message'],
            '$receiverName'=>$options['name'],
            '$senderName'=>$contact->getDisplayName(),
            '$viewLink' => url($options['type']."/".$random."/view")
        ];
        $_data = [
            'account' => $contact->account,
            'client' => $contact->client,
            'amount' => 0,
            'contact' => $contact,
            'message' => $options['message'],
            'invoice' => new Invoice()
           
        ] ;

        
        //$_template = Templates::where("id",$reminder)->first();
        //if($_template) $reminder = $_template->name;
        $_template = false;
        
        $_template = $contact->account->getEmailTemplateObj("client_".$options['template']);
        $reminder = $_template->name;

        $data = [
            'body' => $this->templateService->processClientVariables($_template->content, $_data,$variables),
            'link' => url($options['type']."/".$random."/view"),
            'entityType' => "report",
            //'invoiceId' => $invoice->id,
            'account' => $contact->account,
            'client' => $contact->client,
            'notes' => $reminder,
            'fromEmail' => $contact->email,
        ];

        

        $subject = $this->templateService->processClientVariables($_template->subject, $_data,$variables);
        $fromEmail = $contact->email;
        $view = $_template->view;
        

        $response = $this->sendTo($options['email'], $fromEmail, $contact->account->getDisplayName(), $subject, $view, $data);

        if ($response === true) {
            return true;
        } else {
            return $response;
        }
    }
    /**
     * @param int $length
     *
     * @return string
     */
    protected function generatePassword($length = 9)
    {
        $sets = [
            'abcdefghjkmnpqrstuvwxyz',
            'ABCDEFGHJKMNPQRSTUVWXYZ',
            '23456789',
        ];
        $all = '';
        $password = '';
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }
        $all = str_split($all);
        for ($i = 0; $i < $length - count($sets); $i++) {
            $password .= $all[array_rand($all)];
        }
        $password = str_shuffle($password);

        return $password;
    }

    /**
     * @param Payment $payment
     */
     public function sendPaymentConfirmation(Payment $payment, $refunded = 0)
    {
        $account = $payment->account;
        $client = $payment->client;

        $account->loadLocalizationSettings($client);

        $invoice = $payment->invoice;
        $accountName = $account->getDisplayName();
        $emailTemplate = $account->getEmailTemplate(ENTITY_PAYMENT);
        $emailSubject = $invoice->account->getEmailSubject(ENTITY_PAYMENT);

        if ($refunded > 0) {
            $emailSubject = trans('texts.refund_subject');
            $emailTemplate = trans('texts.refund_body', [
                'amount' => $account->formatMoney($refunded, $client),
                'invoice_number' => $invoice->invoice_number,
            ]);
        } else {
            $emailSubject = $invoice->account->getEmailSubject(ENTITY_PAYMENT);
            $emailTemplate = $account->getEmailTemplate(ENTITY_PAYMENT);
        }

        if ($payment->invitation) {
            $user = $payment->invitation->user;
            $contact = $payment->contact;
            $invitation = $payment->invitation;
        } else {
            $user = $payment->user;
            $invitation = $payment->invoice->invitations[0];
            $contact = $invitation->contact;
            
        }

        $variables = [
            'account' => $account,
            'client' => $client,
            'invitation' => $invitation,
            'amount' => $payment->amount,
        ];

        $data = [
            'body' => $this->templateService->processVariables($emailTemplate, $variables),
            'link' => $invitation->getLink(),
            'invoice' => $invoice,
            'client' => $client,
            'account' => $account,
            'payment' => $payment,
            'entityType' => ENTITY_INVOICE,
            'bccEmail' => $account->getBccEmail(),
            'ccEmail'  => !empty($invoice->cc_email)?explode(",",$invoice->cc_email):false,
            'fromEmail' => $account->getFromEmail(),
            'isRefund' => $refunded > 0,
        ];

        /*if ($account->attachPDF()) {
            $data['pdfString'] = $invoice->getPDFString();
            $data['pdfFileName'] = $invoice->getFileName();
        }*/

        $subject = $this->templateService->processVariables($emailSubject, $variables);
        $data['invoice_id'] = $payment->invoice->id;

        $view = $account->getTemplateView('payment_confirmation');
        $fromEmail = $account->getReplyToEmail() ?: $user->email;

        if ($contact->email) {
            $this->sendTo($contact->email, $fromEmail, $accountName, $subject, $view, $data);
        }

        $account->loadLocalizationSettings();
    }

    /**
     * @param $name
     * @param $email
     * @param $amount
     * @param $license
     * @param $productId
     */
    public function sendLicensePaymentConfirmation($name, $email, $amount, $license, $productId)
    {
        $view = 'license_confirmation';
        $subject = trans('texts.payment_subject');

        if ($productId == PRODUCT_ONE_CLICK_INSTALL) {
            $license = "Softaculous install license: $license";
        } elseif ($productId == PRODUCT_INVOICE_DESIGNS) {
            $license = "Invoice designs license: $license";
        } elseif ($productId == PRODUCT_WHITE_LABEL) {
            $license = "White label license: $license";
        }

        $data = [
            'client' => $name,
            'amount' => Utils::formatMoney($amount, DEFAULT_CURRENCY, DEFAULT_COUNTRY),
            'license' => $license,
        ];

        $this->sendTo($email, CONTACT_EMAIL, CONTACT_NAME, $subject, $view, $data);
    }

    public function sendPasswordReset($contact, $token)
    {
        if (! $contact->email) {
            return;
        }

        $subject = trans('texts.your_password_reset_link');
        $view = 'client_password';
        $data = [
            'token' => $token,
        ];

        $this->sendTo($contact->email, CONTACT_EMAIL, CONTACT_NAME, $subject, $view, $data);
    }
}
