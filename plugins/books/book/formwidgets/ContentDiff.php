<?php namespace Books\Book\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * ContentDiff Form Widget
 *
 * @link https://docs.octobercms.com/3.x/extend/forms/form-widgets.html
 */
class ContentDiff extends FormWidgetBase
{
    protected $defaultAlias = 'book_content_diff';

    public function init()
    {
    }

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('contentdiff');
    }

    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
    }

    public function loadAssets()
    {
        $this->addCss('css/contentdiff.css');
    }

    public function getSaveValue($value)
    {
        return $value;
    }
}
