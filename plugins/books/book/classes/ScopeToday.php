<?php

namespace Books\Book\Classes;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class ScopeToday implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        return $builder->whereDate('created_at', today());
    }

    public function extend(Builder $builder)
    {
        $builder->macro('withoutTodayScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

}
