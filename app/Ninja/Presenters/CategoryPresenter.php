<?php

namespace App\Ninja\Presenters;


class CategoryPresenter extends EntityPresenter
{
    public function user()
    {
        return $this->entity->user->getDisplayName();
    }




}
