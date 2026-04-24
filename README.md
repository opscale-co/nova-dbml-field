## Support us

At Opscale, we’re passionate about contributing to the open-source community by providing solutions that help businesses scale efficiently. If you’ve found our tools helpful, here are a few ways you can show your support:

⭐ **Star this repository** to help others discover our work and be part of our growing community. Every star makes a difference!

💬 **Share your experience** by leaving a review on [Trustpilot](https://www.trustpilot.com/review/opscale.co) or sharing your thoughts on social media. Your feedback helps us improve and grow!

📧 **Send us feedback** on what we can improve at [feedback@opscale.co](mailto:feedback@opscale.co). We value your input to make our tools even better for everyone.

🙏 **Get involved** by actively contributing to our open-source repositories. Your participation benefits the entire community and helps push the boundaries of what’s possible.

💼 **Hire us** if you need custom dashboards, admin panels, internal tools or MVPs tailored to your business. With our expertise, we can help you systematize operations or enhance your existing product. Contact us at hire@opscale.co to discuss your project needs.

Thanks for helping Opscale continue to scale! 🚀



## Description

DBML is a great way to document a database schema, but inspecting one means jumping out to dbdiagram.io or another external tool every time. This package brings the diagram into your admin panel: upload a `.dbml` file from any Nova resource and the interactive ER diagram shows up right on the record. Compatible with Nova 5.

![Demo](https://raw.githubusercontent.com/opscale-co/nova-dbml-field/refs/heads/main/screenshots/nova-dbml-field.png)

## Installation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/opscale-co/nova-dbml-field.svg?style=flat-square)](https://packagist.org/packages/opscale-co/nova-dbml-field)

You can install the package in to a Laravel app that uses [Nova](https://nova.laravel.com) via composer:

```bash
composer require opscale-co/nova-dbml-field
```

The package will auto-register its service provider.

Back your field with a `longText` (or `text`) column on the owning model. The field stores the raw DBML as plain text — no disk configuration required.

```php
Schema::create('schemas', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->longText('dbml')->nullable();
    $table->timestamps();
});
```

## Usage

Add the `DBML` field to any Nova Resource that owns a DBML attribute:

```php
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Opscale\Fields\DBML;

class Schema extends Resource
{
    public static $model = \App\Models\Schema::class;

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Name')->rules('required', 'max:255'),
            DBML::make('Schema', 'dbml')->rules('required'),
        ];
    }
}
```

On **Create / Update** the field renders Nova’s native file dropzone — pick a `.dbml` file, the contents are parsed client-side with `@dbml/core` and only submitted if they parse cleanly. On **Detail / Lens** the field renders the full interactive diagram. It is intentionally **hidden from Index**.

### Field behavior

| View | Behavior |
|---|---|
| Create / Update | File upload dropzone (`.dbml`, `.txt`). Pre-submit parse validation with `@dbml/core`. Invalid DBML blocks the submit. |
| Detail / Lens | Interactive `VueFlow` diagram with pan, zoom, drag, minimap, grid background, auto-layout via `dagre`. |
| Index | Hidden — a full diagram inside a table cell adds no decision-making value. |
| API (raw string) | `fillAttributeFromRequest` transparently accepts a raw DBML string for programmatic callers. |

### Under the hood

| Layer | Dependency |
|---|---|
| Parser | [`@dbml/core`](https://www.npmjs.com/package/@dbml/core) — the official DBML parser. |
| Renderer | [`@vue-flow/core`](https://www.npmjs.com/package/@vue-flow/core), `@vue-flow/controls`, `@vue-flow/minimap`, `@vue-flow/background`. |
| Auto-layout | [`dagre`](https://www.npmjs.com/package/dagre) with `rankdir: LR`. |

All three live behind a one-way pipeline (`parser → graph adapter → layout → renderer`) — no step mutates earlier results, results are memoized per DBML string.

## Testing

```bash

npm run test

```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/opscale-co/.github/blob/main/CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email development@opscale.co instead of using the issue tracker.

## Credits

- [Opscale](https://github.com/opscale-co)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
