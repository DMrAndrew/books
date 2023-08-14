<?php namespace Books\Breadcrumbs\Models;

use Cms\Classes\Page;
use Model;

/**
 * Settings Model
 */
class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
    public $settingsCode = 'books_breadcrumbs_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';

    /**
     * @return array
     */
    public function getHomepageOptions(): array
    {
        return Page::all()->lists('title', 'id');
    }

    /**
     * @return array
     */
    public function getCatalogOptions(): array
    {
        return Page::all()->lists('title', 'id');
    }
    /**
     * @return array
     */
    public function getLcOptions(): array
    {
        return Page::all()->lists('title', 'id');
    }

    /**
     * @return array
     */
    public function getCommercialCabinetOptions(): array
    {
        return Page::all()->lists('title', 'id');
    }
}
