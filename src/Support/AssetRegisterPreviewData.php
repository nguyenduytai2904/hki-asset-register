<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Support;

final class AssetRegisterPreviewData
{
    public static function mine(): array
    {
        return [
            'pending' => [[
                'code' => 'MB-0002',
                'name' => 'iPad 10 (máy kiểm thử)',
                'model' => 'Apple iPad (10th gen)',
                'serial' => 'GG7XL3V2PQ',
                'assigned_by' => 'Lê Thu Hà',
                'assigned_at' => '18/10/2026 16:20',
                'condition' => 'Mới',
                'status' => 'pending',
                'icon' => 'tablet_mac',
                'warranty' => '15/10/2027',
            ]],
            'assets' => [
                [
                    'code' => 'MB-0002',
                    'name' => 'iPad 10 (máy kiểm thử)',
                    'model' => 'Apple iPad (10th gen)',
                    'serial' => 'GG7XL3V2PQ',
                    'condition' => 'Mới',
                    'received_at' => '18/10/2026 16:20',
                    'status' => 'pending',
                    'icon' => 'tablet_mac',
                    'warranty' => '15/10/2027',
                ],
                [
                    'code' => 'LT-0001',
                    'name' => 'Laptop Dell Latitude 5440',
                    'model' => 'Dell Latitude 5440',
                    'serial' => '7XK2Q83',
                    'condition' => 'Tốt',
                    'received_at' => '18/03/2024 09:10',
                    'status' => 'assigned',
                    'icon' => 'laptop',
                    'warranty' => '15/03/2027',
                ],
                [
                    'code' => 'MH-0001',
                    'name' => 'Màn hình Dell P2422H',
                    'model' => 'Dell P2422H',
                    'serial' => 'CN0K9P1D',
                    'condition' => 'Tốt',
                    'received_at' => '13/11/2023 08:45',
                    'status' => 'assigned',
                    'icon' => 'desktop_windows',
                    'warranty' => '10/11/2026',
                    'warranty_warning' => 'Còn 22 ngày',
                ],
            ],
        ];
    }

