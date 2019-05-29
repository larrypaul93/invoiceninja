<?php
/**
 * Model generated using LaraAdmin
 * Help: http://laraadmin.com
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;
class Address extends Model
{
    use SoftDeletes;
    protected $fillable = ['address_1','address_2','country','state','city','zip','type',"entity_id","address_type"];
    protected $table = 'addresses';

    protected $hidden = [

    ];

    protected $guarded = [];

    protected $dates = ['deleted_at'];

    private static $us_states = array(
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AS' => 'American Samoa',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'AE' => 'Armed Forces - Europe',
        'AP' => 'Armed Forces - Pacific',
        'AA' => 'Armed Forces - USA/Canada',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'DC' => 'District of Columbia',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'GU' => 'Guam',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'PR' => 'Puerto Rico',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VI' => 'Virgin Islands',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming'
    );

    private static $canadian_provinces = array(
        'AB' => 'Alberta',
        'BC' => 'British Columbia',
        'MB' => 'Manitoba',
        'NB' => 'New Brunswick',
        'NF' => 'Newfoundland and Labrador',
        'NT' => 'Northwest Territories',
        'NS' => 'Nova Scotia',
        'NU' => 'Nunavut',
        'ON' => 'Ontario',
        'PE' => 'Prince Edward Island',
        'QC' => 'Quebec',
        'SK' => 'Saskatchewan',
        'YT' => 'Yukon Territory'
    );

    public static function autoCorrect($addresses)
    {
        $_addresses = [];
        foreach ($addresses as $address) {
            $country = $address['country'];
            $state = $address['state'];

            $_country = Country::where("iso_3166_2", $country)->first();
            if ($_country) {
                $address['country'] = $_country->id;
            } else {
                $address['country'] = null;
                $address['null_dd_country'] = "true";
            }


            if(strtolower($country) == "us"){
                $state = self::$us_states[$state];
                $state = State::where("name",$state)->first();
                if($state){
                    $address['state'] = $state->id;

                }
                else {
                    $address['state'] = 1;
                    $address['state'] = null;
                    $address['null_dd_state'] = "true";
                }
            }
            elseif(strtolower($country) == "ca"){
                $state = self::$canadian_provinces[$state];
                $state = State::where("name",$state)->first();
                if($state){
                    $address['state'] = $state->id;

                }
                else {
                    $address['state'] = null;
                    $address['null_dd_state'] = "true";
                }
            }
            else {
                $address['state'] = null;
                $address['null_dd_state'] = "true";
            }

            $_addresses[] = $address;
        }
        return $_addresses;
    }

    public function getState(){
        $state = DB::table('states')->select("*")->where("id",$this->state)->first();
        if($state) return $state->name;
        return "";
    }
}
