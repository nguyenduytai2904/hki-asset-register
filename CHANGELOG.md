# Changelog

## [Unreleased]

## [0.1.0] — 2026-10-28

MINOR — Phát hành các use case UC-001–UC-003.
- **Tính năng**: điều hướng và phân quyền; quản lý loại tài sản và vị trí; trang “Của tôi” hiển thị trạng thái trống cho đến khi UC-008 được triển khai.
- **Lược đồ**: sở hữu năm bảng `asset_register_*`; migration chuyển đổi tên bảng thử nghiệm cũ và bổ sung account scope cho handover/history.
- **Cấu hình máy chủ**: không yêu cầu cấu hình mới.
- **Ghi chú triển khai**: cài package, chạy `php artisan package:discover`, `php artisan migrate`, sau đó `php artisan plugin:sync --prune` và grant plugin cho account cần dùng.
- **Không tương thích**: không; UC-004–UC-007 chưa được phát hành trong phiên bản này.
- **Kiểm thử**: 17 Asset plugin tests (114 assertions) và `php artisan route:list --path=asset-register`.