    public static function registry(): array
    {
        return [
            ['code' => 'DT-0001', 'name' => 'iPhone 13 (máy kiểm thử)', 'model' => 'Apple iPhone 13', 'serial' => 'F17GQ2XYN735', 'type' => 'Điện thoại', 'icon' => 'smartphone', 'keeper' => null, 'location' => 'Kho thiết bị', 'condition' => 'Khá', 'status' => 'available', 'warranty' => '05/10/2023', 'warranty_expired' => true],
            ['code' => 'DT-0002', 'name' => 'Samsung Galaxy A54 (máy kiểm thử)', 'model' => 'Samsung Galaxy A54', 'serial' => 'R58W40AB12K', 'type' => 'Điện thoại', 'icon' => 'smartphone', 'keeper' => null, 'location' => 'Kho thiết bị', 'condition' => 'Kém', 'status' => 'repairing', 'warranty' => '12/01/2025', 'warranty_expired' => true],
            ['code' => 'DT-0003', 'name' => 'Xiaomi Redmi Note 13 (máy kiểm thử)', 'model' => 'Xiaomi Redmi Note 13', 'serial' => '23129RAA4G01', 'type' => 'Điện thoại', 'icon' => 'smartphone', 'keeper' => null, 'location' => 'Kho thiết bị', 'condition' => 'Tốt', 'status' => 'available', 'warranty' => '07/05/2027'],
            ['code' => 'LT-0001', 'name' => 'Laptop Dell Latitude 5440', 'model' => 'Dell Latitude 5440', 'serial' => '7XK2Q83', 'type' => 'Laptop', 'icon' => 'laptop', 'keeper' => ['initials' => 'MK', 'name' => 'Trần Minh Khoa'], 'location' => 'Văn phòng tầng 3', 'condition' => 'Tốt', 'status' => 'assigned', 'warranty' => '15/03/2027'],
            ['code' => 'LT-0002', 'name' => 'Laptop Dell Latitude 5440', 'model' => 'Dell Latitude 5440', 'serial' => '7XK2Q91', 'type' => 'Laptop', 'icon' => 'laptop', 'keeper' => ['initials' => 'QB', 'name' => 'Phạm Quốc Bảo'], 'location' => 'Văn phòng tầng 3', 'condition' => 'Tốt', 'status' => 'assigned', 'warranty' => '15/03/2027'],
            ['code' => 'LT-0003', 'name' => 'MacBook Pro 14 M3', 'model' => 'Apple MacBook Pro 14 (M3, 2023)', 'serial' => 'C02FK1QZMD6T', 'type' => 'Laptop', 'icon' => 'laptop_mac', 'keeper' => ['initials' => 'HA', 'name' => 'Nguyễn Hoài An'], 'location' => 'Văn phòng tầng 3', 'condition' => 'Mới', 'status' => 'pending', 'warranty' => '31/03/2028'],
            ['code' => 'LT-0004', 'name' => 'Laptop HP ProBook 440 G5', 'model' => 'HP ProBook 440 G5', 'serial' => '5CD8231JQK', 'type' => 'Laptop', 'icon' => 'laptop', 'keeper' => null, 'location' => 'Kho thiết bị', 'condition' => 'Kém', 'status' => 'retired', 'warranty' => '02/07/2020'],
            ['code' => 'MB-0001', 'name' => 'iPad Air 5 (máy kiểm thử)', 'model' => 'Apple iPad Air (5th gen)', 'serial' => 'DMPGQ7ZQ16M', 'type' => 'Máy tính bảng', 'icon' => 'tablet_mac', 'keeper' => null, 'location' => 'Kho thiết bị', 'condition' => 'Kém', 'status' => 'damaged', 'warranty' => '14/02/2024', 'warranty_expired' => true],
            ['code' => 'MB-0002', 'name' => 'iPad 10 (máy kiểm thử)', 'model' => 'Apple iPad (10th gen)', 'serial' => 'GG7XL3V2PQ', 'type' => 'Máy tính bảng', 'icon' => 'tablet_mac', 'keeper' => ['initials' => 'MK', 'name' => 'Trần Minh Khoa'], 'location' => 'Văn phòng tầng 3', 'condition' => 'Mới', 'status' => 'pending', 'warranty' => '15/10/2027'],
            ['code' => 'MH-0001', 'name' => 'Màn hình Dell P2422H', 'model' => 'Dell P2422H', 'serial' => 'CN0K9P1D', 'type' => 'Màn hình', 'icon' => 'desktop_windows', 'keeper' => ['initials' => 'MK', 'name' => 'Trần Minh Khoa'], 'location' => 'Văn phòng tầng 3', 'condition' => 'Tốt', 'status' => 'assigned', 'warranty' => '10/11/2026', 'warranty_warning' => 'Còn 22 ngày'],
            ['code' => 'MH-0002', 'name' => 'Màn hình Dell P2422H', 'model' => 'Dell P2422H', 'serial' => 'CN0K9P7F', 'type' => 'Màn hình', 'icon' => 'desktop_windows', 'keeper' => null, 'location' => 'Kho thiết bị', 'condition' => 'Tốt', 'status' => 'available', 'warranty' => '10/11/2026', 'warranty_warning' => 'Còn 22 ngày'],
            ['code' => 'MH-0003', 'name' => 'Màn hình LG 29UM69-P', 'model' => 'LG 29UM69-P', 'serial' => '303NTA8B1C2', 'type' => 'Màn hình', 'icon' => 'desktop_windows', 'keeper' => null, 'location' => 'Kho thiết bị', 'condition' => 'Tốt', 'status' => 'available', 'warranty' => '23/11/2027'],
            ['code' => 'PC-0001', 'name' => 'Mac mini M2', 'model' => 'Apple Mac mini (M2, 2023)', 'serial' => 'H4VXR2LQ0Y', 'type' => 'Máy để bàn', 'icon' => 'desktop_windows', 'keeper' => ['initials' => 'TT', 'name' => 'Võ Văn Thành'], 'location' => 'Văn phòng tầng 3', 'condition' => 'Tốt', 'status' => 'assigned', 'warranty' => '20/09/2026', 'warranty_expired' => true],
            ['code' => 'PK-0001', 'name' => 'Bàn phím Logitech MX Keys', 'model' => 'Logitech MX Keys', 'serial' => '920-0123280A9F8', 'type' => 'Phụ kiện', 'icon' => 'keyboard', 'keeper' => null, 'location' => 'Văn phòng tầng 3', 'condition' => 'Tốt', 'status' => 'lost', 'warranty' => '15/03/2025', 'warranty_expired' => true],
            ['code' => 'PK-0002', 'name' => 'Chuột Logitech MX Master 3S', 'model' => 'Logitech MX Master 3S', 'serial' => '231LZ5PK5P2', 'type' => 'Phụ kiện', 'icon' => 'devices_other', 'keeper' => ['initials' => 'CD', 'name' => 'Trịnh Công Đức (đã nghỉ)'], 'location' => 'Văn phòng tầng 3', 'condition' => 'Tốt', 'status' => 'assigned', 'warranty' => '20/02/2027'],
        ];
    }

