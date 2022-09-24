<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        User::create([
            'name' => 'Guido',
            'email' => 'guidoruiz@gmail.com',
            'password' => bcrypt('guido123')
        ]);
        User::create([
            'name' => 'carlos',
            'email' => 'carlos@gmail.com',
            'password' => bcrypt('carlos123')
        ]);
        User::create([
            'name' => 'ronal',
            'email' => 'ronal@gmail.com',
            'password' => bcrypt('ronal123')
        ]);
        User::create([
            'name' => 'derlis',
            'email' => 'derlis@gmail.com',
            'password' => bcrypt('derlis123')
        ]);
    }
}
