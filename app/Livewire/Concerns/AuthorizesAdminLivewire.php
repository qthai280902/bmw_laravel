<?php

namespace App\Livewire\Concerns;

trait AuthorizesAdminLivewire
{
    protected function authorizeAdmin(): void
    {
        abort_unless((bool) auth()->user()?->isAdmin(), 403, 'Unauthorized access to BMW Admin Portal.');
    }
}
