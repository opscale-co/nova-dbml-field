<?php

declare(strict_types=1);

namespace Workbench\App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Opscale\NovaDbmlField\NovaDbmlField;

class Schema extends Resource
{
    public static $model = \Workbench\App\Models\Schema::class;

    public static $title = 'name';

    public static $search = ['name'];

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Name')->rules('required', 'max:255'),
            NovaDbmlField::make('DBML', 'dbml')
                ->rules('required'),
        ];
    }
}
