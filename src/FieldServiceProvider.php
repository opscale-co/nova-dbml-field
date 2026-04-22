<?php

declare(strict_types=1);

namespace Opscale\NovaDbmlField;

use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class FieldServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'nova-dbml-field');

        Nova::serving(function (ServingNova $event): void {
            Nova::script('nova-dbml-field', __DIR__.'/../dist/js/field.js');
            Nova::style('nova-dbml-field', __DIR__.'/../dist/css/field.css');
        });
    }

    public function register(): void
    {
        //
    }
}
