<?php

namespace App\Livewire\Concerns;

trait AuthorizesAdminLivewire
{
    protected function authorizeAdmin(): void
    {
        abort_unless((bool) auth()->user()?->isAdmin(), 403, 'Unauthorized access to BMW Admin Portal.');
    }

    protected function authorizeCrm(): void
    {
        abort_unless((bool) auth()->user()?->canAccessCrm(), 403, 'Unauthorized access to BMW CRM Portal.');
    }

    protected function authorizeContent(): void
    {
        abort_unless((bool) auth()->user()?->canAccessContent(), 403, 'Unauthorized access to BMW Content Portal.');
    }

    protected function authorizeSystemManagement(): void
    {
        abort_unless((bool) auth()->user()?->canManageSystem(), 403, 'Unauthorized access to BMW System Settings.');
    }
}
