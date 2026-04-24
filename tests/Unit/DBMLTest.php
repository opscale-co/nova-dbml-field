<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Laravel\Nova\Http\Requests\NovaRequest;
use Opscale\Fields\DBML;

it('registers the nova-dbml-field vue component', function (): void {
    $field = DBML::make('schema');

    expect($field->component)->toBe('nova-dbml-field');
});

it('fills the attribute with a raw DBML string from the request', function (): void {
    $field = DBML::make('dbml');
    $model = new class
    {
        public ?string $dbml = null;
    };

    $request = NovaRequest::create('/', 'POST', [
        'dbml' => "Table users {\n  id integer [pk]\n}",
    ]);

    invokeFill($field, $request, 'dbml', $model, 'dbml');

    expect($model->dbml)->toBe("Table users {\n  id integer [pk]\n}");
});

it('fills the attribute with the file contents when an UploadedFile is submitted', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'dbml');
    file_put_contents($path, "Table posts {\n  id integer [pk]\n}");

    $file = new UploadedFile($path, 'schema.dbml', 'text/plain', null, true);

    $field = DBML::make('dbml');
    $model = new class
    {
        public ?string $dbml = null;
    };

    $request = NovaRequest::create('/', 'POST', [], [], ['dbml' => $file]);

    invokeFill($field, $request, 'dbml', $model, 'dbml');

    expect($model->dbml)->toBe("Table posts {\n  id integer [pk]\n}");

    @unlink($path);
});

it('writes null when the submitted value is null', function (): void {
    $field = DBML::make('dbml');
    $model = new class
    {
        public ?string $dbml = 'previous';
    };

    $request = NovaRequest::create('/', 'POST', ['dbml' => null]);

    invokeFill($field, $request, 'dbml', $model, 'dbml');

    expect($model->dbml)->toBeNull();
});

it('leaves the attribute untouched when the request does not include it', function (): void {
    $field = DBML::make('dbml');
    $model = new class
    {
        public ?string $dbml = 'keep-me';
    };

    $request = NovaRequest::create('/', 'POST', []);

    invokeFill($field, $request, 'dbml', $model, 'dbml');

    expect($model->dbml)->toBe('keep-me');
});

function invokeFill(DBML $field, NovaRequest $request, string $requestAttr, object $model, string $attr): void
{
    $method = new ReflectionMethod($field, 'fillAttributeFromRequest');
    $method->invoke($field, $request, $requestAttr, $model, $attr);
}
