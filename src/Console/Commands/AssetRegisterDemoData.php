<?php

namespace Hki\AssetRegister\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

final class AssetRegisterDemoData extends Command
{
    protected $signature = 'asset-register:demo-data {accountId} {--reset : Xoá dữ liệu Sổ tài sản của riêng tài khoản trước khi dựng}';
    protected $description = 'Dựng dữ liệu mẫu cho plugin Sổ tài sản phiên bản v0.1.0 theo mục 9.3';

    public function handle()
    {
        // 1. Từ chối chạy khi APP_ENV=production
        if (app()->environment('production')) {
            $this->error('Lệnh này bị từ chối chạy trong môi trường production!');
            return 1;
        }

        $accountId = $this->argument('accountId');
        if (! ctype_digit((string) $accountId) || ! DB::table('accounts')->where('id', (int) $accountId)->exists()) {
            $this->error("Account không tồn tại: {$accountId}");
            return self::FAILURE;
        }
        $accountId = (int) $accountId;
        $today = Carbon::today();
        $requiredDemoMembers = ['ha', 'khoa', 'bao', 'an', 'tam', 'huy'];
        $activeDemoMembers = DB::table('users as u')
            ->join('account_user as au', 'au.user_id', '=', 'u.id')
            ->where('au.account_id', $accountId)
            ->whereNull('au.suspended_at')
            ->whereNull('u.deleted_at')
            ->whereNull('u.banned_at')
            ->whereIn('u.email', array_map(static fn (string $key): string => "{$key}@sotaisan.test", $requiredDemoMembers))
            ->pluck('u.email')
            ->all();
        $missingDemoMembers = array_diff(
            array_map(static fn (string $key): string => "{$key}@sotaisan.test", $requiredDemoMembers),
            $activeDemoMembers,
        );
        if ($missingDemoMembers !== []) {
            $this->error('Thiếu thành viên đang hoạt động trong tài khoản: ' . implode(', ', $missingDemoMembers));
            return self::FAILURE;
        }

        $this->info("Bắt đầu dựng dữ liệu mẫu cho tài khoản ID: {$accountId} (Hôm nay: {$today->toDateString()})");

        DB::beginTransaction();
        try {
            // 2. Xử lý cờ --reset: Chỉ xoá dữ liệu thuộc về RIÊNG tài khoản đó
            if ($this->option('reset')) {
                $this->info('Đang xoá dữ liệu cũ của tài khoản...');

                // Xoá lịch sử, giao nhận, tài sản, loại, nơi đặt của riêng tài khoản này
                // (Giả định các bảng có cột account_id hoặc liên kết qua asset/type/location)
                DB::table('asset_histories')->whereIn('asset_id', function ($query) use ($accountId) {
                    $query->select('id')->from('assets')->where('account_id', $accountId);
                })->delete();

                DB::table('asset_handovers')->whereIn('asset_id', function ($query) use ($accountId) {
                    $query->select('id')->from('assets')->where('account_id', $accountId);
                })->delete();

                DB::table('assets')->where('account_id', $accountId)->delete();
                DB::table('asset_types')->where('account_id', $accountId)->delete();
                DB::table('asset_locations')->where('account_id', $accountId)->delete();
            }

            // 3. Dựng 9 Loại tài sản
            $this->info('Đang tạo danh mục Loại tài sản...');
            $types = [
                ['name' => 'Laptop', 'prefix' => 'LT', 'icon' => 'laptop', 'status' => 'active'],
                ['name' => 'Máy để bàn', 'prefix' => 'PC', 'icon' => 'desktop', 'status' => 'active'],
                ['name' => 'Màn hình', 'prefix' => 'MH', 'icon' => 'monitor', 'status' => 'active'],
                ['name' => 'Điện thoại', 'prefix' => 'DT', 'icon' => 'phone', 'status' => 'active'],
                ['name' => 'Máy tính bảng', 'prefix' => 'MB', 'icon' => 'tablet', 'status' => 'active'],
                ['name' => 'Thiết bị mạng', 'prefix' => 'TBM', 'icon' => 'router', 'status' => 'active'],
                ['name' => 'Máy chủ', 'prefix' => 'SV', 'icon' => 'server', 'status' => 'active'],
                ['name' => 'Phụ kiện', 'prefix' => 'PK', 'icon' => 'headphones', 'status' => 'active'],
                ['name' => 'Máy chiếu', 'prefix' => 'MC', 'icon' => 'projector', 'status' => 'inactive'], // Ngừng dùng
            ];

            $typeIds = [];
            foreach ($types as $t) {
                $typeIds[$t['prefix']] = DB::table('asset_types')->insertGetId([
                    'account_id' => $accountId,
                    'name' => $t['name'],
                    'prefix' => $t['prefix'],
                    'icon' => $t['icon'],
                    'status' => $t['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 4. Dựng 5 Nơi đặt
            $this->info('Đang tạo danh mục Nơi đặt...');
            $locations = [
                ['name' => 'Văn phòng tầng 3', 'description' => 'Khu làm việc chung', 'status' => 'active'],
                ['name' => 'Phòng họp lớn', 'description' => null, 'status' => 'active'],
                ['name' => 'Phòng máy chủ', 'description' => 'Có khoá, chỉ quản trị hệ thống vào', 'status' => 'active'],
                ['name' => 'Kho thiết bị', 'description' => 'Tủ kho phòng hành chính', 'status' => 'active'],
                ['name' => 'Kho cũ tầng 1', 'description' => 'Đã trả mặt bằng', 'status' => 'inactive'], // Ngừng dùng
            ];

            $locationIds = [];
            foreach ($locations as $l) {
                $locationIds[$l['name']] = DB::table('asset_locations')->insertGetId([
                    'account_id' => $accountId,
                    'name' => $l['name'],
                    'description' => $l['description'],
                    'status' => $l['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Hàm phụ trợ lấy user_id từ email dạng <key>@sotaisan.test
            $getUserId = function ($key) use ($accountId) {
                if (!$key) return null;
                return DB::table('users')
                    ->join('account_user', 'account_user.user_id', '=', 'users.id')
                    ->where('email', "{$key}@sotaisan.test")
                    ->where('account_user.account_id', $accountId)
                    ->whereNull('account_user.suspended_at')
                    ->whereNull('users.deleted_at')
                    ->whereNull('users.banned_at')
                    ->value('users.id');
            };

            // 5. Dựng 18 Tài sản mẫu theo mục 9.3
            $this->info('Đang tạo 18 tài sản mẫu...');
            $assets = [
                [
                    'code' => 'DT-0001',
                    'name' => 'iPhone 13 (máy kiểm thử)',
                    'type' => 'DT',
                    'status' => 'available',
                    'keeper' => null,
                    'location' => 'Kho thiết bị',
                    'warranty_end' => '2023-10-05',
                    'brand' => 'Apple',
                    'model' => 'iPhone 13',
                    'serial' => 'F17GQ2XYN735',
                    'condition' => 'fair'
                ],
                [
                    'code' => 'DT-0002',
                    'name' => 'Samsung Galaxy A54 (máy kiểm thử)',
                    'type' => 'DT',
                    'status' => 'in_repair',
                    'keeper' => null,
                    'location' => 'Kho thiết bị',
                    'warranty_end' => '2025-01-12',
                    'brand' => 'Samsung',
                    'model' => 'Galaxy A54',
                    'serial' => 'R58W40AB12K',
                    'condition' => 'poor'
                ],
                [
                    'code' => 'DT-0003',
                    'name' => 'Xiaomi Redmi Note 13 (máy kiểm thử)',
                    'type' => 'DT',
                    'status' => 'available',
                    'keeper' => null,
                    'location' => 'Kho thiết bị',
                    'warranty_end' => $today->copy()->addDays(200)->toDateString(),
                    'brand' => 'Xiaomi',
                    'model' => 'Redmi Note 13',
                    'serial' => '23129RAA4G01',
                    'condition' => 'good'
                ],
                [
                    'code' => 'LT-0001',
                    'name' => 'Laptop Dell Latitude 5440',
                    'type' => 'LT',
                    'status' => 'in_use',
                    'keeper' => 'khoa',
                    'location' => 'Văn phòng tầng 3',
                    'warranty_end' => '2027-03-15',
                    'brand' => 'Dell',
                    'model' => 'Latitude 5440',
                    'serial' => '7XK2Q83',
                    'condition' => 'good'
                ],
                [
                    'code' => 'LT-0002',
                    'name' => 'Laptop Dell Latitude 5440',
                    'type' => 'LT',
                    'status' => 'in_use',
                    'keeper' => 'bao',
                    'location' => 'Văn phòng tầng 3',
                    'warranty_end' => '2027-03-15',
                    'brand' => 'Dell',
                    'model' => 'Latitude 5440',
                    'serial' => '7XK2Q91',
                    'condition' => 'good'
                ],
                [
                    'code' => 'LT-0003',
                    'name' => 'MacBook Pro 14 M3',
                    'type' => 'LT',
                    'status' => 'in_use',
                    'keeper' => 'an',
                    'location' => 'Văn phòng tầng 3',
                    'warranty_end' => '2028-03-31',
                    'brand' => 'Apple',
                    'model' => 'MacBook Pro 14 (M3, 2023)',
                    'serial' => 'C02FK1QZMD6T',
                    'condition' => 'new',
                    'handover_status' => 'pending',
                ],
                [
                    'code' => 'LT-0004',
                    'name' => 'Laptop HP ProBook 440 G5',
                    'type' => 'LT',
                    'status' => 'disposed',
                    'keeper' => null,
                    'location' => 'Kho thiết bị',
                    'warranty_end' => '2020-07-02',
                    'brand' => 'HP',
                    'model' => 'ProBook 440 G5',
                    'serial' => '5CD8231JQK',
                    'condition' => 'poor'
                ],
                [
                    'code' => 'MB-0001',
                    'name' => 'iPad Air 5 (máy kiểm thử)',
                    'type' => 'MB',
                    'status' => 'broken',
                    'keeper' => null,
                    'location' => 'Kho thiết bị',
                    'warranty_end' => '2024-02-14',
                    'brand' => 'Apple',
                    'model' => 'iPad Air (5th gen)',
                    'serial' => 'DMPGQ7Z1Q16M',
                    'condition' => 'poor'
                ],
                [
                    'code' => 'MB-0002',
                    'name' => 'iPad 10 (máy kiểm thử)',
                    'type' => 'MB',
                    'status' => 'in_use',
                    'keeper' => 'khoa',
                    'location' => 'Văn phòng tầng 3',
                    'warranty_end' => '2027-10-15',
                    'brand' => 'Apple',
                    'model' => 'iPad (10th gen)',
                    'serial' => 'GG7XL3V2PQ',
                    'condition' => 'new',
                    'handover_status' => 'pending',
                ],
                [
                    'code' => 'MH-0001',
                    'name' => 'Màn hình Dell P2422H',
                    'type' => 'MH',
                    'status' => 'in_use',
                    'keeper' => 'khoa',
                    'location' => 'Văn phòng tầng 3',
                    'warranty_end' => $today->copy()->addDays(22)->toDateString(),
                    'brand' => 'Dell',
                    'model' => 'P2422H',
                    'serial' => 'CN0K9P1D',
                    'condition' => 'good'
                ],
                [
                    'code' => 'MH-0002',
                    'name' => 'Màn hình Dell P2422H',
                    'type' => 'MH',
                    'status' => 'available',
                    'keeper' => null,
                    'location' => 'Kho thiết bị',
                    'warranty_end' => $today->copy()->addDays(22)->toDateString(),
                    'brand' => 'Dell',
                    'model' => 'P2422H',
                    'serial' => 'CN0K9P7F',
                    'condition' => 'good'
                ],
                [
                    'code' => 'MH-0003',
                    'name' => 'Màn hình LG 24MP400',
                    'type' => 'MH',
                    'status' => 'available',
                    'keeper' => null,
                    'location' => 'Kho thiết bị',
                    'warranty_end' => $today->copy()->addDays(400)->toDateString(),
                    'brand' => 'LG',
                    'model' => '24MP400',
                    'serial' => '403NTAB1C2',
                    'condition' => 'good'
                ],
                [
                    'code' => 'PC-0001',
                    'name' => 'Mac mini M2',
                    'type' => 'PC',
                    'status' => 'in_use',
                    'keeper' => 'tam',
                    'location' => 'Văn phòng tầng 3',
                    'warranty_end' => $today->copy()->subDays(29)->toDateString(),
                    'brand' => 'Apple',
                    'model' => 'Mac mini (M2, 2023)',
                    'serial' => 'H4VXR2LQ0Y',
                    'condition' => 'good'
                ],
                [
                    'code' => 'PK-0001',
                    'name' => 'Bàn phím Logitech MX Keys',
                    'type' => 'PK',
                    'status' => 'lost',
                    'keeper' => null,
                    'location' => 'Văn phòng tầng 3',
                    'warranty_end' => '2025-03-15',
                    'brand' => 'Logitech',
                    'model' => 'MX Keys',
                    'serial' => '2128LZ0A9F8',
                    'condition' => 'good'
                ],
                [
                    'code' => 'PK-0002',
                    'name' => 'Chuột Logitech MX Master 3S',
                    'type' => 'PK',
                    'status' => 'in_use',
                    'keeper' => 'tam',
                    'location' => 'Văn phòng tầng 3',
                    'warranty_end' => '2027-02-20',
                    'brand' => 'Logitech',
                    'model' => 'MX Master 3S',
                    'serial' => '2231LZ5K0P2',
                    'condition' => 'good'
                ],
                [
                    'code' => 'PK-0003',
                    'name' => 'Tai nghe Jabra Evolve2 65',
                    'type' => 'PK',
                    'status' => 'available',
                    'keeper' => null,
                    'location' => 'Kho thiết bị',
                    'warranty_end' => $today->copy()->addDays(300)->toDateString(),
                    'brand' => 'Jabra',
                    'model' => 'Evolve2 65',
                    'serial' => '26599-989-899',
                    'condition' => 'new'
                ],
                [
                    'code' => 'SV-0001',
                    'name' => 'Máy chủ Dell PowerEdge T150',
                    'type' => 'SV',
                    'status' => 'in_use',
                    'keeper' => 'huy',
                    'location' => 'Phòng máy chủ',
                    'warranty_end' => $today->copy()->addDays(20)->toDateString(),
                    'brand' => 'Dell',
                    'model' => 'PowerEdge T150',
                    'serial' => 'JX4TQ63',
                    'condition' => 'good'
                ],
                [
                    'code' => 'TBM-0001',
                    'name' => 'Router Draytek Vigor 2927',
                    'type' => 'TBM',
                    'status' => 'in_use',
                    'keeper' => 'huy',
                    'location' => 'Phòng máy chủ',
                    'warranty_end' => '2026-05-08',
                    'brand' => 'Draytek',
                    'model' => 'Vigor 2927',
                    'serial' => '2927AC0193',
                    'condition' => 'good'
                ],
            ];

            foreach ($assets as $item) {
                $keeperId = $getUserId($item['keeper']);
                $assetId = DB::table('assets')->insertGetId([
                    'account_id' => $accountId,
                    'asset_type_id' => $typeIds[$item['type']],
                    'asset_location_id' => $locationIds[$item['location']],
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'brand' => $item['brand'],
                    'model' => $item['model'],
                    'serial' => $item['serial'],
                    'condition' => $item['condition'],
                    'status' => $item['status'],
                    'current_keeper_id' => $keeperId,
                    'warranty_end_date' => $item['warranty_end'],
                    'purchase_date' => now()->subYears(1)->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Ghi lịch sử cơ bản (Thêm vào sổ)
                DB::table('asset_histories')->insert([
                    'asset_id' => $assetId,
                    'action' => 'added',
                    'description' => "Thêm vào sổ mã {$item['code']}",
                    'user_id' => $getUserId('ha'), // Lê Thu Hà thực hiện
                    'created_at' => now(),
                ]);

                // Nếu tài sản đang được giao hoặc chờ xác nhận, tạo bảng ghi giao nhận (handovers)
                if ($keeperId && $item['status'] === 'in_use') {
                    $handoverStatus = $item['handover_status'] ?? 'confirmed';
                    DB::table('asset_handovers')->insert([
                        'asset_id' => $assetId,
                        'recipient_id' => $keeperId,
                        'status' => $handoverStatus,
                        'handed_over_at' => now()->subDays(2),
                        'confirmed_at' => $handoverStatus === 'confirmed' ? now()->subDays(1) : null,
                        'condition_on_handover' => $item['condition'],
                        'created_at' => now(),
                    ]);
                }
            }

            DB::commit();
            $this->info('Đã dựng thành công toàn bộ dữ liệu mẫu Sổ tài sản v0.1.0!');
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Lỗi khi dựng dữ liệu mẫu: ' . $e->getMessage());
            return 1;
        }
    }
}
