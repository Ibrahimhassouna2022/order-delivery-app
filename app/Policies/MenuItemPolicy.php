<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MenuItemPolicy
{
    public function create(User $user, Restaurant $restaurant): bool
    {
        return $user->role->value === 'restaurant'
            && $user->restaurants()->whereKey($restaurant->getKey())->exists();
    }

    public function update(User $user, MenuItem $menuItem): Response
    {
        if ($user->role->value !== 'restaurant'
            || ! $user->restaurants()->whereKey($menuItem->restaurant_id)->exists()) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function delete(User $user, MenuItem $menuItem): Response
    {
        return $this->update($user, $menuItem);
    }
}
