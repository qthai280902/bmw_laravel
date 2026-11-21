<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->update(['notifiable_type' => 'user']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')
            ->where('notifiable_type', 'user')
            ->update(['notifiable_type' => User::class]);
    }
};
