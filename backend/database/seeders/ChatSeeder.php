<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $workspace = Workspace::first();


        if (!$user || !$workspace) {

            $this->command->error(
                "User or Workspace not found"
            );

            return;
        }


        Chat::factory()
            ->count(10)
            ->create([

                'user_id' => $user->id,

                'workspace_id' => $workspace->id,

            ]);
    }
}
