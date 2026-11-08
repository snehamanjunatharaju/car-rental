ALTER TABLE `booking`
  ADD COLUMN `delivery_status` varchar(30) NOT NULL DEFAULT 'Confirmed'
  AFTER `booking_status`;
