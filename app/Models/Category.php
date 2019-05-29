<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laracasts\Presenter\PresentableTrait;

class Category extends EntityModel
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
    protected $presenter = 'App\Ninja\Presenters\CategoryPresenter';


    protected $table = 'categories';

    protected  $fillable = [
        'name',
        'parent_id'
        ];
    /**
     * @return mixed
     */
    public function getEntityType()
    {
        return ENTITY_CATEGORY;
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }

    public function parent(){
        return $this->belongsTo('App\Models\Category')->withTrashed();
    }

}
