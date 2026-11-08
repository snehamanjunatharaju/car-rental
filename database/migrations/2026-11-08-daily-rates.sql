ALTER TABLE `cars`
  CHANGE COLUMN `price_per_hour` `price_per_day` varchar(20) NOT NULL;

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
