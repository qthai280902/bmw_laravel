<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicFormVisualSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_form_uses_fallback_background_image(): void
    {
        $this->get(route('appointments.create', ['type' => 'consult']))
            ->assertOk()
            ->assertSee('/images/cars/330i/lifestyle-showroom.png', false)
            ->assertSee('name="type"', false)
            ->assertSee('name="appointment_date"', false);
    }

    public function test_admin_can_upload_public_form_background_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['email' => 'admin@bmw.com']);

        $this->actingAs($admin)
            ->get(route('admin.site-settings.edit'))
            ->assertOk()
            ->assertSee('Thiết lập')
            ->assertSee('public_form_background_image', false);

        $this->actingAs($admin)
            ->put(route('admin.site-settings.update'), [
                'public_form_background_image' => $this->fakePngUpload('form-background.png'),
            ])
            ->assertRedirect(route('admin.site-settings.edit'));

        $setting = SiteSetting::query()
            ->where('key', SiteSetting::PUBLIC_FORM_BACKGROUND_IMAGE)
            ->firstOrFail();

        $this->assertNotNull($setting->value);
        Storage::disk('public')->assertExists($setting->value);

        $this->get(route('appointments.create', ['type' => 'consult']))
            ->assertOk()
            ->assertSee('/storage/'.$setting->value, false);
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'form-background-');

        file_put_contents(
            $path,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=', true),
        );

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}
