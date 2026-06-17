-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 17, 2026 lúc 04:51 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `vehicle_ecommerce`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `accessory_orders`
--

CREATE TABLE `accessory_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `customer_address` varchar(500) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `ai_visitor_id` varchar(80) DEFAULT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `accessory_orders`
--

INSERT INTO `accessory_orders` (`id`, `product_id`, `customer_name`, `customer_phone`, `customer_address`, `customer_email`, `ai_visitor_id`, `quantity`, `notes`, `status`, `admin_notes`, `confirmed_at`, `created_at`, `updated_at`) VALUES
(1, 22, 'Browser QA Customer', '0909123456', '1 Nguyen Hue, Quan 1', 'browser.qa@example.com', NULL, 2, 'Smoke test Phase 13', 'pending', NULL, NULL, '2026-06-12 08:32:53', '2026-06-12 08:32:53'),
(2, 16, 'Browser Phase13 1781278664980', '0901234567', '12 Nguyen Hue, Quan 1, TP HCM', 'phase13@example.com', NULL, 2, 'Smoke QA Phase 13', 'confirmed', 'Browser QA confirmed Phase 13', '2026-06-12 08:43:13', '2026-06-12 08:37:45', '2026-06-12 08:43:13'),
(3, 16, 'QA Regression 1781280571180', '0909876543', '88 Le Loi, Quan 1, TP HCM', 'qa-regression@example.com', NULL, 3, 'Post-phase regression QA order', 'completed', 'Phase 14 regression QA checked accessory order admin flow 1781445915951', '2026-06-12 09:10:30', '2026-06-12 09:09:33', '2026-06-14 07:05:16'),
(4, 16, 'Anh Thai Final QA', '0901234567', '123 Final QA Street, Ho Chi Minh City', 'final.qa@example.test', 'f94c7712-1ca7-4cab-9455-6cd619b1ba21', 1, 'Final QA Test - accessory order flow', 'pending', NULL, NULL, '2026-06-17 02:35:26', '2026-06-17 02:35:26');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `agent_conversations`
--

CREATE TABLE `agent_conversations` (
  `id` varchar(36) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `agent_conversation_messages`
--

CREATE TABLE `agent_conversation_messages` (
  `id` varchar(36) NOT NULL,
  `conversation_id` varchar(36) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `agent` varchar(255) NOT NULL,
  `role` varchar(25) NOT NULL,
  `content` text NOT NULL,
  `attachments` text NOT NULL,
  `tool_calls` text NOT NULL,
  `tool_results` text NOT NULL,
  `usage` text NOT NULL,
  `meta` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ai_chat_messages`
--

CREATE TABLE `ai_chat_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ai_chat_session_id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(25) NOT NULL,
  `content` text NOT NULL,
  `content_preview` varchar(300) DEFAULT NULL,
  `page_url` text DEFAULT NULL,
  `page_path` varchar(500) DEFAULT NULL,
  `referrer` text DEFAULT NULL,
  `provider` varchar(80) DEFAULT NULL,
  `model` varchar(120) DEFAULT NULL,
  `response_reason` varchar(80) DEFAULT NULL,
  `latency_ms` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ai_chat_messages`
--

