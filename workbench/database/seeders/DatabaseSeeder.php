<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Workbench\App\Models\Schema;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@laravel.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ],
        );

        Schema::firstOrCreate(
            ['name' => 'Valid schema'],
            ['dbml' => <<<'DBML'
                Table users {
                  id integer [pk]
                  name varchar
                  email varchar [unique]
                }

                Table posts {
                  id integer [pk]
                  user_id integer
                  title varchar
                }

                Ref: posts.user_id > users.id
                DBML],
        );

        Schema::firstOrCreate(
            ['name' => 'Invalid schema'],
            ['dbml' => 'Table users { this is not valid dbml ]'],
        );
    }
}
