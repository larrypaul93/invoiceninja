<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laracasts\Presenter\PresentableTrait;
use Auth;
/**
 * Class Product.
 */
class Product extends EntityModel
{
    use PresentableTrait;
    use SoftDeletes;
    /**
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * @var string
     */
    protected $presenter = 'App\Ninja\Presenters\ProductPresenter';

    /**
     * @var array
     */
    protected $fillable = [
        'product_key',
        'notes',
        'cost',
        'qty',
        'default_tax_rate_id',
        'tax_name1',
        'tax_rate1',
        'tax_name2',
        'tax_rate2',
        'custom_value1',
        'custom_value2',
        'category_id',
        'sub_category_id',
        'status',
        'upc',
        'part_no',
        'sku',
        'purchase_price',
        'supplier_id',
        'on_hand'
    ];

    /**
     * @return array
     */
    public static function getImportColumns()
    {
        return [
            'product_key',
            'notes',
            'cost',
            'qty',
            'category_id',
            'sub_category_id',
            'status',
            'upc',
            'part_no',
            'sku',
            'default_tax_rate_id'
        ];
    }

    /**
     * @return array
     */
    public static function getImportMap()
    {
        return [
            'product|item' => 'product_key',
            'notes|description|details' => 'notes',
            'cost|amount|price' => 'cost',
            'sku' => 'sku',
            'part no|part_no' => 'part_no',
            'upc' => 'upc',
            'status' => 'status',
            "category" => "category_id",


        ];
    }

    /**
     * @return mixed
     */
    public function getEntityType()
    {
        return ENTITY_PRODUCT;
    }

    public function scopeScope($query, $publicId = false, $accountId = false)
    {
        if (! $accountId) {
            $accountId = Auth::user()->account_id;
        }

        $query->where($this->getTable() .'.account_id', '=', $accountId);

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
     * @param $key
     *
     * @return mixed
     */
    public static function findProductByKey($key)
    {
        return self::scope()->where('product_key', '=', $key)->first();
    }

    /**
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function default_tax_rate()
    {
        return $this->belongsTo('App\Models\TaxRate');
    }

    public function category(){
        return $this->belongsTo('App\Models\Category')->withTrashed();
    }
}
