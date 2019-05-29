<?php

namespace App\Ninja\Repositories;

use App\Models\Contact;
use App\Models\Client;
use App\Models\Invitation;
use App\Models\Association;
use App\Models\Address;
use Auth;
use DB;
use Request;
use Utils;
class ContactRepository extends BaseRepository
{
    public function all()
    {
        return Contact::scope()
                ->withTrashed()
                ->get();
    }
    
    public function save($data, $contact = false)
    {
        $publicId = isset($data['public_id']) ? $data['public_id'] : false;

        if ($contact) {
            // do nothing
        } elseif (! $publicId || $publicId == '-1') {
            $contact = Contact::createNew();
            $contact->send_invoice = true;
            $contact->client_id = $data['client_id'];
            $contact->is_primary = Contact::scope()->where('client_id', '=', $contact->client_id)->count() == 0;
            $contact->contact_key = strtolower(str_random(RANDOM_KEY_LENGTH));
        } else {
            $contact = Contact::scope($publicId)->firstOrFail();
        }

        $contact->fill($data);
        if(isset($data['password'])){
           $contact->password =  bcrypt($data['password']);
        }
        $contact->save();

        if(isset($data['address'])){
            $addresses = $data['address'];
            foreach ($addresses as $key => $_address) {
                if($_address['id']){
                    $address = Address::where("id",$_address['id'])->first();
                    $address->fill($_address);
                    
                    $address->save();
                }
                else {
                   $address =  Address::create(["entity_id"=>$contact->id,"address_type"=>"contact"]);
                   $address->fill($_address);
                   //$address->address_type = "account";
                   //$address->entity_id = $client->id;
                   $address->save();
                }
                
            }
        }

        return $contact;
    }

    public function find($filter = null, $userId = false)
    {
       

        
        $query = DB::table('contacts')
            ->leftJoin("addresses",function ($join) {
                $join->on('contacts.id', '=', 'addresses.entity_id')
                    ->where('addresses.type', '=', 'Main')
                    ->where('addresses.address_type','=','contact');
            })
            //->leftJoin("states",'addresses.state','=','states.code')
            //->where('clients.type', '=', 'Client')
            //->whereRaw('(clients.name != "" or contacts.first_name != "" or contacts.last_name != "" or contacts.email != "")') // filter out buy now invoices
            ->select(

                'contacts.public_id',
                'contacts.id',
                'contacts.first_name',
                'contacts.last_name',
                DB::raw('CONCAT(contacts.first_name," ",contacts.last_name) as name'),
                'contacts.email', 
                'contacts.phone_main',
                'contacts.phone_business',
                'contacts.phone_cell',
                'contacts.contact_key',
                'contacts.created_at',
                'contacts.created_at as contact_created_at',
                'contacts.deleted_at',
                'contacts.user_id',

                'addresses.address_1 as address',
                'addresses.city',
                'addresses.zip',
                'addresses.state'
            )->distinct('contacts.id');//->groupBy('contacts.id');

        $this->applyFilters($query, ENTITY_CONTACT);

        if ($filter && !empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query
                    ->whereRaw('CONCAT(contacts.first_name," ",contacts.last_name) like "%'.$filter.'%"')
                    //->orWhere('contacts.last_name', 'like', '%'.$filter.'%')
                    ->orWhere('contacts.email', 'like', '%'.$filter.'%')
                    ->orWhere('addresses.address_1', 'like', '%'.$filter.'%')
                    ->orWhere('addresses.city', 'like', '%'.$filter.'%')
                    ->orWhere('addresses.zip', 'like', '%'.$filter.'%')
                    ->orWhere('addresses.state', 'like', '%'.$filter.'%');
            });
        }

        if ($userId) {
            $query->where('contacts.user_id', '=', $userId);
        }

        return $query;
    }

    public function findByClientId($client_id,$filter = null){
        $query = $this->find($filter);
         $associations = Association::select('element_a as element_id')->where("element_b_type",'account')->where("element_b",$client_id)
                ->where('element_a_type','contact');
        $associations1 = Association::select('element_b as element_id')->where("element_a_type",'account')->where("element_a",$client_id)
            ->where('element_b_type','contact');
        $associations->union($associations1);
        //die($associations->toSql());
        $_associations = [];
        foreach ($associations->get() as $association){
            $_associations[] = $association->element_id;
        }
        $query->where(function($query) use($client_id,$_associations){
            $query->where("contacts.client_id",$client_id)
                ->orwhereIn("contacts.id",$_associations);
        });
       //echo $query->toSql(); die();
        return $query;
    }
}
