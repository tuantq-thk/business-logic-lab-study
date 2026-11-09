<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:make-admin {email : The email address of the user to promote}')]
#[Description('Promote an existing user to the admin role')]
class MakeUserAdmin extends Command
{
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('No user found for that email address.');

            return self::FAILURE;
        }

        $user->forceFill(['role' => User::ROLE_ADMIN])->save();
        $this->info("{$user->email} is now an admin.");

        return self::SUCCESS;
    }
}