INSERT INTO `ai_chat_messages` (`id`, `ai_chat_session_id`, `role`, `content`, `content_preview`, `page_url`, `page_path`, `referrer`, `provider`, `model`, `response_reason`, `latency_ms`, `created_at`, `updated_at`) VALUES
(1, 1, 'user', 'Tư vấn giúp tôi BMW 330i sedan đi làm hằng ngày', 'Tư vấn giúp tôi BMW 330i sedan đi làm hằng ngày', 'http://127.0.0.1:8017/', '/', NULL, NULL, NULL, NULL, NULL, '2026-06-17 01:30:54', '2026-06-17 01:30:54'),
(2, 1, 'assistant', 'Chào quý khách, BMW 330i Sedan là một lựa chọn tuyệt vời cho việc đi làm hàng ngày với sự kết hợp giữa hiệu năng vượt trội và công nghệ dẫn đầu.\n\nThông tin chi tiết về BMW 330i Sedan:\n*   **Giá khởi điểm**: 2.499.000.000 VND\n*   **Mô tả**: Mẫu xe này mang đến trải nghiệm đỉnh cao công xưởng Đức, phù hợp cho những ai tìm kiếm sự sang trọng và vận hành mạnh mẽ trong di chuyển hàng ngày.\n*   **Động cơ**: 2.0L I4 Turbo với công suất tối đa 255 hp.\n\nĐể tìm hiểu thêm về mẫu xe này, quý khách có thể truy cập:\n*   **Chi tiết sản phẩm**: [/catalog/bmw-330i-sedan](/catalog/bmw-330i-sedan)\n\nNếu quý khách muốn trải nghiệm trực tiếp hoặc nhận báo giá, xin vui lòng:\n*   **Đặt lịch lái thử**: [/booking?type=test_drive&product_id=1](/booking?type=test_drive&product_id=1)\n*   **Yêu cầu báo giá**: [/booking?type=quote&product_id=1](/booking?type=quote&product_id=1)\n\nChúng tôi rất sẵn lòng hỗ trợ quý khách.', 'Chào quý khách, BMW 330i Sedan là một lựa chọn tuyệt vời cho việc đi làm hàng ngày với sự kết hợp giữa hiệu năng vượt trội và công nghệ dẫn đầu. Thông tin chi tiết về BMW 330i Sedan: * **Giá khởi điểm**: 2.499.000.000 VN...', 'http://127.0.0.1:8017/', '/', NULL, 'gemini', 'gemini-2.5-flash', 'ok', 4935, '2026-06-17 01:30:54', '2026-06-17 01:30:54'),
(3, 2, 'user', 'toi quan tam BMW 330i', 'toi quan tam BMW 330i', 'http://127.0.0.1:8000/', '/', NULL, NULL, NULL, NULL, NULL, '2026-06-17 02:29:22', '2026-06-17 02:29:22'),
(4, 2, 'assistant', 'Chào anh/chị, anh/chị đang quan tâm đến mẫu **BMW 330i Sedan** phải không ạ?\n\nBMW 330i Sedan thế hệ mới 2025 mang đến trải nghiệm đỉnh cao với hiệu năng vượt trội và công nghệ dẫn đầu. Xe được trang bị động cơ 2.0L I4 Turbo, công suất tối đa 255 mã lực và có giá 2.499.000.000 VND.\n\nAnh/chị có thể tìm hiểu thêm thông tin chi tiết, yêu cầu báo giá hoặc đặt lịch lái thử qua các đường dẫn sau:\n*   **Chi tiết sản phẩm:** /catalog/bmw-330i-sedan\n*   **Yêu cầu báo giá:** /booking?type=quote&product_id=1\n*   **Đặt lịch lái thử:** /booking?type=test_drive&product_id=1\n\nNếu anh/chị muốn so sánh với các mẫu xe khác, vui lòng cho tôi biết thêm ạ.', 'Chào anh/chị, anh/chị đang quan tâm đến mẫu **BMW 330i Sedan** phải không ạ? BMW 330i Sedan thế hệ mới 2025 mang đến trải nghiệm đỉnh cao với hiệu năng vượt trội và công nghệ dẫn đầu. Xe được trang bị động cơ 2.0L I4 Tur...', 'http://127.0.0.1:8000/', '/', NULL, 'gemini', 'gemini-2.5-flash', 'ok', 3728, '2026-06-17 02:29:22', '2026-06-17 02:29:22'),
(5, 2, 'user', 'tim giup toi chiec bmw s1000rr', 'tim giup toi chiec bmw s1000rr', 'http://127.0.0.1:8000/', '/', NULL, NULL, NULL, NULL, NULL, '2026-06-17 02:38:23', '2026-06-17 02:38:23'),
(6, 2, 'assistant', 'Hiện tôi chưa thể phản hồi ngay. Quý khách có thể thử lại sau hoặc gửi yêu cầu tư vấn qua form.', 'Hiện tôi chưa thể phản hồi ngay. Quý khách có thể thử lại sau hoặc gửi yêu cầu tư vấn qua form.', 'http://127.0.0.1:8000/', '/', NULL, 'gemini', 'gemini-2.5-flash', 'provider_error', 13033, '2026-06-17 02:38:23', '2026-06-17 02:38:23'),
(7, 2, 'user', 'tu van BMW Motorrad cho toi', 'tu van BMW Motorrad cho toi', 'http://127.0.0.1:8000/', '/', NULL, NULL, NULL, NULL, NULL, '2026-06-17 02:38:34', '2026-06-17 02:38:34'),
(8, 2, 'assistant', 'Chào mừng quý khách đến với showroom BMW!\n\nVề dòng xe BMW Motorrad, chúng tôi hiện có nhiều mẫu xe đa dạng, từ naked bike linh hoạt đến sportbike hiệu suất cao và adventure bền bỉ. Dưới đây là một số gợi ý nổi bật:\n\n1.  **BMW G310R**: Mẫu naked bike lý tưởng cho đô thị, với hiệu năng vượt trội và công nghệ dẫn đầu.\n    *   Giá từ: 189.000.000 VND\n    *   Xem chi tiết: [BMW G310R](/catalog/bmw-g310r)\n    *   Đặt lịch lái thử: [Lái thử G310R](/booking?type=test_drive&product_id=11)\n\n2.  **BMW S1000RR**: Dòng sportbike đỉnh cao, mang lại trải nghiệm tốc độ và công nghệ hàng đầu.\n    *   Giá từ: 959.000.000 VND\n    *   Xem chi tiết: [BMW S1000RR](/catalog/bmw-s1000rr)\n    *   Đặt lịch lái thử: [Lái thử S1000RR](/booking?type=test_drive&product_id=12)\n\n3.  **BMW R1250GS**: Mẫu xe adventure huyền thoại, sẵn sàng cho mọi hành trình với động cơ Boxer mạnh mẽ.\n    *   Giá từ: 619.000.000 VND\n    *   Xem chi tiết: [BMW R1250GS](/catalog/bmw-r1250gs)\n    *   Đặt lịch lái thử: [Lái thử R1250GS](/booking?type=test_drive&product_id=13)\n\nQuý khách có thể tham khảo thêm các mẫu xe Motorrad khác và đặt lịch tư vấn chuyên sâu tại showroom để chúng tôi có thể hỗ trợ tốt nhất.\n*   Xem toàn bộ dòng xe BMW Motorrad: [Tất cả xe máy BMW Motorrad](/catalog)\n*   Đặt lịch tư vấn: [Đặt lịch tư vấn](/booking?type=consult)', 'Chào mừng quý khách đến với showroom BMW! Về dòng xe BMW Motorrad, chúng tôi hiện có nhiều mẫu xe đa dạng, từ naked bike linh hoạt đến sportbike hiệu suất cao và adventure bền bỉ. Dưới đây là một số gợi ý nổi bật: 1. **B...', 'http://127.0.0.1:8000/', '/', NULL, 'gemini', 'gemini-2.5-flash', 'ok', 5855, '2026-06-17 02:38:34', '2026-06-17 02:38:34'),
(9, 2, 'user', 'tu van vai xe sedan', 'tu van vai xe sedan', 'http://127.0.0.1:8000/', '/', NULL, NULL, NULL, NULL, NULL, '2026-06-17 02:38:55', '2026-06-17 02:38:55'),
(10, 2, 'assistant', 'Chào quý khách, showroom BMW hiện có nhiều mẫu xe sedan nổi bật. Dưới đây là 3 lựa chọn mà quý khách có thể tham khảo, tùy thuộc vào nhu cầu và sở thích về hiệu năng:\n\n1.  **BMW 330i Sedan**: Mẫu sedan thể thao, năng động, phù hợp cho trải nghiệm lái hàng ngày với hiệu năng vượt trội.\n    *   Giá từ: 2.499.000.000 VND\n    *   Xem chi tiết sản phẩm: [/catalog/bmw-330i-sedan](/catalog/bmw-330i-sedan)\n    *   Yêu cầu báo giá: [/booking?type=quote&product_id=1](/booking?type=quote&product_id=1)\n    *   Đăng ký lái thử: [/booking?type=test_drive&product_id=1](/booking?type=test_drive&product_id=1)\n\n2.  **BMW 530i Sedan**: Dòng sedan hạng sang với thiết kế tinh tế, công nghệ tiên tiến và không gian nội thất rộng rãi, mang lại sự thoải mái tối ưu.\n    *   Giá từ: 3.199.000.000 VND\n    *   Xem chi tiết sản phẩm: [/catalog/bmw-530i-sedan](/catalog/bmw-530i-sedan)\n    *   Yêu cầu báo giá: [/booking?type=quote&product_id=2](/booking?type=quote&product_id=2)\n    *   Đăng ký lái thử: [/booking?type=test_drive&product_id=2](/booking?type=test_drive&product_id=2)\n\n3.  **BMW M3 Sedan**: Nếu quý khách tìm kiếm hiệu suất đỉnh cao và trải nghiệm lái thể thao mạnh mẽ, BMW M3 Sedan là lựa chọn hoàn hảo với động cơ M TwinPower Turbo.\n    *   Giá từ: 5.499.000.000 VND\n    *   Xem chi tiết sản phẩm: [/catalog/bmw-m3-sedan](/catalog/bmw-m3-sedan)\n    *   Yêu cầu báo giá: [/booking?type=quote&product_id=6](/booking?type=quote&product_id=6)\n    *   Đăng ký lái thử: [/booking?type=test_', 'Chào quý khách, showroom BMW hiện có nhiều mẫu xe sedan nổi bật. Dưới đây là 3 lựa chọn mà quý khách có thể tham khảo, tùy thuộc vào nhu cầu và sở thích về hiệu năng: 1. **BMW 330i Sedan**: Mẫu sedan thể thao, năng động,...', 'http://127.0.0.1:8000/', '/', NULL, 'gemini', 'gemini-2.5-flash', 'ok', 6372, '2026-06-17 02:38:55', '2026-06-17 02:38:55'),
(11, 2, 'user', 'tu van SUV SAV', 'tu van SUV SAV', 'http://127.0.0.1:8000/', '/', NULL, NULL, NULL, NULL, NULL, '2026-06-17 02:39:13', '2026-06-17 02:39:13'),
(12, 2, 'assistant', 'Chào quý khách, quý khách đang quan tâm đến dòng xe SAV (SUV) của BMW. Hiện tại, showroom chúng tôi có các mẫu xe nổi bật sau:\n\n1.  **BMW X3 M50 xDrive**: Mẫu SAV thể thao với động cơ 3.0L I6 Mild Hybrid, công suất 393 hp. Giá từ 3.999.000.000 VND.\n    *   Xem chi tiết: /catalog/bmw-x3-m50-xdrive\n    *   Đặt lịch lái thử: /booking?type=test_drive&product_id=5\n    *   Yêu cầu báo giá: /booking?type=quote&product_id=5\n\n2.  **BMW X5 M Competition**: Mẫu SAV hiệu năng cao với động cơ 4.4L V8 M TwinPower, công suất 617 hp. Giá từ 6.999.000.000 VND.\n    *   Xem chi tiết: /catalog/bmw-x5-m-competition\n    *   Đặt lịch lái thử: /booking?type=test_drive&product_id=8\n    *   Yêu cầu báo giá: /booking?type=quote&product_id=8\n\n3.  **BMW XM Label**: Mẫu SAV Plug-in Hybrid hiệu suất cực đỉnh với động cơ 4.4L V8 PHEV, công suất 738 hp. Giá từ 12.999.000.000 VND.\n    *   Xem chi tiết: /catalog/bmw-xm-label\n    *   Đặt lịch lái thử: /booking?type=test_drive&product_id=9\n    *   Yêu cầu báo giá: /booking?type=quote&product_id=9\n\nQuý khách có thể tìm hiểu thêm thông tin chi tiết từng mẫu xe hoặc so sánh các mẫu xe này tại đây:\n*   So sánh BMW X3 M50 xDrive và BMW X5 M Competition: /compare?ids=5,8\n*   So sánh BMW X5 M Competition và BMW XM Label: /compare?ids=8,9\n\nNếu quý khách muốn được tư vấn cụ thể hơn hoặc đặt lịch lái thử, vui lòng cho chúng tôi biết.', 'Chào quý khách, quý khách đang quan tâm đến dòng xe SAV (SUV) của BMW. Hiện tại, showroom chúng tôi có các mẫu xe nổi bật sau: 1. **BMW X3 M50 xDrive**: Mẫu SAV thể thao với động cơ 3.0L I6 Mild Hybrid, công suất 393 hp....', 'http://127.0.0.1:8000/', '/', NULL, 'gemini', 'gemini-2.5-flash', 'ok', 6515, '2026-06-17 02:39:13', '2026-06-17 02:39:13'),
(13, 2, 'user', 'tim BMW S1000RR', 'tim BMW S1000RR', 'http://127.0.0.1:8000/', '/', NULL, NULL, NULL, NULL, NULL, '2026-06-17 02:40:42', '2026-06-17 02:40:42'),
(14, 2, 'assistant', 'Chào quý khách, mẫu xe BMW S1000RR hiện có sẵn tại showroom với giá từ **959.000.000 VND**.\n\nĐây là mẫu xe Motorrad thế hệ mới 2025, mang lại hiệu năng vượt trội và công nghệ dẫn đầu.\n\nQuý khách có thể tìm hiểu thêm hoặc thực hiện các bước sau:\n*   **Xem chi tiết sản phẩm:** [BMW S1000RR](https://www.bmw.com/catalog/bmw-s1000rr)\n*   **Đặt lịch lái thử:** [Lái thử BMW S1000RR](https', 'Chào quý khách, mẫu xe BMW S1000RR hiện có sẵn tại showroom với giá từ **959.000.000 VND**. Đây là mẫu xe Motorrad thế hệ mới 2025, mang lại hiệu năng vượt trội và công nghệ dẫn đầu. Quý khách có thể tìm hiểu thêm hoặc t...', 'http://127.0.0.1:8000/', '/', NULL, 'gemini', 'gemini-2.5-flash', 'ok', 5647, '2026-06-17 02:40:42', '2026-06-17 02:40:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ai_chat_sessions`
--

CREATE TABLE `ai_chat_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `visitor_id` varchar(80) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `ip_hash` varchar(64) DEFAULT NULL,
  `user_agent_hash` varchar(64) DEFAULT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(30) DEFAULT NULL,
  `linked_source_type` varchar(40) DEFAULT NULL,
  `linked_source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `link_confidence` varchar(40) DEFAULT NULL,
  `first_seen_at` timestamp NULL DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `message_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_message_preview` varchar(300) DEFAULT NULL,
  `last_intent` varchar(80) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ai_chat_sessions`
--

INSERT INTO `ai_chat_sessions` (`id`, `visitor_id`, `ip_address`, `ip_hash`, `user_agent_hash`, `display_name`, `customer_name`, `customer_email`, `customer_phone`, `linked_source_type`, `linked_source_id`, `link_confidence`, `first_seen_at`, `last_seen_at`, `message_count`, `last_message_preview`, `last_intent`, `status`, `created_at`, `updated_at`) VALUES
(1, '67f10a01-92ac-4801-946b-9ac60d306059', '127.0.0.1', '55df031db20e5613c9298672798736b915b36f76c11c47b0c36b1a4b03fe6593', 'e21ed8cedf14354b9a05b27bd704336fdd6eba17005124f43c7ae692e39187f7', 'Anh Thai Phase 17', 'Anh Thai Phase 17', 'phase17@example.com', '0909001717', 'appointment', 1, 'visitor_id', '2026-06-17 01:30:54', '2026-06-17 01:36:29', 2, 'Tư vấn giúp tôi BMW 330i sedan đi làm hằng ngày', 'sedan', 'converted', '2026-06-17 01:30:54', '2026-06-17 01:36:29'),
(2, 'f94c7712-1ca7-4cab-9455-6cd619b1ba21', '127.0.0.1', '55df031db20e5613c9298672798736b915b36f76c11c47b0c36b1a4b03fe6593', 'b35894746236f546543e0fc5b07215d25387f2857dd41df693a9894da0853da6', 'Anh Thai Final QA', 'Anh Thai Final QA', 'final.qa@example.test', '0901234567', 'appointment', 2, 'visitor_id', '2026-06-17 02:29:22', '2026-06-17 02:40:42', 12, 'tim BMW S1000RR', 'motorbike', 'converted', '2026-06-17 02:29:22', '2026-06-17 02:40:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `appointments`
--

CREATE TABLE `appointments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `guest_phone` varchar(255) DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'test_drive',
  `appointment_date` datetime NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `showroom` varchar(255) DEFAULT NULL,
  `ai_visitor_id` varchar(80) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `guest_name`, `guest_email`, `guest_phone`, `product_id`, `type`, `appointment_date`, `status`, `notes`, `meta_data`, `showroom`, `ai_visitor_id`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Anh Thai Phase 17', 'phase17@example.com', '0909001717', 1, 'consult', '2026-06-20 00:00:00', 'pending', NULL, NULL, NULL, '67f10a01-92ac-4801-946b-9ac60d306059', '2026-06-17 01:36:29', '2026-06-17 01:36:29'),
(2, NULL, 'Anh Thai Final QA', 'final.qa@example.test', '0901234567', 1, 'test_drive', '2026-06-20 00:00:00', 'pending', 'Final QA Test - AI visitor linking check', NULL, NULL, 'f94c7712-1ca7-4cab-9455-6cd619b1ba21', '2026-06-17 02:32:47', '2026-06-17 02:32:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `articles`
--

CREATE TABLE `articles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `body` longtext NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` varchar(320) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `articles`
--

INSERT INTO `articles` (`id`, `user_id`, `title`, `slug`, `category`, `excerpt`, `body`, `cover_image`, `status`, `published_at`, `seo_title`, `seo_description`, `created_at`, `updated_at`) VALUES
(1, 1, 'Browser QA Draft 1781444047469', 'browser-qa-draft-1781444047469', 'su-kien-showroom', 'Draft article created by browser QA.', 'Draft body for browser QA.\r\n\r\nThis should not be public.', NULL, 'draft', NULL, NULL, NULL, '2026-06-14 06:34:09', '2026-06-14 08:02:55'),
(2, 1, 'Browser QA Published 1781444047469', 'browser-qa-published-1781444047469', 'uu-dai-khach-hang', 'Published article created by browser QA.', 'Published body for browser QA.\r\n\r\nThis should be visible on public pages.', NULL, 'draft', NULL, NULL, NULL, '2026-06-14 06:34:11', '2026-06-14 08:02:55'),
(3, 1, 'Ưu đãi mùa hè BMW 2026', 'uu-dai-mua-he-bmw-2026', 'uu-dai-khach-hang', 'Chương trình ưu đãi dành cho khách hàng đặt lịch tại showroom. QA 1781445573444 Đã cập nhật qua HTTP admin QA.', 'Đây là bài viết QA để kiểm tra chức năng Article CMS Phase 14. Nội dung gồm ưu đãi, chương trình bán hàng và thông tin showroom.', NULL, 'published', '2026-06-14 06:59:34', NULL, NULL, '2026-06-14 06:59:33', '2026-06-14 06:59:34'),
(4, 1, 'Bài nháp nội bộ showroom', 'bai-nhap-noi-bo-showroom', 'chuong-trinh-ban-hang', 'Bài nháp dùng cho QA. QA 1781445573444', 'Đây là bài draft để kiểm tra rằng draft không public.', NULL, 'draft', NULL, NULL, NULL, '2026-06-14 06:59:35', '2026-06-14 06:59:35'),
(5, 1, 'Ưu đãi mùa hè BMW 2026 cho khách hàng đặt lịch showroom', 'uu-dai-mua-he-bmw-2026-cho-khach-hang-dat-lich-showroom', 'uu-dai-khach-hang', 'Cập nhật ưu đãi mùa hè dành cho khách hàng đặt lịch tư vấn, lái thử và cấu hình BMW trực tiếp tại showroom.', '## Trải nghiệm ưu đãi theo lịch hẹn\nKhách hàng đặt lịch trước được đội ngũ showroom chuẩn bị xe trưng bày, cấu hình tham khảo và tư vấn tài chính phù hợp với nhu cầu sử dụng.\n\n## Quyền lợi trong thời gian chương trình\nChương trình tập trung vào hỗ trợ tư vấn cá nhân, kiểm tra nhu cầu đổi xe, phụ kiện đi kèm và các lựa chọn bàn giao thuận tiện.\n\n## Cách tham gia\nKhách hàng chọn mẫu xe quan tâm trên catalog, gửi yêu cầu tư vấn hoặc báo giá, sau đó cố vấn showroom sẽ xác nhận khung giờ phù hợp.', 'images/cars/330i/lifestyle-showroom.png', 'published', '2026-06-13 08:02:55', 'Ưu đãi mùa hè BMW 2026 cho khách hàng đặt lịch showroom', 'Cập nhật ưu đãi mùa hè dành cho khách hàng đặt lịch tư vấn, lái thử và cấu hình BMW trực tiếp tại showroom.', '2026-06-14 08:02:55', '2026-06-14 08:02:55'),
(6, 1, 'Gói hỗ trợ tài chính khi sở hữu BMW mới', 'goi-ho-tro-tai-chinh-khi-so-huu-bmw-moi', 'uu-dai-khach-hang', 'Các điểm cần chuẩn bị khi trao đổi phương án tài chính, đặt cọc và kế hoạch bàn giao BMW mới.', '## Chuẩn bị ngân sách sở hữu\nMột phương án tài chính tốt cần làm rõ ngân sách ban đầu, dòng tiền hàng tháng, thời hạn sở hữu dự kiến và nhu cầu đổi xe trong tương lai.\n\n## Tư vấn theo từng dòng xe\nSedan, SUV, xe điện và các mẫu M Performance có cấu trúc chi phí khác nhau, vì vậy showroom sẽ hỗ trợ so sánh theo cấu hình thực tế.\n\n## Minh bạch trước khi đặt xe\nKhách hàng nên yêu cầu báo giá chi tiết, thời gian giao xe dự kiến, điều kiện bảo hành và phụ kiện mong muốn trước khi xác nhận đặt cọc.', 'images/cars/530i.png', 'published', '2026-06-12 08:02:55', 'Gói hỗ trợ tài chính khi sở hữu BMW mới', 'Các điểm cần chuẩn bị khi trao đổi phương án tài chính, đặt cọc và kế hoạch bàn giao BMW mới.', '2026-06-14 08:02:55', '2026-06-14 08:02:55'),
(7, 1, 'Chương trình lái thử và tư vấn cấu hình BMW tại showroom', 'chuong-trinh-lai-thu-va-tu-van-cau-hinh-bmw-tai-showroom', 'chuong-trinh-ban-hang', 'Quy trình đặt lịch lái thử giúp khách hàng cảm nhận vận hành, khoang lái và công nghệ hỗ trợ người lái của BMW.', '## Lái thử có mục tiêu rõ ràng\nTrước buổi hẹn, khách hàng nên xác định nhu cầu chính như đi phố, đi xa, gia đình, hiệu năng hoặc trải nghiệm lái thể thao.\n\n## Tư vấn cấu hình sau lái thử\nSau khi trải nghiệm, cố vấn showroom sẽ gợi ý phiên bản, màu sắc, trang bị và phụ kiện dựa trên phản hồi thực tế của khách hàng.\n\n## Bước tiếp theo\nNếu mẫu xe phù hợp, khách hàng có thể yêu cầu báo giá, so sánh thêm phiên bản hoặc giữ lịch gặp cố vấn để hoàn thiện cấu hình.', 'images/cars/bmw-m3-sedan/lifestyle-showroom.png', 'published', '2026-06-11 08:02:55', 'Chương trình lái thử và tư vấn cấu hình BMW tại showroom', 'Quy trình đặt lịch lái thử giúp khách hàng cảm nhận vận hành, khoang lái và công nghệ hỗ trợ người lái của BMW.', '2026-06-14 08:02:55', '2026-06-14 08:02:55'),
(8, 1, 'Quy trình đặt xe BMW từ chọn cấu hình đến bàn giao', 'quy-trinh-dat-xe-bmw-tu-chon-cau-hinh-den-ban-giao', 'chuong-trinh-ban-hang', 'Tóm tắt các bước quan trọng trong hành trình chọn xe, xác nhận cấu hình, đặt cọc và nhận bàn giao BMW.', '## Chọn đúng nhu cầu trước khi chọn cấu hình\nKhách hàng nên bắt đầu bằng mục đích sử dụng, số người thường đi cùng, thói quen di chuyển và mức độ ưu tiên giữa tiện nghi, hiệu năng, công nghệ.\n\n## Xác nhận thông tin thương mại\nTrước khi đặt cọc, showroom cần thống nhất giá, màu xe, thời gian bàn giao dự kiến, phụ kiện và các hồ sơ liên quan.\n\n## Bàn giao tại showroom\nKhi nhận xe, khách hàng được hướng dẫn vận hành, kết nối My BMW, kiểm tra ngoại thất, nội thất và lịch bảo dưỡng đầu tiên.', 'images/cars/bmw-x5-m-competition/hero-front-three-quarter.png', 'published', '2026-06-10 08:02:55', 'Quy trình đặt xe BMW từ chọn cấu hình đến bàn giao', 'Tóm tắt các bước quan trọng trong hành trình chọn xe, xác nhận cấu hình, đặt cọc và nhận bàn giao BMW.', '2026-06-14 08:02:55', '2026-06-14 08:02:55'),
(9, 1, 'Tuần lễ trải nghiệm BMW Performance tại showroom', 'tuan-le-trai-nghiem-bmw-performance-tai-showroom', 'su-kien-showroom', 'Sự kiện dành cho khách hàng muốn khám phá phong cách vận hành thể thao và thiết kế M Performance.', '## Không gian dành cho người yêu cảm giác lái\nTuần lễ Performance tập trung vào thiết kế khí động học, vô-lăng, ghế ngồi, hệ dẫn động và các chi tiết giúp BMW khác biệt khi vận hành.\n\n## Hoạt động tại showroom\nKhách tham dự có thể xem xe trưng bày, đặt lịch tư vấn cấu hình, tìm hiểu phụ kiện M Performance và nhận lịch lái thử phù hợp.\n\n## Ai nên tham gia\nSự kiện phù hợp với khách hàng đang cân nhắc sedan thể thao, coupe, SUV hiệu năng cao hoặc muốn nâng cấp trải nghiệm lái hiện tại.', 'images/cars/bmw-m4-coupe/lifestyle-showroom.png', 'published', '2026-06-09 08:02:55', 'Tuần lễ trải nghiệm BMW Performance tại showroom', 'Sự kiện dành cho khách hàng muốn khám phá phong cách vận hành thể thao và thiết kế M Performance.', '2026-06-14 08:02:55', '2026-06-14 08:02:55'),
(10, 1, 'Sự kiện giới thiệu dải sản phẩm BMW mới', 'su-kien-gioi-thieu-dai-san-pham-bmw-moi', 'su-kien-showroom', 'Showroom tổ chức tuần trưng bày các mẫu BMW mới, kết hợp tư vấn sản phẩm và đặt lịch trải nghiệm.', '## Dải sản phẩm được trưng bày theo nhu cầu\nKhách hàng có thể tham khảo sedan, SUV, xe điện, Motorrad và phụ kiện chính hãng trong cùng một không gian.\n\n## Trải nghiệm có cố vấn đồng hành\nĐội ngũ showroom giúp giải thích khác biệt giữa các dòng xe, gợi ý cấu hình và chuẩn bị lộ trình tư vấn sau sự kiện.\n\n## Đặt lịch trước để tối ưu thời gian\nViệc đặt hẹn giúp showroom chuẩn bị xe quan tâm, tài liệu cấu hình và khung giờ trao đổi riêng tư hơn.', 'images/cars/bmw-x3-m50-xdrive/lifestyle-showroom.png', 'published', '2026-06-08 08:02:55', 'Sự kiện giới thiệu dải sản phẩm BMW mới', 'Showroom tổ chức tuần trưng bày các mẫu BMW mới, kết hợp tư vấn sản phẩm và đặt lịch trải nghiệm.', '2026-06-14 08:02:55', '2026-06-14 08:02:55'),
(11, 1, 'Những điểm cần chú ý khi chọn BMW sedan cho gia đình', 'nhung-diem-can-chu-y-khi-chon-bmw-sedan-cho-gia-dinh', 'trai-nghiem-bmw', 'Gợi ý đánh giá không gian, tiện nghi, vận hành và công nghệ an toàn khi chọn sedan BMW cho nhu cầu gia đình.', '## Không gian và thói quen sử dụng\nMột chiếc sedan gia đình cần cân bằng ghế ngồi, khoang hành lý, khả năng đi phố và sự thoải mái trong những chuyến xa.\n\n## Cảm giác lái vẫn là điểm khác biệt\nBMW sedan phù hợp với khách hàng muốn xe lịch lãm hằng ngày nhưng vẫn có phản hồi lái chính xác và nhiều cảm xúc.\n\n## Công nghệ hỗ trợ nên kiểm tra kỹ\nKhách hàng nên trải nghiệm màn hình, camera, hỗ trợ đỗ xe, điều hòa, kết nối điện thoại và các chế độ lái trong buổi xem xe.', 'images/cars/330i/cockpit-interior.png', 'published', '2026-06-07 08:02:55', 'Những điểm cần chú ý khi chọn BMW sedan cho gia đình', 'Gợi ý đánh giá không gian, tiện nghi, vận hành và công nghệ an toàn khi chọn sedan BMW cho nhu cầu gia đình.', '2026-06-14 08:02:55', '2026-06-14 08:02:55'),
(12, 1, 'Trải nghiệm khác biệt giữa BMW 3 Series, 5 Series và dòng M', 'trai-nghiem-khac-biet-giua-bmw-3-series-5-series-va-dong-m', 'trai-nghiem-bmw', 'So sánh nhanh tinh thần sử dụng giữa 3 Series linh hoạt, 5 Series sang trọng và các mẫu M hiệu năng cao.', '## 3 Series cho người thích sự linh hoạt\nBMW 3 Series tạo cảm giác gọn, nhanh và phù hợp với khách hàng muốn cân bằng giữa di chuyển hằng ngày và cảm xúc lái.\n\n## 5 Series cho hành trình sang trọng hơn\n5 Series tập trung vào không gian, tiện nghi, công nghệ và sự điềm tĩnh trên những quãng đường dài.\n\n## Dòng M dành cho hiệu năng rõ rệt\nCác mẫu M ưu tiên sức mạnh, kiểm soát thân xe, âm thanh vận hành và thiết kế thể thao, phù hợp với người lái nhiều kinh nghiệm.', 'images/cars/bmw-m5-touring/hero-front-three-quarter.png', 'published', '2026-06-06 08:02:55', 'Trải nghiệm khác biệt giữa BMW 3 Series, 5 Series và dòng M', 'So sánh nhanh tinh thần sử dụng giữa 3 Series linh hoạt, 5 Series sang trọng và các mẫu M hiệu năng cao.', '2026-06-14 08:02:55', '2026-06-14 08:02:55'),
(13, 1, 'Lịch bảo dưỡng BMW và những mốc quan trọng', 'lich-bao-duong-bmw-va-nhung-moc-quan-trong', 'dich-vu-hau-mai', 'Những mốc bảo dưỡng cần lưu ý để xe BMW vận hành ổn định, an toàn và giữ giá trị sử dụng lâu dài.', '## Theo dõi lịch hẹn chủ động\nKhách hàng nên lưu lịch kiểm tra định kỳ, tình trạng lốp, dầu, phanh, lọc gió và các cảnh báo hiển thị trên xe.\n\n## Bảo dưỡng chính hãng giúp kiểm soát rủi ro\nKỹ thuật viên và phụ tùng chính hãng giúp đảm bảo xe được kiểm tra theo tiêu chuẩn phù hợp với từng dòng BMW.\n\n## Khi nào cần đặt lịch ngay\nNếu xe xuất hiện cảnh báo, âm thanh bất thường, rung lắc, giảm hiệu quả phanh hoặc điều hòa yếu, khách hàng nên đặt lịch kiểm tra sớm.', 'images/cars/bmw-550e-xdrive-sedan/design-detail.png', 'published', '2026-06-05 08:02:55', 'Lịch bảo dưỡng BMW và những mốc quan trọng', 'Những mốc bảo dưỡng cần lưu ý để xe BMW vận hành ổn định, an toàn và giữ giá trị sử dụng lâu dài.', '2026-06-14 08:02:55', '2026-06-14 08:02:55'),
(14, 1, 'Phụ kiện chính hãng BMW giúp nâng cấp trải nghiệm lái', 'phu-kien-chinh-hang-bmw-giup-nang-cap-trai-nghiem-lai', 'dich-vu-hau-mai', 'Các nhóm phụ kiện đáng cân nhắc cho nội thất, bảo vệ xe, camera hành trình và phong cách M Performance.', '## Phụ kiện nên phù hợp với thói quen sử dụng\nKhách hàng thường xuyên đi xa có thể ưu tiên camera hành trình, thảm lót, phụ kiện chứa đồ hoặc các chi tiết bảo vệ nội thất.\n\n## Cá nhân hóa phong cách BMW\nNhóm M Performance giúp tăng cảm giác thể thao qua chi tiết carbon, mâm xe, vô-lăng hoặc các điểm nhấn ngoại thất.\n\n## Kiểm tra tương thích trước khi đặt hàng\nShowroom sẽ xác nhận phụ kiện phù hợp với đời xe, phiên bản và nhu cầu lắp đặt trước khi chốt đơn.', 'images/accessories/tham-lot-san-m-performance/lifestyle-use.png', 'published', '2026-06-04 08:02:55', 'Phụ kiện chính hãng BMW giúp nâng cấp trải nghiệm lái', 'Các nhóm phụ kiện đáng cân nhắc cho nội thất, bảo vệ xe, camera hành trình và phong cách M Performance.', '2026-06-14 08:02:55', '2026-06-14 08:02:55'),
(15, 1, 'Cập nhật xe mới về showroom BMW', 'cap-nhat-xe-moi-ve-showroom-bmw', 'tin-tuc-showroom', 'Thông tin các mẫu xe mới, lịch trưng bày và cách đặt lịch xem xe trực tiếp tại showroom.', '## Xe mới được cập nhật theo từng đợt\nShowroom liên tục bổ sung xe trưng bày, xe lái thử và các cấu hình đang được khách hàng quan tâm.\n\n## Cách kiểm tra mẫu xe đang có\nKhách hàng có thể xem catalog public, mở trang chi tiết sản phẩm và gửi yêu cầu tư vấn để được xác nhận tình trạng xe.\n\n## Lợi ích khi đặt lịch xem xe\nĐặt lịch giúp showroom chuẩn bị xe, không gian tư vấn và tài liệu cấu hình trước khi khách hàng đến.', 'images/cars/hero.png', 'published', '2026-06-03 08:02:55', 'Cập nhật xe mới về showroom BMW', 'Thông tin các mẫu xe mới, lịch trưng bày và cách đặt lịch xem xe trực tiếp tại showroom.', '2026-06-14 08:02:55', '2026-06-14 08:02:55'),
(16, 1, 'Không gian tư vấn và trải nghiệm BMW trực tuyến', 'khong-gian-tu-van-va-trai-nghiem-bmw-truc-tuyen', 'tin-tuc-showroom', 'Showroom kết hợp catalog, landing page sản phẩm, so sánh và form tư vấn để hỗ trợ khách hàng trước khi đến trực tiếp.', '## Khám phá trước khi đến showroom\nKhách hàng có thể xem ảnh, thông số, CTA báo giá, so sánh xe và phụ kiện ngay trên website trước buổi hẹn.\n\n## Tư vấn trực tuyến giúp tiết kiệm thời gian\nThông tin từ form public giúp cố vấn hiểu nhu cầu trước, từ đó chuẩn bị cấu hình, giá và phương án trải nghiệm sát hơn.\n\n## Hành trình liền mạch\nTừ trang chủ, catalog, bài viết đến form tư vấn, toàn bộ luồng được thiết kế để đưa khách hàng đến cuộc hẹn showroom rõ ràng hơn.', 'images/cars/bmw-i4-m60-gran-coupe/lifestyle-showroom.png', 'published', '2026-06-02 08:02:55', 'Không gian tư vấn và trải nghiệm BMW trực tuyến', 'Showroom kết hợp catalog, landing page sản phẩm, so sánh và form tư vấn để hỗ trợ khách hàng trước khi đến trực tiếp.', '2026-06-14 08:02:55', '2026-06-14 08:02:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `logo`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Sedan', 'sedan', NULL, 'Xe sedan thể thao hạng sang (3 Series, 5 Series, 7 Series).', '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(2, 'SAV (SUV)', 'sav', NULL, 'Sports Activity Vehicle - Các dòng X của BMW (X3, X5, X7).', '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(3, 'Coupe / Gran Coupe', 'coupe', NULL, 'Xe thể thao 2 cửa hoặc 4 cửa lai coupe của BMW (4 Series, 8 Series).', '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(4, 'M Performance', 'm-performance', NULL, 'Dòng xe hiệu năng cao đích thực (M3, M4, M5, M8, v.v.).', '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(5, 'BMW i (Thuần điện)', 'bmw-i', NULL, 'Dòng xe điện của tương lai (iX, i4, i7).', '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(6, 'Motorrad', 'motorrad', NULL, 'Phân khúc xe mô tô BMW Motorrad (S1000RR, GS, v.v.).', '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(7, 'Phụ kiện Ô tô', 'phu-kien-o-to', NULL, 'Phụ kiện chính hãng BMW dành cho ô tô: thảm lót, camera hành trình, bọc vô lăng, v.v.', '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(8, 'Phụ kiện Xe máy', 'phu-kien-xe-may', NULL, 'Phụ kiện chính hãng BMW Motorrad: thùng nhôm, mũ bảo hiểm, ốp carbon, v.v.', '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_04_21_081545_create_categories_table', 1),
(5, '2026_04_21_081545_create_products_table', 1),
(6, '2026_04_21_081600_create_product_images_table', 1),
(7, '2026_04_22_085207_add_is_active_to_products_table', 1),
(8, '2026_04_22_101055_create_appointments_table', 1),
(9, '2026_05_01_065239_add_meta_data_to_appointments_table', 2),
(10, '2026_06_12_151713_create_accessory_orders_table', 3),
(11, '2026_06_14_130821_create_articles_table', 4),
(12, '2026_06_14_144558_create_site_settings_table', 5),
(13, '2026_06_15_152329_create_agent_conversations_table', 6),
(14, '2026_06_17_080643_create_ai_chat_sessions_table', 7),
(15, '2026_06_17_080644_create_ai_chat_messages_table', 7),
(16, '2026_06_17_080651_add_ai_visitor_id_to_customer_touchpoints', 7);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `price` bigint(20) UNSIGNED NOT NULL,
  `deposit_amount` bigint(20) UNSIGNED NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `specifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`specifications`)),
  `description` text DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `type`, `price`, `deposit_amount`, `stock`, `specifications`, `description`, `is_featured`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'BMW 330i Sedan', 'bmw-330i-sedan', 'car', 2499000000, 100000000, 10, '{\"Engine\":\"2.0L I4 Turbo\",\"Horsepower\":\"255 hp\",\"Torque\":\"295 lb-ft\",\"0-60mph\":\"5.6s\",\"Length_Width_Height\":\"4,713 x 1,827 x 1,440 mm\",\"Wheelbase\":\"2,851 mm\",\"Curb_Weight\":\"1,650 kg\",\"Fuel_Tank_Cap\":\"59 l\",\"Max_Power_RPM\":\"255 HP @ 5,000-6,500 rpm\",\"Max_Torque_RPM\":\"400 Nm @ 1,550-4,400 rpm\",\"Drive_Type\":\"RWD\",\"Transmission_Type\":\"8-Speed Steptronic Sport\",\"Zero_To_Hundred\":\"5.8 gi\\u00e2y\",\"Top_Speed_KMH\":\"250 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW 330i Sedan thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(2, 1, 'BMW 530i Sedan', 'bmw-530i-sedan', 'car', 3199000000, 150000000, 10, '{\"Engine\":\"2.0L I4 Turbo (Mild Hybrid)\",\"Horsepower\":\"255 hp\",\"Torque\":\"295 lb-ft\",\"0-60mph\":\"5.9s\",\"Length_Width_Height\":\"5,060 x 1,900 x 1,515 mm\",\"Wheelbase\":\"2,995 mm\",\"Curb_Weight\":\"1,830 kg\",\"Fuel_Tank_Cap\":\"60 l\",\"Max_Power_RPM\":\"255 HP @ 4,700-6,500 rpm\",\"Max_Torque_RPM\":\"400 Nm @ 1,600-4,500 rpm\",\"Drive_Type\":\"RWD\",\"Transmission_Type\":\"8-Speed Steptronic\",\"Zero_To_Hundred\":\"6.2 gi\\u00e2y\",\"Top_Speed_KMH\":\"250 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW 530i Sedan thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(3, 1, 'BMW 550e xDrive Sedan', 'bmw-550e-xdrive-sedan', 'car', 4399000000, 200000000, 10, '{\"Engine\":\"3.0L I6 PHEV\",\"Horsepower\":\"483 hp (Combined)\",\"Torque\":\"516 lb-ft (Combined)\",\"0-60mph\":\"4.0s\",\"Length_Width_Height\":\"5,060 x 1,900 x 1,515 mm\",\"Wheelbase\":\"2,995 mm\",\"Curb_Weight\":\"2,155 kg\",\"Fuel_Tank_Cap\":\"60 l\",\"Max_Power_RPM\":\"483 HP @ 5,000-6,500 rpm\",\"Max_Torque_RPM\":\"700 Nm @ 1,750-4,700 rpm\",\"Drive_Type\":\"BMW xDrive (AWD)\",\"Transmission_Type\":\"8-Speed Steptronic Sport\",\"Zero_To_Hundred\":\"4.3 gi\\u00e2y\",\"Top_Speed_KMH\":\"250 km\\/h (E-mode: 140 km\\/h)\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW 550e xDrive Sedan thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 0, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(4, 5, 'BMW i4 M60 Gran Coupe', 'bmw-i4-m60-gran-coupe', 'car', 4599000000, 100000000, 10, '{\"Engine\":\"Dual Electric Motors\",\"Horsepower\":\"536 hp\",\"Torque\":\"586 lb-ft\",\"0-60mph\":\"3.7s\",\"Length_Width_Height\":\"4,783 x 1,852 x 1,448 mm\",\"Wheelbase\":\"2,856 mm\",\"Curb_Weight\":\"2,290 kg\",\"Fuel_Tank_Cap\":\"83.9 kWh (Battery)\",\"Max_Power_RPM\":\"536 HP\",\"Max_Torque_RPM\":\"795 Nm\",\"Drive_Type\":\"Electric xDrive\",\"Transmission_Type\":\"Single-speed fixed ratio\",\"Zero_To_Hundred\":\"3.9 gi\\u00e2y\",\"Top_Speed_KMH\":\"225 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW i4 M60 Gran Coupe thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(5, 2, 'BMW X3 M50 xDrive', 'bmw-x3-m50-xdrive', 'car', 3999000000, 150000000, 10, '{\"Engine\":\"3.0L I6 Mild Hybrid\",\"Horsepower\":\"393 hp\",\"Torque\":\"428 lb-ft\",\"0-60mph\":\"4.4s\",\"Length_Width_Height\":\"4,755 x 1,920 x 1,660 mm\",\"Wheelbase\":\"2,865 mm\",\"Curb_Weight\":\"2,050 kg\",\"Fuel_Tank_Cap\":\"65 l\",\"Max_Power_RPM\":\"393 HP @ 5,200-6,250 rpm\",\"Max_Torque_RPM\":\"580 Nm @ 1,900-4,800 rpm\",\"Drive_Type\":\"BMW xDrive (AWD)\",\"Transmission_Type\":\"8-Speed Steptronic Sport\",\"Zero_To_Hundred\":\"4.6 gi\\u00e2y\",\"Top_Speed_KMH\":\"250 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW X3 M50 xDrive thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(6, 4, 'BMW M3 Sedan', 'bmw-m3-sedan', 'car', 5499000000, 250000000, 10, '{\"Engine\":\"3.0L I6 M TwinPower\",\"Horsepower\":\"473 hp\",\"Torque\":\"406 lb-ft\",\"0-60mph\":\"4.1s\",\"Length_Width_Height\":\"4,794 x 1,903 x 1,433 mm\",\"Wheelbase\":\"2,857 mm\",\"Curb_Weight\":\"1,780 kg\",\"Fuel_Tank_Cap\":\"59 l\",\"Max_Power_RPM\":\"473 HP @ 6,250 rpm\",\"Max_Torque_RPM\":\"550 Nm @ 2,650-6,130 rpm\",\"Drive_Type\":\"RWD\",\"Transmission_Type\":\"6-Speed Manual \\/ 8-Speed M Steptronic\",\"Zero_To_Hundred\":\"4.2 gi\\u00e2y\",\"Top_Speed_KMH\":\"290 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW M3 Sedan thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(7, 3, 'BMW M4 Coupe', 'bmw-m4-coupe', 'car', 5599000000, 250000000, 10, '{\"Engine\":\"3.0L I6 M TwinPower\",\"Horsepower\":\"473 hp\",\"Torque\":\"406 lb-ft\",\"0-60mph\":\"4.1s\",\"Length_Width_Height\":\"4,794 x 1,887 x 1,393 mm\",\"Wheelbase\":\"2,857 mm\",\"Curb_Weight\":\"1,775 kg\",\"Fuel_Tank_Cap\":\"59 l\",\"Max_Power_RPM\":\"473 HP @ 6,250 rpm\",\"Max_Torque_RPM\":\"550 Nm @ 2,650-6,130 rpm\",\"Drive_Type\":\"RWD\",\"Transmission_Type\":\"6-Speed Manual \\/ 8-Speed M Steptronic\",\"Zero_To_Hundred\":\"4.2 gi\\u00e2y\",\"Top_Speed_KMH\":\"290 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW M4 Coupe thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(8, 2, 'BMW X5 M Competition', 'bmw-x5-m-competition', 'car', 6999000000, 200000000, 10, '{\"Engine\":\"4.4L V8 M TwinPower\",\"Horsepower\":\"617 hp\",\"Torque\":\"553 lb-ft\",\"0-60mph\":\"3.7s\",\"Length_Width_Height\":\"4,948 x 2,015 x 1,762 mm\",\"Wheelbase\":\"2,972 mm\",\"Curb_Weight\":\"2,400 kg\",\"Fuel_Tank_Cap\":\"83 l\",\"Max_Power_RPM\":\"617 HP @ 6,000 rpm\",\"Max_Torque_RPM\":\"750 Nm @ 1,800-5,800 rpm\",\"Drive_Type\":\"M xDrive (AWD)\",\"Transmission_Type\":\"8-Speed M Steptronic\",\"Zero_To_Hundred\":\"3.9 gi\\u00e2y\",\"Top_Speed_KMH\":\"290 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW X5 M Competition thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 0, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(9, 2, 'BMW XM Label', 'bmw-xm-label', 'car', 12999000000, 500000000, 10, '{\"Engine\":\"4.4L V8 PHEV\",\"Horsepower\":\"738 hp\",\"Torque\":\"738 lb-ft\",\"0-60mph\":\"3.6s\",\"Length_Width_Height\":\"5,110 x 2,005 x 1,755 mm\",\"Wheelbase\":\"3,105 mm\",\"Curb_Weight\":\"2,710 kg\",\"Fuel_Tank_Cap\":\"69 l\",\"Max_Power_RPM\":\"738 HP @ 5,600 rpm\",\"Max_Torque_RPM\":\"1,000 Nm @ 1,800-5,400 rpm\",\"Drive_Type\":\"M xDrive (AWD)\",\"Transmission_Type\":\"8-Speed M Steptronic\",\"Zero_To_Hundred\":\"3.8 gi\\u00e2y\",\"Top_Speed_KMH\":\"290 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW XM Label thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 0, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(10, 4, 'BMW M5 Touring', 'bmw-m5-touring', 'car', 6599000000, 100000000, 10, '{\"Engine\":\"4.4L V8 M Hybrid\",\"Horsepower\":\"717 hp\",\"Torque\":\"738 lb-ft\",\"0-60mph\":\"3.5s\",\"Length_Width_Height\":\"5,096 x 1,970 x 1,516 mm\",\"Wheelbase\":\"3,006 mm\",\"Curb_Weight\":\"2,470 kg\",\"Fuel_Tank_Cap\":\"60 l\",\"Max_Power_RPM\":\"717 HP @ 5,600-6,500 rpm\",\"Max_Torque_RPM\":\"1,000 Nm @ 1,800-5,400 rpm\",\"Drive_Type\":\"M xDrive (AWD)\",\"Transmission_Type\":\"8-Speed M Steptronic\",\"Zero_To_Hundred\":\"3.6 gi\\u00e2y\",\"Top_Speed_KMH\":\"305 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW M5 Touring thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(11, 6, 'BMW G310R', 'bmw-g310r', 'motorbike', 189000000, 10000000, 10, '{\"Engine\":\"1 xy-lanh, 313cc, DOHC\",\"Horsepower\":\"34 hp\",\"Torque\":\"28 Nm\",\"0-60mph\":\"6.8s\",\"Length_Width_Height\":\"2005 x 849 x 1080 mm\",\"Wheelbase\":\"1374 mm\",\"Curb_Weight\":\"164 kg\",\"Fuel_Tank_Cap\":\"11 l\",\"Max_Power_RPM\":\"34 HP @ 9500 rpm\",\"Max_Torque_RPM\":\"28 Nm @ 7500 rpm\",\"Drive_Type\":\"X\\u00edch\",\"Transmission_Type\":\"6 c\\u1ea5p\",\"Zero_To_Hundred\":\"7.0 gi\\u00e2y\",\"Top_Speed_KMH\":\"143 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW G310R thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(12, 6, 'BMW S1000RR', 'bmw-s1000rr', 'motorbike', 959000000, 50000000, 10, '{\"Engine\":\"4 xy-lanh, 999cc, ShiftCam\",\"Horsepower\":\"205 hp\",\"Torque\":\"113 Nm\",\"0-60mph\":\"2.7s\",\"Length_Width_Height\":\"2073 x 826 x 1151 mm\",\"Wheelbase\":\"1441 mm\",\"Curb_Weight\":\"197 kg\",\"Fuel_Tank_Cap\":\"16.5 l\",\"Max_Power_RPM\":\"205 HP @ 13500 rpm\",\"Max_Torque_RPM\":\"113 Nm @ 11000 rpm\",\"Drive_Type\":\"X\\u00edch\",\"Transmission_Type\":\"6 c\\u1ea5p\",\"Zero_To_Hundred\":\"2.8 gi\\u00e2y\",\"Top_Speed_KMH\":\"299 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW S1000RR thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(13, 6, 'BMW R1250GS', 'bmw-r1250gs', 'motorbike', 619000000, 30000000, 10, '{\"Engine\":\"Boxer 2 xy-lanh, 1254cc\",\"Horsepower\":\"136 hp\",\"Torque\":\"143 Nm\",\"0-60mph\":\"3.4s\",\"Length_Width_Height\":\"2207 x 952 x 1430 mm\",\"Wheelbase\":\"1525 mm\",\"Curb_Weight\":\"249 kg\",\"Fuel_Tank_Cap\":\"20 l\",\"Max_Power_RPM\":\"136 HP @ 7750 rpm\",\"Max_Torque_RPM\":\"143 Nm @ 6250 rpm\",\"Drive_Type\":\"Tr\\u1ee5c (shaft drive)\",\"Transmission_Type\":\"6 c\\u1ea5p\",\"Zero_To_Hundred\":\"3.5 gi\\u00e2y\",\"Top_Speed_KMH\":\"200 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW R1250GS thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(14, 6, 'BMW F900R', 'bmw-f900r', 'motorbike', 459000000, 20000000, 10, '{\"Engine\":\"2 xy-lanh, 895cc\",\"Horsepower\":\"105 hp\",\"Torque\":\"92 Nm\",\"0-60mph\":\"3.7s\",\"Length_Width_Height\":\"2140 x 815 x 1130 mm\",\"Wheelbase\":\"1518 mm\",\"Curb_Weight\":\"211 kg\",\"Fuel_Tank_Cap\":\"13 l\",\"Max_Power_RPM\":\"105 HP @ 8500 rpm\",\"Max_Torque_RPM\":\"92 Nm @ 6500 rpm\",\"Drive_Type\":\"X\\u00edch\",\"Transmission_Type\":\"6 c\\u1ea5p\",\"Zero_To_Hundred\":\"3.8 gi\\u00e2y\",\"Top_Speed_KMH\":\"200 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW F900R thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 0, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(15, 6, 'BMW K1600GT', 'bmw-k1600gt', 'motorbike', 1200000000, 50000000, 10, '{\"Engine\":\"6 xy-lanh th\\u1eb3ng h\\u00e0ng, 1649cc\",\"Horsepower\":\"160 hp\",\"Torque\":\"180 Nm\",\"0-60mph\":\"3.3s\",\"Length_Width_Height\":\"2340 x 1000 x 1460 mm\",\"Wheelbase\":\"1618 mm\",\"Curb_Weight\":\"343 kg\",\"Fuel_Tank_Cap\":\"26.5 l\",\"Max_Power_RPM\":\"160 HP @ 6750 rpm\",\"Max_Torque_RPM\":\"180 Nm @ 5250 rpm\",\"Drive_Type\":\"Tr\\u1ee5c\",\"Transmission_Type\":\"6 c\\u1ea5p\",\"Zero_To_Hundred\":\"3.4 gi\\u00e2y\",\"Top_Speed_KMH\":\"200 km\\/h\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu BMW K1600GT thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 0, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(16, 7, 'Thảm lót sàn M Performance', 'tham-lot-san-m-performance', 'accessory', 3500000, 0, 10, '{\"Material\":\"Cao su nguy\\u00ean kh\\u1ed1i ch\\u1ed1ng tr\\u01a1n\",\"Compatibility\":\"BMW 3 Series \\/ 5 Series \\/ X3 \\/ X5\",\"Weight\":\"2.5 kg\\/b\\u1ed9\",\"Warranty\":\"12 th\\u00e1ng\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu Thảm lót sàn M Performance thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(17, 7, 'Camera hành trình BMW Advanced Eye 3.0', 'camera-hanh-trinh-bmw-advanced-eye-30', 'accessory', 12500000, 0, 10, '{\"Resolution\":\"4K Ultra HD\",\"Field_of_View\":\"170\\u00b0 g\\u00f3c r\\u1ed9ng\",\"Storage\":\"64GB MicroSD (included)\",\"Features\":\"GPS, G-Sensor, Night Vision, Parking Mode\",\"Warranty\":\"24 th\\u00e1ng\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu Camera hành trình BMW Advanced Eye 3.0 thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(18, 7, 'Bọc vô lăng M Performance Carbon', 'boc-vo-lang-m-performance-carbon', 'accessory', 8900000, 0, 10, '{\"Material\":\"Alcantara + Carbon Fiber\",\"Compatibility\":\"BMW M3 \\/ M4 \\/ M5 \\/ M8\",\"Weight\":\"0.8 kg\",\"Color\":\"Black \\/ Red Stitching\",\"Warranty\":\"12 th\\u00e1ng\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu Bọc vô lăng M Performance Carbon thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 0, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(19, 7, 'Bộ vành BMW M Performance Y-Spoke 20\"', 'bo-vanh-bmw-m-performance-y-spoke-20', 'accessory', 45000000, 5000000, 10, '{\"Size\":\"20 x 8.5J (Front) \\/ 20 x 9.5J (Rear)\",\"Material\":\"Forged Aluminum Alloy\",\"Finish\":\"Jet Black \\/ Polished\",\"Compatibility\":\"BMW 3 Series (G20) \\/ 4 Series (G22)\",\"Weight\":\"9.2 kg\\/v\\u00e0nh\",\"Warranty\":\"24 th\\u00e1ng\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu Bộ vành BMW M Performance Y-Spoke 20\" thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(20, 7, 'Bộ đèn LED nội thất Ambient Light Pro', 'bo-den-led-noi-that-ambient-light-pro', 'accessory', 6200000, 0, 10, '{\"Colors\":\"64 m\\u00e0u t\\u00f9y ch\\u1ec9nh\",\"Zones\":\"11 v\\u00f9ng chi\\u1ebfu s\\u00e1ng\",\"Compatibility\":\"BMW 5 Series \\/ X5 \\/ 7 Series\",\"Control\":\"BMW iDrive \\/ BMW Connected App\",\"Warranty\":\"12 th\\u00e1ng\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu Bộ đèn LED nội thất Ambient Light Pro thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 0, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(21, 8, 'Thùng nhôm BMW Motorrad Vario 35L', 'thung-nhom-bmw-motorrad-vario-35l', 'accessory', 15800000, 0, 10, '{\"Capacity\":\"35 L\\u00edt (m\\u1ed7i b\\u00ean)\",\"Material\":\"Nh\\u00f4m nguy\\u00ean kh\\u1ed1i\",\"Compatibility\":\"BMW R1250GS \\/ R1300GS \\/ F850GS\",\"Lock\":\"Kh\\u00f3a th\\u1ed1ng nh\\u1ea5t BMW (One-key system)\",\"Weight\":\"4.8 kg\\/th\\u00f9ng\",\"Warranty\":\"24 th\\u00e1ng\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu Thùng nhôm BMW Motorrad Vario 35L thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(22, 8, 'Mũ bảo hiểm BMW System 7 Carbon', 'mu-bao-hiem-bmw-system-7-carbon', 'accessory', 22500000, 0, 10, '{\"Shell\":\"Carbon Fiber\",\"Weight\":\"1.590 kg (size M)\",\"Certification\":\"ECE 22.06\",\"Features\":\"L\\u1eadt h\\u00e0m, k\\u00ednh ch\\u1ed1ng s\\u01b0\\u01a1ng, Bluetooth-ready\",\"Sizes\":\"XS - XXL\",\"Warranty\":\"24 th\\u00e1ng\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu Mũ bảo hiểm BMW System 7 Carbon thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(23, 8, 'Ốp Carbon bảo vệ động cơ S1000RR', 'op-carbon-bao-ve-dong-co-s1000rr', 'accessory', 7800000, 0, 10, '{\"Material\":\"Carbon Fiber 3K Twill Weave\",\"Compatibility\":\"BMW S1000RR (2023+)\",\"Parts\":\"N\\u1eafp \\u0111\\u1eady b\\u00ecnh x\\u0103ng, \\u1ed1p h\\u00f4ng, ch\\u1eafn gi\\u00f3\",\"Weight\":\"0.4 kg\\/b\\u1ed9\",\"Warranty\":\"12 th\\u00e1ng\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu Ốp Carbon bảo vệ động cơ S1000RR thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 0, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(24, 8, 'Áo giáp BMW Motorrad StreetGuard', 'ao-giap-bmw-motorrad-streetguard', 'accessory', 18900000, 0, 10, '{\"Material\":\"GORE-TEX\\u00ae Pro\",\"Protection\":\"CE Level 2 (Vai, Khu\\u1ef7u tay, L\\u01b0ng)\",\"Features\":\"Ch\\u1ed1ng n\\u01b0\\u1edbc, ch\\u1ed1ng gi\\u00f3, tho\\u00e1ng kh\\u00ed 4 m\\u00f9a\",\"Sizes\":\"S - 3XL\",\"Warranty\":\"24 th\\u00e1ng\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu Áo giáp BMW Motorrad StreetGuard thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 1, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL),
(25, 8, 'Bộ bảo vệ gầm BMW Motorrad Enduro', 'bo-bao-ve-gam-bmw-motorrad-enduro', 'accessory', 5600000, 0, 10, '{\"Material\":\"Nh\\u00f4m CNC 4mm\",\"Compatibility\":\"BMW R1250GS Adventure \\/ R1300GS\",\"Weight\":\"3.2 kg\",\"Protection\":\"Carter d\\u1ea7u, \\u1ed1ng x\\u1ea3, h\\u1ed9p s\\u1ed1\",\"Warranty\":\"24 th\\u00e1ng\"}', 'Trải nghiệm đỉnh cao công xưởng Đức với mẫu Bộ bảo vệ gầm BMW Motorrad Enduro thế hệ mới 2025. Hiệu năng vượt trội, công nghệ dẫn đầu.', 0, 1, '2026-04-30 23:21:22', '2026-04-30 23:21:22', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `path`, `is_primary`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'images/cars/330i.png', 0, 8, '2026-04-30 23:21:22', '2026-06-10 05:03:51'),
(2, 2, 'images/cars/530i.png', 0, 6, '2026-04-30 23:21:22', '2026-06-10 05:03:51'),
(3, 3, 'images/cars/550e.png', 0, 6, '2026-04-30 23:21:22', '2026-06-10 05:03:51'),
(4, 4, 'images/cars/i4.png', 0, 6, '2026-04-30 23:21:22', '2026-06-10 05:03:51'),
(5, 5, 'images/cars/x3m50.png', 0, 6, '2026-04-30 23:21:22', '2026-06-10 05:03:51'),
(6, 6, 'images/cars/m3.png', 0, 6, '2026-04-30 23:21:22', '2026-06-10 05:03:51'),
(7, 7, 'images/cars/m4.png', 0, 6, '2026-04-30 23:21:22', '2026-06-10 05:03:51'),
(8, 8, 'images/cars/x5m.png', 0, 6, '2026-04-30 23:21:22', '2026-06-10 05:03:51'),
(9, 9, 'images/cars/xm.png', 0, 6, '2026-04-30 23:21:22', '2026-06-10 05:03:51'),
(10, 10, 'images/cars/m5t.png', 0, 6, '2026-04-30 23:21:22', '2026-06-10 05:03:51'),
(26, 1, 'images/cars/330i/hero-front-three-quarter.png', 1, 0, '2026-06-10 02:16:17', '2026-06-10 05:03:51'),
(27, 1, 'images/cars/330i/side-profile.png', 0, 1, '2026-06-10 02:16:17', '2026-06-10 05:03:51'),
(28, 1, 'images/cars/330i/rear-three-quarter.png', 0, 2, '2026-06-10 02:16:17', '2026-06-10 05:03:51'),
(29, 1, 'images/cars/330i/cockpit-interior.png', 0, 3, '2026-06-10 02:16:17', '2026-06-10 05:03:51'),
(30, 1, 'images/cars/330i/design-detail-wheel-light.png', 0, 4, '2026-06-10 02:16:17', '2026-06-10 05:03:51'),
(31, 1, 'images/cars/330i/lifestyle-showroom.png', 0, 5, '2026-06-10 02:16:17', '2026-06-10 05:03:51'),
(32, 1, 'images/cars/330i/urban-motion.png', 0, 6, '2026-06-10 02:16:17', '2026-06-10 05:03:51'),
(33, 1, 'images/cars/330i/studio-front-three-quarter.png', 0, 7, '2026-06-10 02:16:17', '2026-06-10 05:03:51'),
(34, 2, 'images/cars/bmw-530i-sedan/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(35, 2, 'images/cars/bmw-530i-sedan/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(36, 2, 'images/cars/bmw-530i-sedan/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(37, 2, 'images/cars/bmw-530i-sedan/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(38, 2, 'images/cars/bmw-530i-sedan/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(39, 2, 'images/cars/bmw-530i-sedan/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(40, 3, 'images/cars/bmw-550e-xdrive-sedan/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(41, 3, 'images/cars/bmw-550e-xdrive-sedan/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(42, 3, 'images/cars/bmw-550e-xdrive-sedan/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(43, 3, 'images/cars/bmw-550e-xdrive-sedan/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(44, 3, 'images/cars/bmw-550e-xdrive-sedan/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(45, 3, 'images/cars/bmw-550e-xdrive-sedan/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(46, 4, 'images/cars/bmw-i4-m60-gran-coupe/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(47, 4, 'images/cars/bmw-i4-m60-gran-coupe/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(48, 4, 'images/cars/bmw-i4-m60-gran-coupe/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(49, 4, 'images/cars/bmw-i4-m60-gran-coupe/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(50, 4, 'images/cars/bmw-i4-m60-gran-coupe/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(51, 4, 'images/cars/bmw-i4-m60-gran-coupe/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(52, 5, 'images/cars/bmw-x3-m50-xdrive/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(53, 5, 'images/cars/bmw-x3-m50-xdrive/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(54, 5, 'images/cars/bmw-x3-m50-xdrive/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(55, 5, 'images/cars/bmw-x3-m50-xdrive/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(56, 5, 'images/cars/bmw-x3-m50-xdrive/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(57, 5, 'images/cars/bmw-x3-m50-xdrive/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(58, 6, 'images/cars/bmw-m3-sedan/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(59, 6, 'images/cars/bmw-m3-sedan/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(60, 6, 'images/cars/bmw-m3-sedan/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(61, 6, 'images/cars/bmw-m3-sedan/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(62, 6, 'images/cars/bmw-m3-sedan/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(63, 6, 'images/cars/bmw-m3-sedan/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(64, 7, 'images/cars/bmw-m4-coupe/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(65, 7, 'images/cars/bmw-m4-coupe/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(66, 7, 'images/cars/bmw-m4-coupe/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(67, 7, 'images/cars/bmw-m4-coupe/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(68, 7, 'images/cars/bmw-m4-coupe/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(69, 7, 'images/cars/bmw-m4-coupe/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(70, 8, 'images/cars/bmw-x5-m-competition/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(71, 8, 'images/cars/bmw-x5-m-competition/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(72, 8, 'images/cars/bmw-x5-m-competition/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(73, 8, 'images/cars/bmw-x5-m-competition/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(74, 8, 'images/cars/bmw-x5-m-competition/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(75, 8, 'images/cars/bmw-x5-m-competition/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(76, 9, 'images/cars/bmw-xm-label/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(77, 9, 'images/cars/bmw-xm-label/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(78, 9, 'images/cars/bmw-xm-label/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(79, 9, 'images/cars/bmw-xm-label/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(80, 9, 'images/cars/bmw-xm-label/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(81, 9, 'images/cars/bmw-xm-label/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(82, 10, 'images/cars/bmw-m5-touring/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(83, 10, 'images/cars/bmw-m5-touring/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(84, 10, 'images/cars/bmw-m5-touring/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(85, 10, 'images/cars/bmw-m5-touring/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(86, 10, 'images/cars/bmw-m5-touring/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(87, 10, 'images/cars/bmw-m5-touring/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(88, 11, 'images/motorbikes/bmw-g310r/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(89, 11, 'images/motorbikes/bmw-g310r/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(90, 11, 'images/motorbikes/bmw-g310r/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(91, 11, 'images/motorbikes/bmw-g310r/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(92, 11, 'images/motorbikes/bmw-g310r/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(93, 11, 'images/motorbikes/bmw-g310r/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(94, 12, 'images/motorbikes/bmw-s1000rr/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(95, 12, 'images/motorbikes/bmw-s1000rr/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(96, 12, 'images/motorbikes/bmw-s1000rr/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(97, 12, 'images/motorbikes/bmw-s1000rr/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(98, 12, 'images/motorbikes/bmw-s1000rr/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(99, 12, 'images/motorbikes/bmw-s1000rr/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(100, 13, 'images/motorbikes/bmw-r1250gs/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(101, 13, 'images/motorbikes/bmw-r1250gs/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(102, 13, 'images/motorbikes/bmw-r1250gs/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(103, 13, 'images/motorbikes/bmw-r1250gs/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(104, 13, 'images/motorbikes/bmw-r1250gs/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(105, 13, 'images/motorbikes/bmw-r1250gs/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(106, 14, 'images/motorbikes/bmw-f900r/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(107, 14, 'images/motorbikes/bmw-f900r/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(108, 14, 'images/motorbikes/bmw-f900r/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(109, 14, 'images/motorbikes/bmw-f900r/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(110, 14, 'images/motorbikes/bmw-f900r/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(111, 14, 'images/motorbikes/bmw-f900r/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(112, 15, 'images/motorbikes/bmw-k1600gt/hero-front-three-quarter.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(113, 15, 'images/motorbikes/bmw-k1600gt/side-profile.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(114, 15, 'images/motorbikes/bmw-k1600gt/rear-three-quarter.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(115, 15, 'images/motorbikes/bmw-k1600gt/cockpit-interior.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(116, 15, 'images/motorbikes/bmw-k1600gt/design-detail.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(117, 15, 'images/motorbikes/bmw-k1600gt/lifestyle-showroom.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(118, 16, 'images/accessories/tham-lot-san-m-performance/hero-product.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(119, 16, 'images/accessories/tham-lot-san-m-performance/detail-material.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(120, 16, 'images/accessories/tham-lot-san-m-performance/installed-context.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(121, 16, 'images/accessories/tham-lot-san-m-performance/lifestyle-use.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(122, 16, 'images/accessories/tham-lot-san-m-performance/compatibility-context.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(123, 16, 'images/accessories/tham-lot-san-m-performance/studio-angle.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(124, 17, 'images/accessories/camera-hanh-trinh-bmw-advanced-eye-30/hero-product.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(125, 17, 'images/accessories/camera-hanh-trinh-bmw-advanced-eye-30/detail-material.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(126, 17, 'images/accessories/camera-hanh-trinh-bmw-advanced-eye-30/installed-context.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(127, 17, 'images/accessories/camera-hanh-trinh-bmw-advanced-eye-30/lifestyle-use.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(128, 17, 'images/accessories/camera-hanh-trinh-bmw-advanced-eye-30/compatibility-context.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(129, 17, 'images/accessories/camera-hanh-trinh-bmw-advanced-eye-30/studio-angle.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(130, 18, 'images/accessories/boc-vo-lang-m-performance-carbon/hero-product.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(131, 18, 'images/accessories/boc-vo-lang-m-performance-carbon/detail-material.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(132, 18, 'images/accessories/boc-vo-lang-m-performance-carbon/installed-context.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(133, 18, 'images/accessories/boc-vo-lang-m-performance-carbon/lifestyle-use.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(134, 18, 'images/accessories/boc-vo-lang-m-performance-carbon/compatibility-context.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(135, 18, 'images/accessories/boc-vo-lang-m-performance-carbon/studio-angle.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(136, 19, 'images/accessories/bo-vanh-bmw-m-performance-y-spoke-20/hero-product.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(137, 19, 'images/accessories/bo-vanh-bmw-m-performance-y-spoke-20/detail-material.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(138, 19, 'images/accessories/bo-vanh-bmw-m-performance-y-spoke-20/installed-context.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(139, 19, 'images/accessories/bo-vanh-bmw-m-performance-y-spoke-20/lifestyle-use.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(140, 19, 'images/accessories/bo-vanh-bmw-m-performance-y-spoke-20/compatibility-context.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(141, 19, 'images/accessories/bo-vanh-bmw-m-performance-y-spoke-20/studio-angle.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(142, 20, 'images/accessories/bo-den-led-noi-that-ambient-light-pro/hero-product.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(143, 20, 'images/accessories/bo-den-led-noi-that-ambient-light-pro/detail-material.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(144, 20, 'images/accessories/bo-den-led-noi-that-ambient-light-pro/installed-context.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(145, 20, 'images/accessories/bo-den-led-noi-that-ambient-light-pro/lifestyle-use.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(146, 20, 'images/accessories/bo-den-led-noi-that-ambient-light-pro/compatibility-context.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(147, 20, 'images/accessories/bo-den-led-noi-that-ambient-light-pro/studio-angle.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(148, 21, 'images/accessories/thung-nhom-bmw-motorrad-vario-35l/hero-product.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(149, 21, 'images/accessories/thung-nhom-bmw-motorrad-vario-35l/detail-material.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(150, 21, 'images/accessories/thung-nhom-bmw-motorrad-vario-35l/installed-context.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(151, 21, 'images/accessories/thung-nhom-bmw-motorrad-vario-35l/lifestyle-use.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(152, 21, 'images/accessories/thung-nhom-bmw-motorrad-vario-35l/compatibility-context.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(153, 21, 'images/accessories/thung-nhom-bmw-motorrad-vario-35l/studio-angle.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(154, 22, 'images/accessories/mu-bao-hiem-bmw-system-7-carbon/hero-product.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(155, 22, 'images/accessories/mu-bao-hiem-bmw-system-7-carbon/detail-material.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(156, 22, 'images/accessories/mu-bao-hiem-bmw-system-7-carbon/installed-context.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(157, 22, 'images/accessories/mu-bao-hiem-bmw-system-7-carbon/lifestyle-use.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(158, 22, 'images/accessories/mu-bao-hiem-bmw-system-7-carbon/compatibility-context.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(159, 22, 'images/accessories/mu-bao-hiem-bmw-system-7-carbon/studio-angle.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(160, 23, 'images/accessories/op-carbon-bao-ve-dong-co-s1000rr/hero-product.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(161, 23, 'images/accessories/op-carbon-bao-ve-dong-co-s1000rr/detail-material.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(162, 23, 'images/accessories/op-carbon-bao-ve-dong-co-s1000rr/installed-context.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(163, 23, 'images/accessories/op-carbon-bao-ve-dong-co-s1000rr/lifestyle-use.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(164, 23, 'images/accessories/op-carbon-bao-ve-dong-co-s1000rr/compatibility-context.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(165, 23, 'images/accessories/op-carbon-bao-ve-dong-co-s1000rr/studio-angle.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(166, 24, 'images/accessories/ao-giap-bmw-motorrad-streetguard/hero-product.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(167, 24, 'images/accessories/ao-giap-bmw-motorrad-streetguard/detail-material.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(168, 24, 'images/accessories/ao-giap-bmw-motorrad-streetguard/installed-context.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(169, 24, 'images/accessories/ao-giap-bmw-motorrad-streetguard/lifestyle-use.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(170, 24, 'images/accessories/ao-giap-bmw-motorrad-streetguard/compatibility-context.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(171, 24, 'images/accessories/ao-giap-bmw-motorrad-streetguard/studio-angle.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(172, 25, 'images/accessories/bo-bao-ve-gam-bmw-motorrad-enduro/hero-product.png', 1, 0, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(173, 25, 'images/accessories/bo-bao-ve-gam-bmw-motorrad-enduro/detail-material.png', 0, 1, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(174, 25, 'images/accessories/bo-bao-ve-gam-bmw-motorrad-enduro/installed-context.png', 0, 2, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(175, 25, 'images/accessories/bo-bao-ve-gam-bmw-motorrad-enduro/lifestyle-use.png', 0, 3, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(176, 25, 'images/accessories/bo-bao-ve-gam-bmw-motorrad-enduro/compatibility-context.png', 0, 4, '2026-06-10 05:01:29', '2026-06-10 05:03:51'),
(177, 25, 'images/accessories/bo-bao-ve-gam-bmw-motorrad-enduro/studio-angle.png', 0, 5, '2026-06-10 05:01:29', '2026-06-10 05:03:51');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('139V3UuUmLREtMJCJvXEhFJgJ8PTKdgQqLJA0pUk', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQk1wNkFMVktIVHVadE5ISm9hQldxUkk3V0xpRkVWNld4aHFJdWRTUSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jb21wYXJlP2lkcz0xJTJDMiI7czo1OiJyb3V0ZSI7czoxNjoicHJvZHVjdHMuY29tcGFyZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1781689430),
('3FL0NWNS3FXZj1CUbdGJkPaYDIYq7UPkEop2xiPk', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWkE0c0NOMU1TckVDZXYwZFVwTnhhOHdOcEE3WDFFbGhyeGw4UUdCZSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jb21wYXJlP2lkcz0xJTJDMiI7czo1OiJyb3V0ZSI7czoxNjoicHJvZHVjdHMuY29tcGFyZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1781689378),
('5Zr7UhfHKgQkCc5V2KLgWKCNMZ2I8ZYwIhaT2h9w', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibEZ4RFNIQjVmbE8xT3lMNU1udTNtSk9LUmlyM3UxVlFTRm1Fd2s0MSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781689598),
('CQsac0HSZvspH4WTBZ5T44tujPSsuoT1ZsB7v3Ld', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWHZjOGpqaHZ4UFIwWGpsZHZqWmhjMVY5RGZBcXpYT0ppWWRMRXpzTyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781689365),
('FcxLdwrPc8Eobf6mQEPKETnZwjuzz0ZgiY6N12l0', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiOThmUjJOWHZHOW5FRnp1OXlEZ2dyczFKZVpaangybU45T2xscUpzRCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jYXRhbG9nP3R5cGU9bW90b3JiaWtlIjtzOjU6InJvdXRlIjtzOjE0OiJwcm9kdWN0cy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1781689416),
('irVCQxYkdGUsHsv4ONZLJuYHAQR1OYXkYrvk7r0L', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiejZTM2QwYm5lWHIxbjhNWmt0WE90eGNsTFppc1lTMWZDQWVtMFExOCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC90aW0taGlldS10aGVtL3V1LWRhaS1tdWEtaGUtYm13LTIwMjYiO3M6NToicm91dGUiO3M6MTM6ImFydGljbGVzLnNob3ciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1781689416),
('iU3lmIdvji4vw1ZREFwahJixnZVTWt9Yy3Nh0yWu', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMk5tRElRZVZyUDlUcFJmYTZOT3Jab1BSWlo1aDQ5ZHEwdFpsUFcyNCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNyI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781684837),
('jBO8Z5Uzb5thnTD1ljRYwDSE9fcG85deXyREWMEu', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNXFQY2l5bm1BQUhPTlhmWjh4bFNWTUY3ZEZRa3ZSbU9heWdxYVpFayI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC90aW0taGlldS10aGVtL2JhaS1uaGFwLW5vaS1iby1zaG93cm9vbSI7czo1OiJyb3V0ZSI7czoxMzoiYXJ0aWNsZXMuc2hvdyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1781689416),
('lipj2KvezsXpiWkIXQuUW8adnhUprkIaFoMFGIXM', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYm92WFI1VU5RSjJxSVZuRmxjd1E5eWZvdVJUUURQWGJmbTBBdnBrZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781688211),
('nJwAxlRfSKietuI7H8ZSFjl7fijBknYTjrZ5lPgN', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUmJZR0FEekZMUGw4Vmc2Yk5UZ2VQMDFuOWduY2NrY0pLUE5DUVlFYSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jb21wYXJlP2lkcz1hYmMlMkM5OTk5OTkiO3M6NToicm91dGUiO3M6MTY6InByb2R1Y3RzLmNvbXBhcmUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1781689430),
('NSO1iweygtJs5WjOlVTJW29ioSOXDukf1nqr5VlQ', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZmhWeWlycUVHODVnb01YNFlTejBiVXhnNDk4WktjbVd3QVFxbjZtNSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781689378),
('Q0YwQR6QEir4ZQnGAQZX2NY1HqwFzbOoEAojAHcO', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZlpLa1FBQ3BURVptVUd6aFpCbkZaaFp1cjVoTGJvNEx3OFlpdUNXdCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jb21wYXJlP2lkcz0xJTJDMTYiO3M6NToicm91dGUiO3M6MTY6InByb2R1Y3RzLmNvbXBhcmUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1781689416),
('QRLkOgfagKwNJEgvky2HR0VijBEnoCOcGXJ8pp87', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSTU4azlOY2o1U1NRYjRhUlhGSGF6c0dGVGdYSFRMV012MXJSUTM4eiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781689573),
('QWfFUAB7slHEN5TyaSmGSZxTs552qLBWWgoWIUDs', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWHh3a0JzS2twWnlWZklQVGVkVkZEQ2NnbjNJU3Vpa1JaR3U3VEFrbyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9fQ==', 1781691983),
('rfId1q6JgypwD0JGphJGScNqxbiYJTeaFhwRCFwS', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieFJQVGNpQzdMTmZHejQxcEF4ZWRBYnNPbTlUalZNNGZDdjFTbFltYiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jYXRhbG9nL2Jtdy0zMzBpLXNlZGFuIjtzOjU6InJvdXRlIjtzOjEzOiJwcm9kdWN0cy5zaG93Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781689378),
('u2UUC4dJMex6hLOKbqwumUiX2rARmMEdqml3W35E', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidjM3czNma3lHZzNrNDFvTTNOekJEdHhZSDVadW93aDQyOEhrSnd6MiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1781689316),
('UX3W59SY1wB1GjjT1Pia4o47u5e6rDJQN9xcYc9n', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTG1WMlpwbkxZYmlkM2lIcW9ObDhzT1ZzT2tRdnBrd2FCSXY0Wnh0aSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jYXRhbG9nL2Jtdy1zMTAwMHJyIjtzOjU6InJvdXRlIjtzOjEzOiJwcm9kdWN0cy5zaG93Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781689416),
('w4CnxXKI4uTtxNSqnFksodPv2QPXcw8ZPsvxiqBm', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYVlvRE5udFhva2dYNXlwclh5U3lNTWRJZFY4cW1OMGFmWWpsSTFmTiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781706978),
('Wmz5rMxVnHD1dN8cfx0yaMF9mQVpaEfhoG6QncPM', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibW9YcEVyWEtqWnhuQjVUb2NkbGh1dmRUSFQxZ3p5QWE1Qm1UbEJYRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo0NDoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FkbWluL2FpLWNvbnZlcnNhdGlvbnMiO319', 1781689298),
('WUtXSS6nBLhnfGIkJDs2YR7CgSmeHKMbF1zEVbbr', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidzdjdE5iZDBsTEQ5NFpYZnFnZWtPMmR2VDZKMDZMeG9XUXBlVVlBWSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNyI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781684857),
('XAtxZN9DaqBJAFAOarlJaQONLyqx39RSBxjCPwsN', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiY1Bvd2VCRnVOTUdqWk5OU0hSRENEb2o3bTJnN3A0T3BHNkVEUDg3dSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jb21wYXJlP2lkcz1hYmMlMkM5OTk5OTkiO3M6NToicm91dGUiO3M6MTY6InByb2R1Y3RzLmNvbXBhcmUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1781689416),
('XHNCamBnJvqOmkLp6rEWs8GzXduERFDPtUZerdyp', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieGE0cnNoR1V1R3RyYTVibnJ0MjNqZ3dSV3pnQm15UHdrVEdBVkJzRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jb21wYXJlP2lkcz0xJTJDMTYiO3M6NToicm91dGUiO3M6MTY6InByb2R1Y3RzLmNvbXBhcmUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1781689430),
('yj4tqAdFldRV37Xz1VDJCYTZeAs6USpYf5NREvWv', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQlY5amdRQ3RSaXdsUU80aVFhV2l6eVFrMmVGUGJWd2Z1OWthSnlHdCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNyI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781685485),
('ZBIBO5pWtGdWTOrn7LmPoqJGYIHjA04QImHqJAlT', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiek5qa01lSzBlUmYxZWUzVVFjZHE5d3V2SFJ2Z2ZKanowa25tbGV0MyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jb21wYXJlP2lkcz0xJTJDMSUyQzIiO3M6NToicm91dGUiO3M6MTY6InByb2R1Y3RzLmNvbXBhcmUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1781689430),
('Zq2HgrBepa0S8ASMUgqcvMZ4cOCSiH0s8b5tK2mH', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiencxOHhMdUQ4ZGtRbG5oa2FCRGRoVEhEZmdCMTlyYVBYR1VZeDZBbyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781706443);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `site_settings`
--

CREATE TABLE `site_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin BMW Showroom', 'admin@bmw.com', NULL, '$2y$12$JxRfpMw8AwPLM3LrYkLVy.bK6WryKc.Qd3uRWjDxID1.kN0NS.Xy6', NULL, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(2, 'Nguyễn Quốc Thái', 'quanly1@bmw.com', NULL, '$2y$12$yKrFbwgEsJ/GIpb02tuQwuKGIvA4SrQIQp9DcYH3U3U0p4erWmWW2', NULL, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(3, 'Bác. Lý Nhật Chi', 'qa-nonadmin@example.com', '2026-06-12 09:11:00', '$2y$12$fWzmmBTdFeUxtIVy8SVZ5er10IHyG20X1sbo6QmNi/JkyAfAzcwPC', 'VqPj9MM5wb', '2026-06-12 09:11:00', '2026-06-12 09:11:00');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `accessory_orders`
--
ALTER TABLE `accessory_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accessory_orders_product_id_foreign` (`product_id`),
  ADD KEY `accessory_orders_status_created_at_index` (`status`,`created_at`),
  ADD KEY `accessory_orders_status_index` (`status`),
  ADD KEY `accessory_orders_ai_visitor_id_index` (`ai_visitor_id`);

--
-- Chỉ mục cho bảng `agent_conversations`
--
ALTER TABLE `agent_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_conversations_user_id_updated_at_index` (`user_id`,`updated_at`);

--
-- Chỉ mục cho bảng `agent_conversation_messages`
--
ALTER TABLE `agent_conversation_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_index` (`conversation_id`,`user_id`,`updated_at`),
  ADD KEY `agent_conversation_messages_user_id_index` (`user_id`),
  ADD KEY `agent_conversation_messages_conversation_id_index` (`conversation_id`);

--
-- Chỉ mục cho bảng `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_chat_messages_session_created_index` (`ai_chat_session_id`,`created_at`),
  ADD KEY `ai_chat_messages_role_index` (`role`),
  ADD KEY `ai_chat_messages_response_reason_index` (`response_reason`);

--
-- Chỉ mục cho bảng `ai_chat_sessions`
--
ALTER TABLE `ai_chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_chat_sessions_visitor_id_last_seen_at_index` (`visitor_id`,`last_seen_at`),
  ADD KEY `ai_chat_sessions_ip_hash_last_seen_at_index` (`ip_hash`,`last_seen_at`),
  ADD KEY `ai_chat_sessions_linked_source_index` (`linked_source_type`,`linked_source_id`),
  ADD KEY `ai_chat_sessions_visitor_id_index` (`visitor_id`),
  ADD KEY `ai_chat_sessions_ip_hash_index` (`ip_hash`),
  ADD KEY `ai_chat_sessions_first_seen_at_index` (`first_seen_at`),
  ADD KEY `ai_chat_sessions_last_seen_at_index` (`last_seen_at`),
  ADD KEY `ai_chat_sessions_last_intent_index` (`last_intent`),
  ADD KEY `ai_chat_sessions_status_index` (`status`);

--
-- Chỉ mục cho bảng `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointments_user_id_foreign` (`user_id`),
  ADD KEY `appointments_product_id_foreign` (`product_id`),
  ADD KEY `appointments_ai_visitor_id_index` (`ai_visitor_id`);

--
-- Chỉ mục cho bảng `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `articles_slug_unique` (`slug`),
  ADD KEY `articles_user_id_foreign` (`user_id`),
  ADD KEY `articles_status_published_at_index` (`status`,`published_at`),
  ADD KEY `articles_category_index` (`category`),
  ADD KEY `articles_status_index` (`status`),
  ADD KEY `articles_published_at_index` (`published_at`);

--
-- Chỉ mục cho bảng `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Chỉ mục cho bảng `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`);

--
-- Chỉ mục cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Chỉ mục cho bảng `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Chỉ mục cho bảng `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_slug_unique` (`slug`),
  ADD KEY `1` (`category_id`),
  ADD KEY `products_type_index` (`type`),
  ADD KEY `products_price_index` (`price`),
  ADD KEY `products_is_active_index` (`is_active`);

--
-- Chỉ mục cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_images_product_id_foreign` (`product_id`);

--
-- Chỉ mục cho bảng `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Chỉ mục cho bảng `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `site_settings_key_unique` (`key`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `accessory_orders`
--
ALTER TABLE `accessory_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `ai_chat_sessions`
--
ALTER TABLE `ai_chat_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `articles`
--
ALTER TABLE `articles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- AUTO_INCREMENT cho bảng `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `accessory_orders`
--
ALTER TABLE `accessory_orders`
  ADD CONSTRAINT `accessory_orders_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  ADD CONSTRAINT `ai_chat_messages_ai_chat_session_id_foreign` FOREIGN KEY (`ai_chat_session_id`) REFERENCES `ai_chat_sessions` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
