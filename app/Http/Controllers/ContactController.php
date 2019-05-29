<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Http\Requests\CreateClientRequest;
use App\Http\Requests\CreateContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Requests\ContactRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Account;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\Role;
use App\Ninja\Datatables\ContactDatatable;
use App\Ninja\Repositories\ClientRepository;
use App\Services\ClientService;
use App\Services\ContactService;
use App\Models\ServiceReport;
use App\Models\Country;
use App\Models\Contact;
use App\Models\State;
use App\Models\User;
use App\Models\Timezone;
use Auth;
use Cache;
use Input;
use Redirect;
use Session;
use URL;
use Utils;
use View;
use Former;

class ContactController extends BaseController
{
    protected $clientService;
    protected $clientRepo;
    protected $contactService;
    protected $entityType = ENTITY_CLIENT;

    public function __construct(ClientRepository $clientRepo, ClientService $clientService,ContactService $contactService)
    {
        //parent::__construct();

        $this->clientRepo = $clientRepo;
        $this->clientService = $clientService;
        $this->contactService = $contactService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return View::make('list_wrapper', [
            'entityType' => ENTITY_CONTACT,
            'datatable' => new ContactDatatable(),
            'title' => trans('texts.clients'),
            'statuses' => Contact::getStatuses(),
        ]);
    }

    public function getDatatable()
    {
        $search = Input::get('sSearch');
        $userId = Auth::user()->filterId();

        return $this->contactService->getDatatable(false,$search,false);
    }

