<?php

namespace App\Policies;
use App\Models\User;

class InvoicePolicy extends EntityPolicy
{

    /**
     * @param User $user
     * @param $item
     *
     * @return bool
     */
     public static function view(User $user, $item)
     {
         if (! static::checkModuleEnabled($user, $item)) {
             return false;
         }
 
         return $user->hasPermission('view_all') ;
     }

     
 
}
