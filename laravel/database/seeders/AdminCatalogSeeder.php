<?php

namespace Database\Seeders;

use App\Models\CatalogSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminCatalogSeeder extends Seeder
{
    public function run(): void
    {
        CatalogSetting::query()->updateOrCreate(
            ['key' => CatalogSetting::CATALOG_KEY],
            [
                'label' => 'Lab catalog',
                'description' => 'Cho phép learner nhìn thấy catalog các business-logic lab.',
                'group' => 'Learning shell',
                'implemented' => true,
                'default_enabled' => false,
            ],
        );

        foreach (config('labs.registry', []) as $lab) {
            CatalogSetting::query()->updateOrCreate(
                ['key' => $lab['key']],
                [
                    'label' => $lab['label'],
                    'description' => $lab['description'],
                    'group' => $lab['group'],
                    'implemented' => $lab['implemented'],
                    'default_enabled' => $lab['default_enabled'],
                ],
            );
        }

        $adminEmail = config('catalog.admin.email');
        $adminPassword = config('catalog.admin.password');

        if (! filled($adminEmail) || ! filled($adminPassword)) {
            $this->command?->warn('Admin user skipped: set ADMIN_EMAIL and ADMIN_PASSWORD to seed one.');

            return;
        }

        $admin = User::query()->firstOrNew(['email' => $adminEmail]);
        $admin->name = config('catalog.admin.name');
        $admin->password = Hash::make($adminPassword);
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
    }
}
