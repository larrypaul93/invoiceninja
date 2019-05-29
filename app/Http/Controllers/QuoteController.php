<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Models\Account;
use App\Models\Client;
use App\Models\Country;
use App\Models\Invitation;
use App\Models\Invoice;
use App\Models\InvoiceDesign;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\User;
use App\Ninja\Datatables\InvoiceDatatable;
use App\Ninja\Mailers\ContactMailer as Mailer;
use App\Ninja\Repositories\ClientRepository;
use App\Ninja\Repositories\InvoiceRepository;
use App\Services\InvoiceService;
use App\Models\ServiceReport;
use Modules\Templates\Models\Templates;
use Auth;
use Cache;
use Input;
use Redirect;
use Session;
use Utils;
use View;

class QuoteController extends BaseController
{
    protected $mailer;
    protected $invoiceRepo;
    protected $clientRepo;
    protected $invoiceService;
    protected $entityType = ENTITY_INVOICE;

    public function __construct(Mailer $mailer, InvoiceRepository $invoiceRepo, ClientRepository $clientRepo, InvoiceService $invoiceService)
    {
        // parent::__construct();

        $this->mailer = $mailer;
        $this->invoiceRepo = $invoiceRepo;
        $this->clientRepo = $clientRepo;
        $this->invoiceService = $invoiceService;
    }

    public function index()
    {
        $datatable = new InvoiceDatatable();
        $datatable->entityType = ENTITY_QUOTE;

        $data = [
          'title' => trans('texts.quotes'),
          'entityType' => ENTITY_QUOTE,
          'datatable' => $datatable,
        ];

        return response()->view('list_wrapper', $data);
    }

    public function getDatatable($clientPublicId = null)
    {
        $accountId = Auth::user()->account_id;
        $search = Input::get('sSearch');

        return $this->invoiceService->getDatatable($accountId, $clientPublicId, ENTITY_QUOTE, $search);
    }

    public function create(InvoiceRequest $request, $clientPublicId = 0)
    {
        if (! Utils::hasFeature(FEATURE_QUOTES)) {
            return Redirect::to('/invoices/create');
        }

        $account = Auth::user()->account;
        $clientId = null;
        if ($clientPublicId) {
            $clientId = Client::getPrivateId($clientPublicId);
        }
        $invoice = $account->createInvoice(ENTITY_QUOTE, $clientId);
        $invoice->public_id = 0;

        if($invoice->client->id){
            $invoice->load( 'account.country','client.mainAddress', 'client.contacts', 'client.country','client.reports');
            $invoice->client->load('contacts', 'country','mainAddress','reports');
            $Contacts = $invoice->client->getAssociation();
            //$_contacts = $client->contacts;

            // var_dump($client->id,$client->contacts->count(),$Contacts->count());
            $invoice->client->contactsNew = $invoice->client->contacts->merge($Contacts);
        }
        
        
        $invoice->user_quote_signature = Auth::user()->signature;
        $invoice->user_name = Auth::user()->getContact()->getName();
        $invoice->user_position = Auth::user()->getContact()->position;
        $invoice->invoice_design_id = 12;
        $data = [
            'entityType' => $invoice->getEntityType(),
            'invoice' => $invoice,
            'contacts' =>json_encode([]),
            'data' => Input::old('data'),
            'method' => 'POST',
            'url' => 'invoices',
            'title' => trans('texts.new_quote'),
        ];
        $data = array_merge($data, self::getViewModel());

        return View::make('invoices.edit', $data);
    }

    private static function getViewModel()
    {
        $account = Auth::user()->account;
        $clients = Client::lead()->with('contacts', 'country','mainAddress','reports')->has('contacts')->orderBy('name')->limit(10);
        if (! Auth::user()->hasPermission('view_all')) {
            $clients = $clients->where('clients.user_id', '=', Auth::user()->id);
        }

        $clients = $clients->get();
        foreach ($clients as $client){

            $Contacts = $client->getAssociation();
            //$_contacts = $client->contacts;

            // var_dump($client->id,$client->contacts->count(),$Contacts->count());
            $client->contactsNew = $client->contacts->merge($Contacts);
            // $client->contacts = $client->contactsNew;
            // var_dump($client->contactsNew->count());
            //echo "<br>";
        }
        $accountManger = User::orderBy("users.name","asc")->get(["users.id","users.name"]);
        
        return [
          'entityType' => ENTITY_QUOTE,
          'account' => $account,
          'products' => Product::scope()->with('default_tax_rate')->orderBy('product_key')->get(),
          'taxRateOptions' => $account->present()->taxRateOptions,
          'defaultTax' => $account->default_tax_rate,
          'countries' => Cache::get('countries'),
          'clients' => $clients,
          'taxRates' => TaxRate::scope()->orderBy('name')->get(),
          'templates' => Templates::scope()->get(["id","name"]),
          'currencies' => Cache::get('currencies'),
          'sizes' => Cache::get('sizes'),
          'showBreadcrumbs' => false,
          'paymentTerms' => Cache::get('paymentTerms'),
          'languages' => Cache::get('languages'),
          'industries' => Cache::get('industries'),
          'invoiceDesigns' => InvoiceDesign::getDesigns(),
          'invoiceFonts' => Cache::get('fonts'),
          'invoiceLabels' => Auth::user()->account->getInvoiceLabels(),
          'isRecurring' => false,
          'accountManger' => $accountManger,
          'expenses' => [],
        ];
    }

    public function bulk()
    {
        $action = Input::get('bulk_action') ?: Input::get('action');
        ;
        $ids = Input::get('bulk_public_id') ?: (Input::get('public_id') ?: Input::get('ids'));

        if ($action == 'convert') {
            $invoice = Invoice::with('invoice_items')->scope($ids)->firstOrFail();
            $clone = $this->invoiceService->convertQuote($invoice);

            Session::flash('message', trans('texts.converted_to_invoice'));

            return Redirect::to('invoices/'.$clone->public_id);
        }

        $count = $this->invoiceService->bulk($ids, $action);

        if ($count > 0) {
            if ($action == 'markSent') {
                $key = 'updated_quote';
            } elseif ($action == 'download') {
                $key = 'downloaded_quote';
            } else {
                $key = "{$action}d_quote";
            }
            $message = Utils::pluralize($key, $count);
            Session::flash('message', $message);
        }

        return $this->returnBulk(ENTITY_QUOTE, $action, $ids);
    }

    public function approve($invitationKey)
    {
        $invitation = Invitation::with('invoice.invoice_items', 'invoice.invitations')->where('invitation_key', '=', $invitationKey)->firstOrFail();
        $invoice = $invitation->invoice;

        if ($invoice->due_date) {
            $carbonDueDate = \Carbon::parse($invoice->due_date);
            if (! $carbonDueDate->isToday() && ! $carbonDueDate->isFuture()) {
                return redirect("view/{$invitationKey}")->withError(trans('texts.quote_has_expired'));
            }
        }
        
        $invitationKey = $this->invoiceService->approveQuote($invoice, $invitation);
        Session::flash('message', trans('texts.quote_is_approved'));

        return Redirect::to("view/{$invitationKey}");
    }
}
