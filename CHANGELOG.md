# Changelog

## [Unreleased]

- **Tính năng**: Hoàn thiện UC-001–UC-007: phân quyền, danh mục, sổ tài sản, tạo, xem chi tiết và cập nhật tài sản.
- **Bảo hành**: Chọn thời hạn 3/6/12/24 tháng hoặc số tháng tùy chỉnh; tự tính ngày hết hạn từ ngày mua nhưng vẫn cho phép sửa; migration thêm cột nullable `warranty_duration_months`.
- **Giao diện**: Thao tác danh mục mở dialog có bố cục nhãn/trường căn trái và nút nhất quán; hộp xác nhận xóa hiển thị phía trước dialog; các datepicker trong form tài sản dùng điều khiển năm gốc của nền tảng, hiển thị cạnh tháng và có thể truy cập bằng bàn phím; sửa hiển thị icon và cuộn kép trong dialog tài sản; sửa lỗi JS datepicker khiến form thêm tài sản thoát dialog; bỏ banner thành công trùng toast, đánh dấu trường bắt buộc và làm sáng ô ngày.
- **Chi tiết tài sản**: Giới hạn chiều cao danh sách lịch sử và cho phép cuộn độc lập; giữ toàn bộ lịch sử mới nhất trước và hỗ trợ cuộn bằng bàn phím.
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
