<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPortalEmail extends Model
{
    protected $fillable = ["email_key","contact_id","entity_id","type","data"];

    public function contact(){
        return $this->belongsTo("App\Models\Contact");
    }

    public function invoice(){
        return $this->belongsTo("App\Models\Invoice","entity_id");
    }
    
}