    public static function types(): array
    {
        return [
            ['name' => 'Laptop', 'prefix' => 'LT', 'next_code' => 'LT-0005', 'count' => 4, 'status' => 'active', 'icon' => 'laptop'],
            ['name' => 'Máy để bàn', 'prefix' => 'PC', 'next_code' => 'PC-0002', 'count' => 1, 'status' => 'active', 'icon' => 'desktop_windows'],
            ['name' => 'Màn hình', 'prefix' => 'MH', 'next_code' => 'MH-0004', 'count' => 3, 'status' => 'active', 'icon' => 'desktop_windows'],
            ['name' => 'Điện thoại', 'prefix' => 'DT', 'next_code' => 'DT-0004', 'count' => 3, 'status' => 'active', 'icon' => 'smartphone'],
            ['name' => 'Máy tính bảng', 'prefix' => 'MB', 'next_code' => 'MB-0003', 'count' => 2, 'status' => 'active', 'icon' => 'tablet_mac'],
            ['name' => 'Thiết bị mạng', 'prefix' => 'TBM', 'next_code' => 'TBM-0002', 'count' => 1, 'status' => 'active', 'icon' => 'router'],
            ['name' => 'Máy chủ', 'prefix' => 'SV', 'next_code' => 'SV-0002', 'count' => 1, 'status' => 'active', 'icon' => 'dns'],
            ['name' => 'Phụ kiện', 'prefix' => 'PK', 'next_code' => 'PK-0004', 'count' => 3, 'status' => 'active', 'icon' => 'keyboard'],
            ['name' => 'Máy chiếu', 'prefix' => 'MC', 'next_code' => 'MC-0001', 'count' => 0, 'status' => 'inactive', 'icon' => 'devices_other'],
        ];
    }

    public static function locations(): array
    {
        return [
            ['name' => 'Văn phòng tầng 3', 'description' => 'Khu làm việc chung', 'count' => 8, 'status' => 'active'],
            ['name' => 'Phòng họp lớn', 'description' => null, 'count' => 0, 'status' => 'active'],
            ['name' => 'Phòng máy chủ', 'description' => 'Có khoá, chỉ quản trị hệ thống vào', 'count' => 2, 'status' => 'active'],
            ['name' => 'Kho thiết bị', 'description' => 'Tủ khoá phòng hành chính', 'count' => 8, 'status' => 'active'],
            ['name' => 'Kho cũ tầng 1', 'description' => 'Đã trả mặt bằng', 'count' => 0, 'status' => 'inactive'],
        ];
    }
}
