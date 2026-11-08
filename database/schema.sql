-- phpMyAdmin SQL Dump
-- version 5.0.1
-- https://www.phpmyadmin.net/
--
-- Host: ?

-- Generation Time: May 13, 2024 at 08:08 AM
-- Server version: 10.4.11-MariaDB
-- PHP Version: 7.4.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `car_rental`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `id` int(11) NOT NULL,
  `booking_id` varchar(50) NOT NULL,
  `car` int(11) NOT NULL,
  `customer` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `total_price` varchar(20) NOT NULL,
  `booking_status` varchar(20) NOT NULL DEFAULT 'Pending',
  `delivery_status` varchar(30) NOT NULL DEFAULT 'Confirmed',
  `txnid` varchar(50) NOT NULL,
  `payment_id` varchar(50) DEFAULT NULL,
  `payment_mode` varchar(20) DEFAULT NULL,
  `payment_status` varchar(50) NOT NULL,
  `payment_date` varchar(50) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `brand_name` varchar(50) NOT NULL,
  `image` varchar(150) NOT NULL,
  `status` varchar(15) NOT NULL DEFAULT 'Enable',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `brand_name`, `image`, `status`, `created_at`) VALUES
(1, 'Maruti Suzuki', '../admin/uploaded-files/brands/3ea7d8fc9f276b6ca818fc58f173f5af.png', 'Enable', '2024-05-09 09:40:55'),
(2, 'BMW', '../admin/uploaded-files/brands/180b8f96e6ed97b42a06e6167db119fb.png', 'Enable', '2024-05-09 09:41:10'),
(4, 'Toyota', '../admin/uploaded-files/brands/b721dc7c8acba9b4d27281f47efa6400.png', 'Enable', '2024-05-09 09:41:46'),
(5, 'Ford', '../admin/uploaded-files/brands/48819963f71d8b66c520fde2ad962054.png', 'Enable', '2024-05-09 09:41:57'),
(6, 'Mahindra', '../admin/uploaded-files/brands/3d2f6cae754ff62004849119d5221829.png', 'Enable', '2024-05-09 09:42:18'),
(7, 'Hyundai', '../admin/uploaded-files/brands/309349b705f3f97467cb352e1fe7653d.png', 'Enable', '2024-05-09 10:25:24');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `car_name` varchar(150) NOT NULL,
  `brand` int(11) NOT NULL,
  `car_body_type` varchar(50) DEFAULT NULL,
  `transmission` varchar(50) NOT NULL,
  `fuel_type` varchar(20) NOT NULL,
  `model` varchar(20) NOT NULL,
  `seating_capacity` varchar(20) NOT NULL,
  `km_driven` varchar(20) NOT NULL,
  `price_per_day` varchar(20) NOT NULL,
  `reg_no` varchar(20) NOT NULL,
  `ac` int(11) NOT NULL,
  `sun_roof` int(11) NOT NULL,
  `air_bags` int(11) NOT NULL,
  `central_lock` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `image1` varchar(250) DEFAULT NULL,
  `image2` varchar(250) DEFAULT NULL,
  `image3` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `car_name`, `brand`, `car_body_type`, `transmission`, `fuel_type`, `model`, `seating_capacity`, `km_driven`, `price_per_day`, `reg_no`, `ac`, `sun_roof`, `air_bags`, `central_lock`, `description`, `status`, `image1`, `image2`, `image3`, `created_at`) VALUES