    public function getContactDatatable($clientPublicId){
        $search = Input::get('sSearch');
        $clientId = Client::getPrivateId($clientPublicId);

        return $this->contactService->getDatatable($clientId, $search );
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(CreateContactRequest $request)
    {
        $contact = $this->contactService->save($request->input());

        Session::flash('message', trans('texts.created_client'));

        return redirect("contacts");//->to();
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show(ClientRequest $request)
    {
        $client = $request->entity();
        $user = Auth::user();

        $actionLinks = [];
        if ($user->can('create', ENTITY_INVOICE)) {
            $actionLinks[] = ['label' => trans('texts.new_invoice'), 'url' => URL::to('/invoices/create/'.$client->public_id)];
        }
        if ($user->can('create', ENTITY_TASK)) {
            $actionLinks[] = ['label' => trans('texts.new_task'), 'url' => URL::to('/tasks/create/'.$client->public_id)];
        }
        if (Utils::hasFeature(FEATURE_QUOTES) && $user->can('create', ENTITY_QUOTE)) {
            $actionLinks[] = ['label' => trans('texts.new_quote'), 'url' => URL::to('/quotes/create/'.$client->public_id)];
        }

        if (! empty($actionLinks)) {
            $actionLinks[] = \DropdownButton::DIVIDER;
        }

        if ($user->can('create', ENTITY_PAYMENT)) {
            $actionLinks[] = ['label' => trans('texts.enter_payment'), 'url' => URL::to('/payments/create/'.$client->public_id)];
        }

        if ($user->can('create', ENTITY_CREDIT)) {
            $actionLinks[] = ['label' => trans('texts.enter_credit'), 'url' => URL::to('/credits/create/'.$client->public_id)];
        }

        if ($user->can('create', ENTITY_EXPENSE)) {
            $actionLinks[] = ['label' => trans('texts.enter_expense'), 'url' => URL::to('/expenses/create/0/'.$client->public_id)];
        }

        $token = $client->getGatewayToken();

        $data = [
            'actionLinks' => $actionLinks,
            'showBreadcrumbs' => false,
            'client' => $client,
            'credit' => $client->getTotalCredit(),
            'title' => trans('texts.view_client'),
            'hasRecurringInvoices' => Invoice::scope()->recurring()->withArchived()->whereClientId($client->id)->count() > 0,
            'hasQuotes' => Invoice::scope()->quotes()->withArchived()->whereClientId($client->id)->count() > 0,
            'hasTasks' => Task::scope()->withArchived()->whereClientId($client->id)->count() > 0,
            'gatewayLink' => $token ? $token->gatewayLink() : false,
            'gatewayName' => $token ? $token->gatewayName() : false,
        ];

        return View::make('clients.show', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create($client_id = false)
    {
        $client = null;
        
        $data = [
            'client' => $client,
            'contact' =>null,
            'method' => 'POST',
            'url' => 'contacts',
            'title' => trans('texts.new_contact'),
        ];
        if($client_id)
            Former::populateField("client_id",$client_id);
        $data = array_merge($data, self::getViewModel());

        return View::make('contacts.edit', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit(ContactRequest $request)
    {
        $contact = $request->entity();
        $contact->load(["client","addresses"]);
        $data = [
            'client' => $contact->client,
            'contact'=>$contact,
            'method' => 'PUT',
            'url' => 'contacts/'.$contact->public_id."",
            'title' => trans('texts.edit_contact'),
        ];

        $data = array_merge($data, self::getViewModel());

        if (Auth::user()->account->isNinjaAccount()) {
            if ($account = Account::whereId($client->public_id)->first()) {
                $data['planDetails'] = $account->getPlanDetails(false, false);
            }
        }

        return View::make('contacts.edit', $data);
    }

    private static function getViewModel()
    {
       $countries = Country::get();
        $countriesStates = [];
        foreach ($countries as $key => $country) {
            $countriesStates[$country->id] = $country->states()->get(["code","name"]);
        }
        $accountManger = User::join("role_user","users.id","=","role_user.user_id")->join("roles","roles.id","=","role_user.role_id")->whereIn("roles.name",["STAFF","SALES_REPRESENTATIVE","MANAGEMENT","SUPER_ADMIN"])->orderBy("users.name","asc")->get(["users.id","users.name"]);
        $clients = Client::scope()->get(["id","name"]);
        $roles = Role::get(["id","name"]);
       return [
            'data' => Input::old('data'),
            'account' => Auth::user()->account,
            'sizes' => Cache::get('sizes'),
            'currencies' => Cache::get('currencies'),
            "clients"   => $clients,
            'countriesStates' => $countriesStates,
            'countries' => $countries,
            'roles' => $roles,
            'timezones'=> Timezone::get(["id","name"]),
            "accountManger" => $accountManger,
            'customLabel1' => Auth::user()->account->custom_client_label1,
            'customLabel2' => Auth::user()->account->custom_client_label2,
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function update(UpdateContactRequest $request)
    {
        $contact = $this->contactService->save($request->input(), $request->entity());

        Session::flash('message', trans('texts.updated_client'));

        return redirect("contacts");//->to();
    }

    public function bulk()
    {
        $action = Input::get('action');
        $ids = Input::get('public_id') ? Input::get('public_id') : Input::get('ids');
        $count = $this->clientService->bulk($ids, $action);

        $message = Utils::pluralize($action.'d_client', $count);
        Session::flash('message', $message);

        return $this->returnBulk(ENTITY_CLIENT, $action, $ids);
    }

    public function statement()
    {
        $account = Auth::user()->account;
        $client = Client::scope(request()->client_id)->with('contacts')->firstOrFail();
        $invoice = $account->createInvoice(ENTITY_INVOICE);
        $invoice->client = $client;
        $invoice->date_format = $account->date_format ? $account->date_format->format_moment : 'MMM D, YYYY';
        $invoice->invoice_items = Invoice::scope()
            ->with(['client'])
            ->whereClientId($client->id)
            ->invoices()
            ->whereIsPublic(true)
            ->where('balance', '>', 0)
            ->get();

        $data = [
            'showBreadcrumbs' => false,
            'client' => $client,
            'invoice' => $invoice,
        ];

        return view('clients.statement', $data);
    }

    public function  getClientsJSON(){
        $q = strtolower(Input::get("q"));
        $clients = Client::lead()->where(function($query)use($q){
            $query->whereRaw('LOWER(name) like ?', array('%' . $q. '%'))
                ->orWhereRaw('LOWER(suffix) like ?', array('%' . $q . '%'))
                ->orWhereRaw('LOWER(legal_business_name) like ?', array('%' . $q . '%'));
        })->with('contacts', 'country','mainAddress','reports')->has('contacts')->orderBy('name')->limit(100);
        if (! Auth::user()->hasPermission('view_all')) {
            $clients = $clients->where('clients.user_id', '=', Auth::user()->id);
        }
        //echo $clients->toSql();
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
       return response()->json($clients);
    }
}
