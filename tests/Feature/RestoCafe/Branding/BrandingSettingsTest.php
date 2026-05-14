<?php

namespace Tests\Feature\RestoCafe\Branding;

use App\Modules\Branches\Models\Branch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class BrandingSettingsTest extends RestoCafeTestCase
{
    public function test_edit_page_includes_branding_tokens(): void
    {
        $resp = $this->actingAs($this->manager())
            ->get(route('branch.edit'))
            ->assertOk();

        $this->assertStringContainsString('&quot;branding&quot;:', $resp->content());
    }

    public function test_update_saves_branding_text_fields(): void
    {
        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name'           => 'Main Branch',
            'tax_rate'       => '10',
            'currency_code'  => 'USD',
            'business_name'  => 'Cafe Roma',
            'tagline'        => 'Taste the difference',
            'story'          => 'Founded in 2020.',
            'primary_color'  => '#123456',
            'secondary_color'=> '#654321',
            'accent_color'   => '#abcdef',
            'whatsapp'       => '+1234567890',
            'public_slug'    => 'cafe-roma',
            'is_public'      => true,
        ])->assertRedirect(route('branch.edit'));

        $branch = Branch::query()->find(1);
        $this->assertSame('Cafe Roma', $branch->business_name);
        $this->assertSame('Taste the difference', $branch->tagline);
        $this->assertSame('Founded in 2020.', $branch->story);
        $this->assertSame('#123456', $branch->primary_color);
        $this->assertSame('cafe-roma', $branch->public_slug);
        $this->assertTrue($branch->is_public);
    }

    public function test_update_rejects_invalid_hex_color(): void
    {
        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name'          => 'Main Branch',
            'tax_rate'      => '10',
            'currency_code' => 'USD',
            'primary_color' => 'not-a-color',
        ])->assertSessionHasErrors(['primary_color']);
    }

    public function test_update_rejects_invalid_url_for_social_links(): void
    {
        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name'          => 'Main Branch',
            'tax_rate'      => '10',
            'currency_code' => 'USD',
            'instagram_url' => 'not-a-url',
        ])->assertSessionHasErrors(['instagram_url']);
    }

    public function test_update_rejects_duplicate_slug_on_other_branch(): void
    {
        $other = $this->makeSecondaryBranch();
        $other['branch']->update(['public_slug' => 'taken-slug']);

        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name'          => 'Main Branch',
            'tax_rate'      => '10',
            'currency_code' => 'USD',
            'public_slug'   => 'taken-slug',
        ])->assertSessionHasErrors(['public_slug']);
    }

    public function test_update_allows_same_slug_for_own_branch(): void
    {
        Branch::query()->find(1)?->update(['public_slug' => 'my-slug']);

        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name'          => 'Main Branch',
            'tax_rate'      => '10',
            'currency_code' => 'USD',
            'public_slug'   => 'my-slug',
        ])->assertRedirect(route('branch.edit'));
    }

    public function test_logo_upload_stores_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name'          => 'Main Branch',
            'tax_rate'      => '10',
            'currency_code' => 'USD',
            'logo'          => UploadedFile::fake()->image('logo.jpg', 200, 200),
        ])->assertRedirect(route('branch.edit'));

        $branch = Branch::query()->find(1);
        $this->assertNotNull($branch->logo_path);
        Storage::disk('public')->assertExists($branch->logo_path);
    }

    public function test_logo_upload_rejects_non_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name'          => 'Main Branch',
            'tax_rate'      => '10',
            'currency_code' => 'USD',
            'logo'          => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors(['logo']);
    }

    public function test_logo_upload_rejects_oversized_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name'          => 'Main Branch',
            'tax_rate'      => '10',
            'currency_code' => 'USD',
            'logo'          => UploadedFile::fake()->image('big.jpg')->size(3000),
        ])->assertSessionHasErrors(['logo']);
    }

    public function test_cover_upload_stores_and_replaces_old(): void
    {
        Storage::fake('public');

        // First upload
        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name'          => 'Main Branch',
            'tax_rate'      => '10',
            'currency_code' => 'USD',
            'cover'         => UploadedFile::fake()->image('cover1.jpg', 1200, 400),
        ])->assertRedirect();

        $branch = Branch::query()->find(1);
        $firstPath = $branch->cover_path;
        Storage::disk('public')->assertExists($firstPath);

        // Second upload deletes the first
        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name'          => 'Main Branch',
            'tax_rate'      => '10',
            'currency_code' => 'USD',
            'cover'         => UploadedFile::fake()->image('cover2.jpg', 1200, 400),
        ])->assertRedirect();

        Storage::disk('public')->assertMissing($firstPath);
        $branch->refresh();
        $this->assertNotSame($firstPath, $branch->cover_path);
    }

    public function test_logo_upload_replaces_existing_logo(): void
    {
        Storage::fake('public');

        // First logo
        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name' => 'Main Branch', 'tax_rate' => '10', 'currency_code' => 'USD',
            'logo' => UploadedFile::fake()->image('logo1.jpg', 200, 200),
        ])->assertRedirect();

        $branch = Branch::query()->find(1);
        $firstPath = $branch->logo_path;
        Storage::disk('public')->assertExists($firstPath);

        // Second upload: old logo must be deleted
        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name' => 'Main Branch', 'tax_rate' => '10', 'currency_code' => 'USD',
            'logo' => UploadedFile::fake()->image('logo2.jpg', 200, 200),
        ])->assertRedirect();

        Storage::disk('public')->assertMissing($firstPath);
        $branch->refresh();
        $this->assertNotSame($firstPath, $branch->logo_path);
    }

    public function test_brand_tokens_inertia_prop_has_fallback_colors(): void
    {
        $resp = $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertStringContainsString('&quot;primary_color&quot;:', $resp->content());
    }
}
