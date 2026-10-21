# Changelog

## [Unreleased]

- **Tính năng**: Hoàn thiện skeleton plugin theo Quy chuẩn xây dựng plugin 1.0.
- **Lược đồ**: plugin sở hữu năm bảng asset và có lifecycle uninstall/purge.
- **Cấu hình máy chủ**: `ASSET_REGISTER_ENABLED` (mặc định `true`; hiện là cấu hình mẫu).
- **Ghi chú triển khai**: chạy `php artisan plugin:sync`, sau đó grant plugin cho account cần demo.
- **Không tương thích**: không.
- **Kiểm thử**: PHP lint và smoke test manifest.

## [0.1.0] — 2026-10-20

PATCH — Bản demo Hello World đầu tiên.
- **Tính năng**: bản demo starter cũ; đã được loại bỏ khỏi package hiện tại.
- **Lược đồ**: không.
- **Cấu hình máy chủ**: không.
- **Ghi chú triển khai**: không.
- **Không tương thích**: không.
- **Kiểm thử**: PHP lint.
