<?php

namespace Books\Collections\Components;

use Books\Collections\classes\CollectionEnum;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Library Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Library extends ComponentBase
{
    protected ?User $user;

    public function componentDetails()
    {
        return [
            'name' => 'Library Component',
            'description' => 'No description provided yet...',
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->user = Auth::getUser();
        if ($this->user) {
            $lib = $this->user->getLib();
            $collection = collect(CollectionEnum::cases())->map(function ($item) use ($lib) {
                return [
                    'label' => $item->label(),
                    'type' => $item,
                    'count' => count($lib[$item->value] ?? []),
                    'items' => $lib[$item->value] ?? [],
                ];
            });
            $this->page['library'] = $collection;
            $this->page['library_has_items'] = (bool) $collection->pluck('count')->sum();
        }
    }
}
