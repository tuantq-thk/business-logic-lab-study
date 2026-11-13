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
                'label_key' => 'catalog.global.label',
                'description_key' => 'catalog.global.description',
                'group_key' => 'catalog.global.group',
                'implemented' => true,
                'default_enabled' => false,
            ],
        );

        foreach (config('labs.registry', []) as $lab) {
            CatalogSetting::query()->updateOrCreate(
                ['key' => $lab['key']],
                [
                    'label_key' => $lab['label_key'],
                    'description_key' => $lab['description_key'],
                    'group_key' => $lab['group_key'],
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
