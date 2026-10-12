<?php

namespace App\Actions\Restaurant;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CreateMenuItemAction
{
    /**
     * @param  array{name: string, price: numeric-string|int|float, currency_code: string, description?: string|null, is_available?: bool}  $validatedData
     */
    public function execute(
        Restaurant $restaurant,
        array $validatedData,
        ?UploadedFile $photo,
    ): MenuItem {
        $photoPath = null;

        if ($photo !== null) {
            $photoPath = $photo->store('menu-items', 'public');

            if ($photoPath === false) {
                throw new RuntimeException('Unable to store the menu item photo.');
            }
        }

        return $restaurant->menuItems()->create([
            ...$validatedData,
            'photo_path' => $photoPath,
        ]);
    }
}
