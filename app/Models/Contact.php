<?php

namespace App\Models;

use Utils;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use App\Models\LookupContact;
use Illuminate\Notifications\Notifiable;

/**
 * Class Contact.
 */
class Contact extends EntityModel implements AuthenticatableContract, CanResetPasswordContract
{
    use SoftDeletes, Authenticatable, CanResetPassword, Notifiable;

    protected $guard = 'client';

    /**
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * @return mixed
     */
    public function getEntityType()
    {
        return ENTITY_CONTACT;
    }

    /**
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'client_id',
        'user_id',
        'username',
        'position',
        'type',
        'status',
        'phone_business',
        'phone_home',
        'phone_main',
        'phone_fax',
        'phone_toll_free',
        'phone_cell',
        'webmail_email',
        'webmail_password',
        'hear_about_us',
        'website',
        'personal',
        'contact_image',
        'avatar',
        'email',
        'phone',
        'send_invoice',
        'signature',
        'custom_value1',
        'custom_value2',
        
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'confirmation_code',
    ];

    public function scopeScope($query, $publicId = false, $accountId = false)
    {
        if (! $accountId) {
            $accountId = Auth::user()->account_id;
        }

       // $query->where($this->getTable() .'.account_id', '=', $accountId);

        if ($publicId) {
            if (is_array($publicId)) {
                $query->whereIn('public_id', $publicId);
            } else {
                $query->wherePublicId($publicId);
            }
        }

        /* if (Auth::check() && ! Auth::user()->hasPermission('view_all') && method_exists($this, 'getEntityType') && $this->getEntityType() != ENTITY_TAX_RATE) {
            $query->where(Utils::pluralizeEntityType($this->getEntityType()) . '.user_id', '=', Auth::user()->id);
        } */

        return $query;
    }


    /**
     * @var string
     */
    public static $fieldFirstName = 'first_name';

    public function getRoute()
    {
        return "/contacts/{$this->public_id}";
    }
    /**
     * @var string
     */
    public static $fieldLastName = 'last_name';

    /**
     * @var string
     */
    public static $fieldEmail = 'email';

    /**
     * @var string
     */
    public static $fieldPhone = 'phone';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

    /**
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }

    /**
     * @return mixed
     */
    public function client()
    {
        return $this->belongsTo('App\Models\Client')->withTrashed();
    }

    public function addresses(){
        return $this->hasMany("App\Models\Address","entity_id")->where("address_type","contact");
    }

    public function billingAddress(){
        return $this->hasOne('App\Models\Address','entity_id',"id")
        ->where("address_type","contact")->where("type","Billing");
        
    }
    /**
     * @return mixed
     */
    public function getPersonType()
    {
        return PERSON_CONTACT;
    }

    /**
     * @return mixed|string
     */
    public function getName()
    {
        return $this->getDisplayName();
    }

    /**
     * @return mixed|string
     */
    public function getDisplayName()
    {
        if ($this->getFullName()) {
            return $this->getFullName();
        } else {
            return $this->email;
        }
    }

    /**
     * @param $contact_key
     *
     * @return mixed
     */
    public function getContactKeyAttribute($contact_key)
    {
        if (empty($contact_key) && $this->id) {
            $this->contact_key = $contact_key = strtolower(str_random(RANDOM_KEY_LENGTH));
            static::where('id', $this->id)->update(['contact_key' => $contact_key]);
        }

        return $contact_key;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        if ($this->first_name || $this->last_name) {
            return trim($this->first_name.' '.$this->last_name);
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getLinkAttribute()
    {
        if (! $this->account) {
            $this->load('account');
        }

        $account = $this->account;
        $url = trim(SITE_URL, '/');

        if ($account->hasFeature(FEATURE_CUSTOM_URL)) {
            if (Utils::isNinjaProd() && ! Utils::isReseller()) {
                $url = $account->present()->clientPortalLink();
            }

            if ($this->account->subdomain) {
                $url = Utils::replaceSubdomain($url, $account->subdomain);
            }
        }

        return "{$url}/client/dashboard/{$this->contact_key}";
    }

    public function sendPasswordResetNotification($token)
    {
        //$this->notify(new ResetPasswordNotification($token));
        app('App\Ninja\Mailers\ContactMailer')->sendPasswordReset($this, $token);
    }
}

Contact::creating(function ($contact)
{
    LookupContact::createNew($contact->account->account_key, [
        'contact_key' => $contact->contact_key,
    ]);
});

Contact::deleted(function ($contact)
{
    if ($contact->forceDeleting) {
        LookupContact::deleteWhere([
            'contact_key' => $contact->contact_key,
        ]);
    }
});