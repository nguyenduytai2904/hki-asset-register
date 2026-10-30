# Changelog

## [Unreleased]

MINOR — Hoàn thiện các quy trình quản lý tài sản UC-001–UC-010 cho quản lý và thành viên; bổ sung cấu hình thời hạn bảo hành và cải thiện các màn hình thao tác.

- **Tính năng**: Bổ sung phân quyền, danh mục, sổ tài sản, tạo/xem/cập nhật tài sản; thành viên xem tài sản được giao và đang giữ; quản lý đổi trạng thái, thanh lý tài sản và theo dõi lịch sử.
- **Giao diện**: Hoàn thiện dialog danh mục, datepicker có thể truy cập bằng bàn phím, biểu mẫu tài sản và khu vực lịch sử cuộn độc lập; nạp tài nguyên qua entrypoint plugin và dùng Alpine factory trong namespace `window.HK.assetRegister`.
- **Sửa lỗi**: Sửa bộ lọc nhiều lựa chọn của sổ tài sản, datepicker trong dialog thao tác, lỗi dialog khi gửi biểu mẫu và các hiển thị thừa; bỏ dữ liệu xem trước không có thật.
- **Dữ liệu**: Dùng năm bảng có tiền tố `asset_register_`; installer, migration và lifecycle dùng chung schema.
- **Hiệu năng**: Phân trang danh sách tài sản của tôi theo tài khoản và thành viên; ưu tiên bàn giao chờ xác nhận.

Lược đồ: migration `2026_10_27_000011_add_warranty_duration_to_asset_register_assets.php` thêm cột `warranty_duration_months` nullable; migration `2026_10_28_000011_add_account_scoped_keeper_index.php` thay chỉ mục người giữ bằng chỉ mục ghép `(account_id, current_keeper_id)`.
Cấu hình máy chủ: không.
Ghi chú triển khai: chạy các migration của plugin Asset Register khi cập nhật.
Không tương thích: không.
Kiểm thử: 50 kiểm thử PHP xanh (276 assertions); 2 kiểm thử JavaScript và `npm run build` xanh; E2E chưa chạy.

## [0.1.0] — 2026-10-20

PATCH — Bản demo Hello World đầu tiên.

- **Tính năng**: bản demo starter cũ; đã được loại bỏ khỏi package hiện tại.

Lược đồ: không.
Cấu hình máy chủ: không.
Ghi chú triển khai: không.
Không tương thích: bản demo starter đã được loại bỏ khỏi package hiện tại.
Kiểm thử: PHP lint.
