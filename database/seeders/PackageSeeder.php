<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach ($this->packages() as $package) {
            Package::updateOrCreate(['code' => $package['code']], $package);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function packages(): array
    {
        return [
            [
                'code' => 'starter',
                'name' => 'Starter',
                'tagline' => 'For small teams getting started',
                'price_label' => 'TZS 45,000 / mo',
                'monthly_price' => 45000,
                'annual_price' => 432000,
                'currency' => 'TZS',
                'features' => ['1 active branch', 'Up to 50 employees', 'Check-ins & reports'],
                'employee_limit' => 50,
                'branch_limit' => 1,
                'active' => true,
                'position' => 1,
            ],
            [
                'code' => 'business',
                'name' => 'Business',
                'tagline' => 'For growing organizations',
                'price_label' => 'TZS 120,000 / mo',
                'monthly_price' => 120000,
                'annual_price' => 1152000,
                'currency' => 'TZS',
                'features' => ['Up to 5 branches', 'Up to 500 employees', 'Attendance analytics', 'Priority support'],
                'employee_limit' => 500,
                'branch_limit' => 5,
                'active' => true,
                'position' => 2,
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'tagline' => 'For large institutions',
                'price_label' => 'TZS 350,000 / mo',
                'monthly_price' => 350000,
                'annual_price' => 3360000,
                'currency' => 'TZS',
                'features' => ['Unlimited branches', 'Unlimited employees', 'SSO & API access', 'Dedicated manager'],
                'employee_limit' => null,
                'branch_limit' => null,
                'active' => true,
                'position' => 3,
            ],
        ];
    }
}
