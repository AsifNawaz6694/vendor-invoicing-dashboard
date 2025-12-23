<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $logTypes = [
            'authentication' => [
                'user.login',
                'user.logout',
                'user.password_reset',
                'user.2fa_enabled',
                'user.2fa_disabled',
            ],
            'user-management' => [
                'user.created',
                'user.updated',
                'user.deleted',
                'user.activated',
                'user.deactivated',
            ],
            'role-management' => [
                'role.created',
                'role.updated',
                'role.deleted',
                'role.permissions_updated',
            ],
            'permission-management' => [
                'permission.created',
                'permission.updated',
                'permission.deleted',
            ],
            'invoice' => [
                'invoice.created',
                'invoice.updated',
                'invoice.approved',
                'invoice.rejected',
                'invoice.paid',
            ],
            'shipment-tracking-update' => [
                'Shipment Tracking Update Cron Started',
                'Shipment Tracking Update Cron Completed',
            ],
            'system' => [
                'system.maintenance_mode_enabled',
                'system.maintenance_mode_disabled',
                'system.backup_created',
                'system.cache_cleared',
            ],
        ];

        $ipAddresses = [
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
            '127.0.0.1',
            '203.0.113.50',
            '198.51.100.25',
        ];

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];

        // Generate activity logs for the past 30 days
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $activities = [];

        for ($i = 0; $i < 500; $i++) {
            $logName = array_rand($logTypes);
            $descriptions = $logTypes[$logName];
            $description = $descriptions[array_rand($descriptions)];

            // Some activities are system-generated (no user)
            $isSystem = $logName === 'shipment-tracking-update' || ($logName === 'system' && rand(0, 1));
            $user = $isSystem ? null : $users->random();

            $createdAt = Carbon::createFromTimestamp(
                rand($startDate->timestamp, $endDate->timestamp)
            );

            $activities[] = [
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'System',
                'user_email' => $user?->email,
                'log_name' => $logName,
                'description' => $description,
                'subject_type' => null,
                'subject_id' => null,
                'causer_type' => $user ? User::class : null,
                'causer_id' => $user?->id,
                'properties' => json_encode($this->generateProperties($logName, $description)),
                'ip_address' => $isSystem ? null : $ipAddresses[array_rand($ipAddresses)],
                'user_agent' => $isSystem ? null : $userAgents[array_rand($userAgents)],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        // Sort by created_at
        usort($activities, fn($a, $b) => $a['created_at'] <=> $b['created_at']);

        // Insert in chunks
        foreach (array_chunk($activities, 100) as $chunk) {
            ActivityLog::insert($chunk);
        }

        $this->command->info('Created 500 sample activity logs.');
    }

    /**
     * Generate sample properties based on log type.
     */
    private function generateProperties(string $logName, string $description): ?array
    {
        $properties = match ($logName) {
            'authentication' => [
                'browser' => ['Chrome', 'Firefox', 'Safari', 'Edge'][rand(0, 3)],
                'platform' => ['Windows', 'macOS', 'Linux'][rand(0, 2)],
            ],
            'user-management' => [
                'changes' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                ],
            ],
            'invoice' => [
                'invoice_number' => 'INV-' . rand(1000, 9999),
                'amount' => rand(100, 10000) / 100,
                'currency' => 'USD',
            ],
            'shipment-tracking-update' => [
                'processed_count' => rand(10, 100),
                'duration_seconds' => rand(5, 60),
            ],
            default => null,
        };

        return $properties;
    }
}
