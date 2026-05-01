-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th5 01, 2026 lúc 10:32 AM
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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('quanly@bmw.com|127.0.0.1', 'i:1;', 1777621898),
('quanly@bmw.com|127.0.0.1:timer', 'i:1777621898;', 1777621898);

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
(9, '2026_05_01_065239_add_meta_data_to_appointments_table', 2);

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
(1, 1, 'images/cars/330i.png', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(2, 2, 'images/cars/530i.png', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(3, 3, 'images/cars/550e.png', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(4, 4, 'images/cars/i4.png', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(5, 5, 'images/cars/x3m50.png', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(6, 6, 'images/cars/m3.png', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(7, 7, 'images/cars/m4.png', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(8, 8, 'images/cars/x5m.png', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(9, 9, 'images/cars/xm.png', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(10, 10, 'images/cars/m5t.png', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(11, 11, 'https://upload.wikimedia.org/wikipedia/commons/5/58/BMW_G_310_R.jpg', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(12, 12, 'https://upload.wikimedia.org/wikipedia/commons/7/79/BMW_S1000_RR_Studio.JPG', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(13, 13, 'https://upload.wikimedia.org/wikipedia/commons/9/9d/BMW_R_1250_GS_%282024-04-29_Sp%29.JPG', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(14, 14, 'https://upload.wikimedia.org/wikipedia/commons/f/fb/BMW_F900R_%281crop%29.jpg', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(15, 15, 'https://upload.wikimedia.org/wikipedia/commons/7/7f/BMW_K_1600_GT_Right.JPG', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(16, 16, 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=800&h=600&fit=crop', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(17, 17, 'https://images.unsplash.com/photo-1580894732444-8ecded7900cd?w=800&h=600&fit=crop', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(18, 18, 'https://images.unsplash.com/photo-1617814076367-b759c7d7e738?w=800&h=600&fit=crop', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(19, 19, 'https://images.unsplash.com/photo-1611821064430-0d40291d0f0b?w=800&h=600&fit=crop', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(20, 20, 'https://images.unsplash.com/photo-1603584173870-7f23fdae1b7a?w=800&h=600&fit=crop', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(21, 21, 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=800&h=600&fit=crop', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(22, 22, 'https://images.unsplash.com/photo-1571068316344-75bc76f77890?w=800&h=600&fit=crop', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(23, 23, 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?w=800&h=600&fit=crop', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(24, 24, 'https://images.unsplash.com/photo-1591637333184-19aa84b3e01f?w=800&h=600&fit=crop', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22'),
(25, 25, 'https://images.unsplash.com/photo-1609630875171-b1321377ee65?w=800&h=600&fit=crop', 1, 0, '2026-04-30 23:21:22', '2026-04-30 23:21:22');

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
('7zTTmvWwcn4D8FNt0Qx8mEiXZu2f8VLPTRXjbJRd', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibDVvMXp2SEthbzliZUVkdmNLQ0RpZktJQW5lMmJhMzVOdlVaZkZOcyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvcHJvZHVjdHMtYnktY2F0ZWdvcnk/Y2F0ZWdvcnlfdHlwZT1vdG8iO3M6NToicm91dGUiO3M6MjE6ImFwaS5wcm9kdWN0cy5jYXRlZ29yeSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1777621182),
('wjH688WNpUpD1kJOD7jWfZlctzTnup9dIQoa6FEX', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiTHFiNEpCSTdMWVFUY2xrODNJb0RQaEIzbXhHUkZZdjdZaXM3VHQ2bSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czozOiJ1cmwiO2E6MDp7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7fQ==', 1777623200);

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
(2, 'Nguyễn Quốc Thái', 'quanly1@bmw.com', NULL, '$2y$12$yKrFbwgEsJ/GIpb02tuQwuKGIvA4SrQIQp9DcYH3U3U0p4erWmWW2', NULL, '2026-04-30 23:21:22', '2026-04-30 23:21:22');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointments_user_id_foreign` (`user_id`),
  ADD KEY `appointments_product_id_foreign` (`product_id`);

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
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
