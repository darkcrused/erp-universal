<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Tenant;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::updateOrCreate(
            ['id' => '6e3f40f0-04a8-4349-ad97-34c317e7981f'],
            ['name' => 'Empresa Demo Ltda']
        );

        $user = User::updateOrCreate(
            ['email' => 'admin@demo.com.br'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Admin Demo',
                'password' => Hash::make('Senha@123'),
                'locale' => 'pt_BR',
                'timezone' => 'America/Sao_Paulo',
                'is_active' => true,
            ]
        );

        $user->syncRoles(['admin']);
    }
}