(1, 'ALTO Lxi', 1, 'Hatchback ', 'Manual', 'Petrol', 'LXI', '5', '1000', '120', 'demo321', 1, 0, 1, 0, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled', 'Active', '../admin/uploaded-files/cars/82db459b3ac508db8ae463c424f78a05.jpg', '../admin/uploaded-files/cars/7284fc8d034ce54f9d67ad28207f9f58.jpg', '../admin/uploaded-files/cars/606600dc33b14b5b8e229dc2af83bf15.jpg', '2024-05-09 09:53:23'),
(2, 'Mahindra XUV700', 6, 'SUV', 'Automatic', 'Diesel', ' XUV700', '7', '10000', '250', '32demo', 1, 1, 1, 1, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled', 'Active', '../admin/uploaded-files/cars/f460cd7e805b0fa6f7c0a1f4f1c7fb43.jpg', '../admin/uploaded-files/cars/78ffc5a454af46323d0c2fb76620f200.jpg', '../admin/uploaded-files/cars/1b3cbf083210eb146e7d26eb48b08c79.jpg', '2024-05-09 09:57:32'),
(3, 'Mahindra Scorpio', 6, 'SUV', 'Automatic', 'Petrol', ' Z4 Petrol', '7', '500', '210', '32yuiy', 1, 1, 1, 0, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled', 'Active', '../admin/uploaded-files/cars/205507a37c5aaa98af07477d61e551a1.jpg', '../admin/uploaded-files/cars/1b16615d22469249713a83c4fb4f4bd7.jpg', '../admin/uploaded-files/cars/a927409316064f8bc9d8a965cc3274fd.jpg', '2024-05-09 10:00:01'),
(4, 'Mahindra Thar', 6, 'SUV', 'Automatic', 'Petrol', 'AX (O) ', '5', '5000', '300', '32ytre', 1, 1, 1, 1, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled', 'Active', '../admin/uploaded-files/cars/118e7beb4d15c00055e492ebc23c7a83.jpg', '../admin/uploaded-files/cars/eef49985010ec9213202e967dbc0a96b.jpg', '../admin/uploaded-files/cars/7847dca6976b4c50903145540527cdbb.jpg', '2024-05-09 10:01:57'),
(5, 'Toyota Corolla', 4, 'Sedan', 'Automatic', 'Petrol', 'DOHC', '5', '15000', '320', '3212ytre', 1, 1, 1, 0, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled', 'Active', '../admin/uploaded-files/cars/5606dd4ee9be89892fd04029026b772d.jpg', '../admin/uploaded-files/cars/ce3ac26abbaae38ca61896eeacc79af9.jpg', '../admin/uploaded-files/cars/8c153cd37a62bd70355b8d627e28b6c2.png', '2024-05-09 10:05:24'),
(6, 'Ford Mustang', 5, 'Coupe', 'Automatic', 'Petrol', 'DOHC', '5', '1000', '350', 'dserw12', 1, 0, 1, 0, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled', 'Active', '../admin/uploaded-files/cars/056a16a67f6cf16cf808f8916cd7c971.jpg', '../admin/uploaded-files/cars/d3cebd363883c79a5a9b37b227e9a091.jpg', '../admin/uploaded-files/cars/87d6d9ff4e7ca5e45b47264084bd470a.jpg', '2024-05-09 10:10:50'),
(11, 'BMW Z4', 2, 'Convertible', 'Automatic', 'Petrol', 'z4', '4', '100', '400', 'xcewr321', 1, 1, 1, 0, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown', 'Active', '../admin/uploaded-files/cars/d843d2f63a4d8ed501d696b82a4bb9d6.jpg', '', '', '2024-05-09 10:19:05'),
(12, 'Hyundai Creta', 7, 'SUV', 'Automatic', 'CNG', '2022 Model', '5', '1000', '120', 'er321`', 1, 1, 1, 0, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown', 'Active', '../admin/uploaded-files/cars/d07c24fcadc2d486afcebc6f39cba4e7.jpg', '../admin/uploaded-files/cars/babb9d6402866c18ca0834f4a016e804.jpg', '../admin/uploaded-files/cars/b6b207dbdbf63b47b5f4957c749d28dd.png', '2024-05-09 10:28:18');

-- Illustrative Bengaluru daily estimates in INR, not live provider quotes.
UPDATE `cars`
SET `price_per_day` = CASE `id`
  WHEN 1 THEN '1499'
  WHEN 2 THEN '2999'
  WHEN 3 THEN '2799'
  WHEN 4 THEN '2999'
  WHEN 5 THEN '2199'
  WHEN 6 THEN '1000'
  WHEN 11 THEN '1000'
  WHEN 12 THEN '2499'
  ELSE `price_per_day`
END,
`status` = CASE
  WHEN `id` IN (6, 11) THEN 'Inactive'
  ELSE `status`
END
WHERE `id` IN (1, 2, 3, 4, 5, 6, 11, 12);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(256) NOT NULL,
  `is_verified` varchar(20) NOT NULL DEFAULT 'Pending',
  `address` text DEFAULT NULL,
  `driving_license_no` varchar(50) DEFAULT NULL,
  `driving_license_image1` varchar(255) DEFAULT NULL,
  `driving_license_image2` varchar(255) DEFAULT NULL,
  `address_proof_no` varchar(50) DEFAULT NULL,
  `address_proof_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `customers`
--

-- --------------------------------------------------------

--
-- Table structure for table `disabled_dates`
--

CREATE TABLE `disabled_dates` (
  `id` int(11) NOT NULL,
  `date` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `disabled_dates`
--

INSERT INTO `disabled_dates` (`id`, `date`) VALUES
(1, '2020-02-07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `username` varchar(150) NOT NULL,
  `password` varchar(256) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `disabled_dates`
--
ALTER TABLE `disabled_dates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `disabled_dates`
--
ALTER TABLE `disabled_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
UPDATE cars SET reg_no = CONCAT('DEMO-', id);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
