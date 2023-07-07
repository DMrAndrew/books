<?php

namespace App\classes;

use Cms\Classes\Controller;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomPaginator extends LengthAwarePaginator
{

    public string $handler = '';
    public string $scrollToContainer = '';

    /**
     * @param string $handler
     * @return CustomPaginator
     */
    public function setHandler(string $handler): static
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * @param string $scrollToContainer
     * @return CustomPaginator
     */
    public function setScrollToContainer(string $scrollToContainer): static
    {
        $this->scrollToContainer = $scrollToContainer;
        return $this;
    }

    public function linkCollectionCompact()
    {
        if (!$this->count()) {
            return $this->linkCollection();
        }

        $visibleLinksList = [
            $this->lastPage(),
            1,
            $this->currentPage(),
            $this->currentPage() + 1,
            $this->currentPage() - 1,
        ];
        $links = $this->linkCollection()->map(fn($i) => in_array($i['label'], $visibleLinksList) ? $i : null);

        $links = $links->filter(function ($value, $key) use ($links) {
            return $value || ($key !== 0 && ($links[$key + 1] ?? false));
        })->map(function ($value) {
            return $value ? array_merge($value, ['url' => $value['label']]) : ['url' => null, 'label' => '...', 'active' => false];
        });
        $links->prepend(['url' => $this->currentPage() - 1, 'label' => 'Назад', 'active' => false, 'disabled' => !!!($this->currentPage() > 1)]);

        $links->push(['url' => $this->currentPage() + 1, 'label' => 'Вперёд', 'active' => false, 'disabled' => !!!$this->hasMorePages()]);

        return $links;
    }

    public function render($view = null, $data = []): \Illuminate\Contracts\Support\Htmlable|string
    {

        $templatePath = $view ?? themes_path("/demo/partials/site/component_base_pagination.htm");


        if (!file_exists($templatePath) || !is_readable($templatePath)) {
            throw new \Exception($templatePath . ' no such file');
        }

        $markup = file_get_contents($templatePath);
        return (new Controller)->getTwig()
            ->createTemplate($markup)
            ->render(array_merge($data, ['paginator' => $this]));
    }

    public static function from(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator): static
    {
        return new static($paginator->getCollection(), $paginator->total(), $paginator->perPage(), $paginator->currentPage());
    }
}
