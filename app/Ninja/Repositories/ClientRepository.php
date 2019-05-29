<?php

namespace App\Ninja\Repositories;

use App\Events\ClientWasCreated;
use App\Events\ClientWasUpdated;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Address;
use Auth;
use Cache;
use DB;

class ClientRepository extends BaseRepository
{
    public function getClassName()
    {
        return 'App\Models\Client';
    }

    public function all()
    {
        return Client::scope()
                ->with('user', 'contacts', 'country')
                ->withTrashed()
                ->where('is_deleted', '=', false)
                ->get();
    }

    public function find($filter = null, $userId = false)
    {
        $query = DB::table('clients')
                    ->leftJoin("addresses",function ($join) {
                        $join->on('clients.id', '=', 'addresses.entity_id')
                            ->where('addresses.type', '=', 'Main')
                            ->where('addresses.address_type','=','account');
                    })
                  //  ->leftJoin("states",'addresses.state','=','states.id')
                    //->where('clients.type', '=', 'Client')
                    ->where(function($query){
                        $query->where('clients.type', '=', 'Client')
                        ->orWhere('clients.type', '=', 'Partner')
                        ->orWhere('clients.type', '=', 'Management');
                    })
                    //->whereRaw('(clients.name != "" or contacts.first_name != "" or contacts.last_name != "" or contacts.email != "")') // filter out buy now invoices
                    ->select(
                        DB::raw('COALESCE(clients.currency_id) currency_id'),
                        DB::raw('COALESCE(clients.country_id) country_id'),
                        DB::raw('(select sum(balance) from invoices where invoice_status_id > 1 AND invoice_type_id = 1 AND invoices.client_id = clients.id and account_id = '.Auth::user()->account_id .') as balance'),
                        'clients.public_id',
                        'clients.id',
                        'clients.name as account',
                        'clients.suffix',
                       // 'clients.balance',
                        'clients.last_login',
                        'clients.created_at',
                        'clients.created_at as client_created_at',
                        'clients.work_phone',
                        'clients.deleted_at',
                        'clients.is_deleted',
                        'clients.user_id',
                        'clients.id_number',
                        'addresses.address_1 as address',
                        'addresses.city',
                        'addresses.zip',
                        'addresses.state'
                    )->distinct();

        $this->applyFilters($query, ENTITY_CLIENT);

        if ($filter) {
            $query->where(function ($query) use ($filter) {
                $query->where('clients.name', 'like', '%'.$filter.'%')
                      ->orWhere('clients.suffix', 'like', '%'.$filter.'%')
                      ->orWhere('clients.id_number', '=', $filter)
                      ->orWhere('addresses.address_1', 'like', '%'.$filter.'%')
                      ->orWhere('addresses.city', 'like', '%'.$filter.'%')
                      ->orWhere('addresses.zip', 'like', '%'.$filter.'%')
                      ->orWhere('addresses.state', 'like', '%'.$filter.'%');
            });
        }

        if ($userId) {
            $query->where('clients.user_id', '=', $userId);
        }

        return $query;
    }

    public function save($data, $client = null)
    {
        $publicId = isset($data['public_id']) ? $data['public_id'] : false;

        if ($client) {
            // do nothing
        } elseif (! $publicId || $publicId == '-1') {
            $client = Client::createNew();
        } else {
            $client = Client::scope($publicId)->with('contacts')->firstOrFail();
        }

        // auto-set the client id number
        if (Auth::check() && Auth::user()->account->client_number_counter && !$client->id_number && empty($data['id_number'])) {
            $data['id_number'] = Auth::user()->account->getNextNumber();
        }

        if ($client->is_deleted) {
            return $client;
        }

        // convert currency code to id
        if (isset($data['currency_code'])) {
            $currencyCode = strtolower($data['currency_code']);
            $currency = Cache::get('currencies')->filter(function ($item) use ($currencyCode) {
                return strtolower($item->code) == $currencyCode;
            })->first();
            if ($currency) {
                $data['currency_id'] = $currency->id;
            }
        }

        $client->fill($data);
        $client->save();

        
        
        if(isset($data['address'])){
            $addresses = $data['address'];
            foreach ($addresses as $key => $_address) {
                if($_address['id']){
                    $address = Address::where("id",$_address['id'])->first();
                    $address->fill($_address);
                    
                    $address->save();
                }
                else {
                   $address =  Address::create(["entity_id"=>$client->id,"address_type"=>"account"]);
                   $address->fill($_address);
                   //$address->address_type = "account";
                   //$address->entity_id = $client->id;
                   $address->save();
                }
                
            }
        }
        
        if ( ! isset($data['contact']) && ! isset($data['contacts'])) {
            return $client;
        }
        
        $first = true;
        $contacts = isset($data['contact']) ? [$data['contact']] : $data['contacts'];
        $contactIds = [];

        // If the primary is set ensure it's listed first
        usort($contacts, function ($left, $right) {
            if (isset($right['is_primary']) && isset($left['is_primary'])) {
                return $right['is_primary'] - $left['is_primary'];
            } else {
                return 0;
            }
        });

        foreach ($contacts as $contact) {
            $contact = $client->addContact($contact, $first);
            $contactIds[] = $contact->public_id;
            $first = false;
        }

        if (! $client->wasRecentlyCreated) {
            foreach ($client->contacts as $contact) {
                if (! in_array($contact->public_id, $contactIds)) {
                    $contact->delete();
                }
            }
        }

        if (! $publicId || $publicId == '-1') {
            event(new ClientWasCreated($client));
        } else {
            event(new ClientWasUpdated($client));
        }

        return $client;
    }

    
    public function findPhonetically($clientName)
    {
        $clientNameMeta = metaphone($clientName);

        $map = [];
        $max = SIMILAR_MIN_THRESHOLD;
        $clientId = 0;

        $clients = Client::scope()->get(['id', 'name', 'public_id']);

        foreach ($clients as $client) {
            $map[$client->id] = $client;

            if (! $client->name) {
                continue;
            }

            $similar = similar_text($clientNameMeta, metaphone($client->name), $percent);

            if ($percent > $max) {
                $clientId = $client->id;
                $max = $percent;
            }
        }

        $contacts = Contact::scope()->get(['client_id', 'first_name', 'last_name', 'public_id']);

        foreach ($contacts as $contact) {
            if (! $contact->getFullName() || ! isset($map[$contact->client_id])) {
                continue;
            }

            $similar = similar_text($clientNameMeta, metaphone($contact->getFullName()), $percent);

            if ($percent > $max) {
                $clientId = $contact->client_id;
                $max = $percent;
            }
        }

        return ($clientId && isset($map[$clientId])) ? $map[$clientId] : null;
    }

   
}
