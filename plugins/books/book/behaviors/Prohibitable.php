<?php

namespace Books\Book\Behaviors;

use Books\Book\Classes\Scopes\ProhibitedScope;
use Books\Book\Models\Prohibited;
use October\Rain\Database\Builder;
use October\Rain\Database\Model;
use October\Rain\Extension\ExtensionBase;
use RainLab\Location\Models\Country;
use RainLab\User\Facades\Auth;
use Schema;

class Prohibitable extends ExtensionBase
{

    protected string $class;

    public function __construct(protected Model $model)
    {
        $this->model->morphMany['prohibited'] = [Prohibited::class, 'name' => 'prohibitable'];
        $this->class = get_class($this->model);
    }

    public function scopeWithoutProhibited(Builder $builder)
    {
        return $builder->prohibited();
    }

    public function scopeProhibitedOnly(Builder $builder)
    {
        return $builder->prohibited(mode: 'only');
    }

    public function scopeProhibited(Builder $builder, string $mode = 'without')
    {

        if (Schema::hasTable((new Prohibited())->getTable())) {

            $user = Auth::getUser();
            $country = $user?->country ?? Country::getDefault();
            $prohibited_builder = fn() => Prohibited::query()->type($this->class);
            $column_id = (new $this->class)->getQualifiedKeyName();
            $method = match ($mode) {
                'only' => 'whereIn',
                default => 'whereNotIn'
            };

            return $builder->{$method}($column_id, $prohibited_builder()
                ->when($country, fn($b) => $b->exceptAtCountry($country))->ids());
        }
        return $builder;
    }


}
