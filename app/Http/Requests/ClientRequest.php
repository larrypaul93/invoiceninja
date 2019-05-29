<?php

namespace App\Http\Requests;
use App\Libraries\HistoryUtils;

class ClientRequest extends EntityRequest
{
    protected $entityType = ENTITY_CLIENT;

    public function entity()
    {
        $client = parent::entity();
        
        // eager load the contacts
        if ($client && ! $client->relationLoaded('contacts')) {
            $client->load('contacts');
        }
         
        return $client;
    }

    public function authorize()
    {
        if ($this->entity()) {
            if ($this->user()->can('view', $this->entity()) || true) {
                HistoryUtils::trackViewed($this->entity());

                return true;
            }
        } else {
            return $this->user()->can('create', $this->entityType);
        }
    }
}
