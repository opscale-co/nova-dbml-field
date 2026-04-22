<?php

declare(strict_types=1);

namespace Opscale\NovaDbmlField;

use Laravel\Nova\Fields\Field;

class NovaDbmlField extends Field
{
    public $component = 'nova-dbml-field';

    public $showOnCreation = false;

    public $showOnUpdate = false;
}
