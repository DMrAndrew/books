<?php

namespace Books\Breadcrumbs\Classes;

use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Books\Breadcrumbs\Exceptions\InvalidBreadcrumbException;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;

class BreadcrumbsManager
{
    use Macroable;

    /**
     * @var BreadcrumbsGenerator
     */
    protected $generator;

    /**
     * @var array
     */
    protected $callbacks = [];
    /**
     * @var array
     */
    protected $before = [];
    /**
     * @var array
     */
    protected $after = [];

    /**
     * BreadcrumbsManager constructor.
     * @param BreadcrumbsGenerator $generator
     */
    public function __construct(BreadcrumbsGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param callable $callback
     */
    public function before(callable $callback): void
    {
        $this->before[] = $callback;
    }

    /**
     * @param callable $callback
     */
    public function after(callable $callback): void
    {
        $this->after[] = $callback;
    }

    /**
     * @param string $name
     * @param callable $callback
     * @throws DuplicateBreadcrumbException
     */
    public function register(string $name, callable $callback): void
    {
        if (isset($this->callbacks[$name])) {
            throw new DuplicateBreadcrumbException($name);
        }

        $this->callbacks[$name] = $callback;
    }

    /**
     * @param string $name
     * @param mixed ...$params
     * @return Collection
     */
    public function generate(string $name, ...$params): Collection
    {
        try {
            return $this->generator->generate($this->callbacks, $this->before, $this->after, $name, $params);
        } catch (InvalidBreadcrumbException $e) {
            return new Collection;
        }
    }
}
