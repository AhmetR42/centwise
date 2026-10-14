<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\DefaultCategories;
use App\Support\DemoData;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * A ready-to-use account: demo@centwise.test / password
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Demo user',
            'email' => 'demo@centwise.test',
            'password' => 'password',
        ]);

        DefaultCategories::create($user);
        DemoData::fill($user);
    }
}
