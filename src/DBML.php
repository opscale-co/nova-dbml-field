<?php

declare(strict_types=1);

namespace Opscale\Fields;

use Illuminate\Http\UploadedFile;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Override;

class DBML extends Field
{
    public $component = 'nova-dbml-field';

    /**
     * Hidden from Index listings — a full Vue Flow diagram inside a table cell
     * adds no decision-making value. Detail view is the primary experience.
     *
     * @var (callable(NovaRequest, mixed):(bool))|bool
     */
    public $showOnIndex = false;

    /**
     * Accept either an UploadedFile (via the file-upload FormField) or a raw
     * DBML string (API callers). Both paths store the DBML text into the model
     * attribute — the attribute is always plain text, never a file path.
     *
     * The parent implementation reads request input and assigns it directly; we
     * intentionally replace it to branch on UploadedFile, so no parent:: call.
     */
    #[Override]
    protected function fillAttributeFromRequest(NovaRequest $request, string $requestAttribute, object $model, string $attribute): void
    {
        if (! $request->exists($requestAttribute)) {
            return;
        }

        $value = $request[$requestAttribute];

        if ($value instanceof UploadedFile) {
            $model->{$attribute} = (string) file_get_contents($value->getRealPath());

            return;
        }

        $model->{$attribute} = $value === null ? null : (string) $value;
    }
}
