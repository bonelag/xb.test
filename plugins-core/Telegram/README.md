# Plugin Telegram

Plugin Telegram Bot cho XBoard, cung cấp các chức năng như liên kết tài khoản người dùng, truy vấn dung lượng, lấy liên kết đăng ký, v.v.

## Tính năng

-   ✅ Chức năng thông báo vé hỗ trợ (có thể bật/tắt)
-   ✅ Chức năng thông báo thanh toán (có thể bật/tắt)
-   ✅ Liên kết/Hủy liên kết tài khoản người dùng
-   ✅ Truy vấn tình trạng sử dụng dung lượng
-   ✅ Lấy liên kết đăng ký
-   ✅ Hỗ trợ phản hồi vé hỗ trợ

## Các lệnh khả dụng

### `/start` - Bắt đầu

Chào mừng người dùng mới và hiển thị thông tin trợ giúp, hỗ trợ cấu hình động.

### `/bind` - Liên kết tài khoản

Liên kết tài khoản XBoard của người dùng với Telegram.

/bind [liên kết đăng ký]

### `/traffic` - Xem dung lượng

Xem tình trạng sử dụng dung lượng của tài khoản đã liên kết hiện tại.

### `/getlatesturl` - Lấy liên kết đăng ký

Lấy liên kết đăng ký mới nhất.

### `/unbind` - Hủy liên kết

Hủy liên kết giữa tài khoản Telegram hiện tại và tài khoản XBoard.

## Tùy chọn cấu hình

### Cấu hình cơ bản

| Mục cấu hình | Kiểu dữ liệu | Giá trị mặc định                                                                                     | Mô tả                 |
| ------------ | ------------ | ---------------------------------------------------------------------------------------------------- | --------------------- |
| `auto_reply` | boolean      | true                                                                                                 | Có tự động phản hồi các lệnh không xác định không |
| `help_text`  | text         | 'Vui lòng sử dụng các lệnh sau:\\n/bind - Liên kết tài khoản\\n/traffic - Xem dung lượng\\n/getlatesturl - Lấy liên kết mới nhất' | Văn bản phản hồi khi gặp lệnh không xác định   |

### Cấu hình động cho lệnh `/start`

| Mục cấu hình            | Kiểu dữ liệu | Mô tả                     |
| ----------------------- | ------------ | ------------------------- |
| `start_welcome_title`   | text         | Tiêu đề chào mừng         |
| `start_bot_description` | text         | Giới thiệu chức năng bot  |
| `start_bind_guide`      | text         | Hướng dẫn liên kết cho người dùng chưa liên kết     |
| `start_unbind_guide`    | text         | Danh sách lệnh hiển thị cho người dùng đã liên kết |
| `start_bind_commands`   | text         | Danh sách lệnh hiển thị cho người dùng chưa liên kết |
| `start_footer`          | text         | Thông tin chân trang        |

### Cấu hình thông báo vé hỗ trợ

| Mục cấu hình           | Kiểu dữ liệu | Giá trị mặc định | Mô tả                 |
| ---------------------- | ------------ | ---------------- | --------------------- |
| `enable_ticket_notify` | boolean      | true             | Có bật chức năng thông báo vé hỗ trợ không |

### Cấu hình thông báo thanh toán

| Mục cấu hình            | Kiểu dữ liệu | Giá trị mặc định | Mô tả                 |
| ----------------------- | ------------ | ---------------- | --------------------- |
| `enable_payment_notify` | boolean      | true             | Có bật chức năng thông báo thanh toán không |

## Quy trình sử dụng

### Quy trình cho người dùng mới

1. Người dùng lần đầu sử dụng Bot, gửi `/start`
2. Liên kết tài khoản theo hướng dẫn: `/bind [liên kết đăng ký]`
3. Sau khi liên kết thành công, có thể sử dụng các chức năng khác

### Quy trình sử dụng hàng ngày

1. Xem dung lượng: `/traffic`
2. Lấy liên kết đăng ký: `/getlatesturl`
3. Quản lý liên kết: `/unbind`