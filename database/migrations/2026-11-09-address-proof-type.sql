ALTER TABLE `customers`
  ADD COLUMN `address_proof_type` varchar(30) DEFAULT NULL
  AFTER `driving_license_image2`;
