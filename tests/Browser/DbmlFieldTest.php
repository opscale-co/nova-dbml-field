<?php

declare(strict_types=1);

namespace Opscale\NovaDbmlField\Tests\Browser;

use Laravel\Dusk\Browser;
use Opscale\NovaDbmlField\Tests\DuskTestCase;
use PHPUnit\Framework\Attributes\Test;

final class DbmlFieldTest extends DuskTestCase
{
    #[Test]
    final public function create_view_shows_the_file_upload_dropzone(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->loginToNova($browser)
                ->visit('/nova/resources/schemas/new')
                ->waitForText('Create Schema', 15)
                ->assertPresent('[data-testid="dbml-form-field"]')
                ->assertPresent('[data-testid="dbml-form-field"] input[type="file"]');
        });
    }

    #[Test]
    final public function uploading_a_valid_dbml_file_shows_the_ok_status(): void
    {
        $fixture = __DIR__.'/fixtures/valid.dbml';

        $this->browse(function (Browser $browser) use ($fixture): void {
            $this->loginToNova($browser)
                ->visit('/nova/resources/schemas/new')
                ->waitForText('Create Schema', 15)
                ->attach('[data-testid="dbml-form-field"] input[type="file"]', $fixture)
                ->waitFor('[data-testid="dbml-form-field-ok"]', 5)
                ->assertSeeIn('[data-testid="dbml-form-field-ok"]', 'Schema parsed')
                ->screenshot('form-dropzone-nova-wrapped');
        });
    }

    #[Test]
    final public function create_form_submits_the_file_contents_as_the_attribute(): void
    {
        $fixture = __DIR__.'/fixtures/valid.dbml';

        $this->browse(function (Browser $browser) use ($fixture): void {
            $this->loginToNova($browser)
                ->visit('/nova/resources/schemas/new')
                ->waitForText('Create Schema', 15)
                ->type('@name', 'Created via browser')
                ->attach('[data-testid="dbml-form-field"] input[type="file"]', $fixture)
                ->waitFor('[data-testid="dbml-form-field-ok"]', 5)
                ->press('Create Schema')
                ->waitForText('The schema was created', 15)
                ->waitFor('[data-testid="dbml-viewer"]', 10)
                ->waitFor('[data-testid="dbml-table-node"]', 10)
                ->assertPresent('[data-testid="dbml-table-node"]');
        });
    }

    #[Test]
    final public function detail_view_renders_the_interactive_diagram(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->loginToNova($browser)
                ->visit('/nova/resources/schemas/1')
                ->waitFor('[data-testid="dbml-viewer"]', 15)
                ->waitFor('[data-testid="dbml-table-node"]', 10)
                ->assertPresent('[data-testid="dbml-table-node"]')
                ->assertPresent('.vue-flow__node')
                ->assertPresent('.vue-flow__edge')
                ->screenshot('detail-rendered');
        });
    }

    #[Test]
    final public function detail_view_shows_error_banner_for_invalid_dbml(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->loginToNova($browser)
                ->visit('/nova/resources/schemas/2')
                ->waitFor('[data-testid="dbml-viewer"]', 15)
                ->waitFor('[data-testid="dbml-error"]', 10)
                ->assertSeeIn('[data-testid="dbml-error"]', 'DBML parse error');
        });
    }

    #[Test]
    final public function index_view_hides_the_dbml_column(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->loginToNova($browser)
                ->visit('/nova/resources/schemas')
                ->waitForText('Schemas', 15)
                ->assertMissing('[data-testid="dbml-viewer"]')
                ->assertMissing('[data-testid="dbml-table-node"]');
        });
    }

    #[Test]
    final public function update_view_shows_the_file_upload_dropzone(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->loginToNova($browser)
                ->visit('/nova/resources/schemas/1/edit')
                ->waitForText('Update Schema', 15)
                ->assertPresent('[data-testid="dbml-form-field"]')
                ->assertPresent('[data-testid="dbml-form-field"] input[type="file"]');
        });
    }
}
