<?php

namespace App\Livewire\Concerns;

use Livewire\WithPagination;

trait WithBmwPagination
{
    use WithPagination;

    public function paginationView(): string
    {
        return 'livewire.pagination.bmw';
    }
}
