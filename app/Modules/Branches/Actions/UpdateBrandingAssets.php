<?php

namespace App\Modules\Branches\Actions;

use App\Modules\Branches\Models\Branch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateBrandingAssets
{
    public function handle(Branch $branch, ?UploadedFile $logo, ?UploadedFile $cover): void
    {
        if ($logo) {
            if ($branch->logo_path) {
                Storage::disk('public')->delete($branch->logo_path);
            }
            $branch->logo_path = $logo->store('branding/logos', 'public');
        }

        if ($cover) {
            if ($branch->cover_path) {
                Storage::disk('public')->delete($branch->cover_path);
            }
            $branch->cover_path = $cover->store('branding/covers', 'public');
        }
    }
}
