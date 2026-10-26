# Changelog

## [Unreleased]

- **Tính năng**: Hoàn thiện UC-001–UC-007: phân quyền, danh mục, sổ tài sản, tạo, xem chi tiết và cập nhật tài sản.
- **Dữ liệu**: Tài nguyên plugin dùng năm bảng có tiền tố `asset_register_`; installer, migration và lifecycle dùng chung schema.
- **Giao diện**: Nạp CSS/JavaScript qua entrypoints của plugin; dùng Alpine factory trong namespace `window.HK.assetRegister`.
- **Kiểm thử**: Feature tests nghiệp vụ/phân quyền và unit test account scope.

## [0.1.0] — 2026-10-20

PATCH — Bản demo Hello World đầu tiên.
- **Tính năng**: bản demo starter cũ; đã được loại bỏ khỏi package hiện tại.
- **Lược đồ**: không.
- **Cấu hình máy chủ**: không.
- **Ghi chú triển khai**: không.
- **Không tương thích**: không.
- **Kiểm thử**: PHP lint.
