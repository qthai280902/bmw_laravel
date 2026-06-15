<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public function edit(): View
    {
        $backgroundPath = SiteSetting::getValue(SiteSetting::PUBLIC_FORM_BACKGROUND_IMAGE);

        return view('admin.site-settings.edit', [
            'backgroundPath' => $backgroundPath,
            'backgroundUrl' => SiteSetting::publicFormBackgroundImageUrl(),
            'fallbackUrl' => asset(SiteSetting::DEFAULT_PUBLIC_FORM_BACKGROUND_IMAGE),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'public_form_background_image' => ['nullable', 'image', 'max:5120'],
            'remove_public_form_background_image' => ['nullable', 'boolean'],
        ]);

        $currentPath = SiteSetting::getValue(SiteSetting::PUBLIC_FORM_BACKGROUND_IMAGE);

        if ($request->boolean('remove_public_form_background_image')) {
            $this->deleteStoredImage($currentPath);
            SiteSetting::setValue(SiteSetting::PUBLIC_FORM_BACKGROUND_IMAGE, null, 'image');

            return redirect()
                ->route('admin.site-settings.edit')
                ->with('success', 'Đã đưa nền form public về ảnh mặc định.');
        }

        if ($request->hasFile('public_form_background_image')) {
            $path = $request->file('public_form_background_image')->store('site-settings', 'public');

            $this->deleteStoredImage($currentPath);

            SiteSetting::setValue(SiteSetting::PUBLIC_FORM_BACKGROUND_IMAGE, $path, 'image');

            return redirect()
                ->route('admin.site-settings.edit')
                ->with('success', 'Đã cập nhật hình nền form public.');
        }

        return redirect()
            ->route('admin.site-settings.edit')
            ->with('success', 'Thiết lập giao diện chưa có thay đổi mới.');
    }

    private function deleteStoredImage(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $normalizedPath = ltrim($path, '/');

        if (str_starts_with($normalizedPath, 'storage/')) {
            $normalizedPath = substr($normalizedPath, strlen('storage/'));
        }

        Storage::disk('public')->delete($normalizedPath);
    }
}
