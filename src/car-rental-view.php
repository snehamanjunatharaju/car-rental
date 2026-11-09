<?php

function app_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_profile_document_file_type($path)
{
    if (!is_string($path) || !is_file($path)) {
        return null;
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (!isset($extensions[$mimeType])) {
        return null;
    }

    if ($mimeType === 'application/pdf') {
        $handle = fopen($path, 'rb');
        $signature = $handle === false ? '' : fread($handle, 5);
        if ($handle !== false) {
            fclose($handle);
        }
        if ($signature !== '%PDF-') {
            return null;
        }
    } else {
        $image = @getimagesize($path);
        if ($image === false || ($image['mime'] ?? '') !== $mimeType) {
            return null;
        }
    }

    return [
        'extension' => $extensions[$mimeType],
        'mime_type' => $mimeType,
        'is_image' => $mimeType !== 'application/pdf',
    ];
}

function app_profile_document_is_image($filename)
{
    return in_array(strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
}

function app_parse_datetime_input($value)
{
    if (!is_string($value) || $value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
    $errors = DateTimeImmutable::getLastErrors();

    if (
        $date === false
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        || $date->format('Y-m-d\TH:i') !== $value
    ) {
        return null;
    }

    return $date;
}

function app_parse_rental_datetime($value)
{
    if (!is_string($value) || $value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!d-m-Y h:i A', $value);
    $errors = DateTimeImmutable::getLastErrors();

    if (
        $date === false
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        || $date->format('d-m-Y h:i A') !== $value
    ) {
        return null;
    }

    return $date;
}

function app_calculate_rental_quote($dailyRate, DateTimeImmutable $pickup, DateTimeImmutable $dropoff)
{
    $dailyRate = (float) $dailyRate;
    if (!is_finite($dailyRate) || $dailyRate < 1 || $dropoff <= $pickup) {
        throw new InvalidArgumentException('A valid rate and rental period are required.');
    }

    $days = (int) ceil(($dropoff->getTimestamp() - $pickup->getTimestamp()) / 86400);
    $subtotal = round($dailyRate * $days, 2);

    return [
        'days' => $days,
        'daily_rate' => $dailyRate,
        'subtotal' => $subtotal,
        'total' => $subtotal,
    ];
}

function app_supported_rental_city($city)
{
    if (!is_string($city)) {
        return null;
    }

    $city = strtolower(trim($city));
    if (in_array($city, ['bengaluru', 'bangalore'], true)) {
        return 'Bengaluru';
    }

    return null;
}

function app_delivery_status_steps()
{
    return ['Confirmed', 'Preparing', 'On the way', 'Delivered'];
}

function app_delivery_status_position($status)
{
    $position = array_search($status, app_delivery_status_steps(), true);

    return $position === false ? 0 : $position;
}

function app_payment_method_label($paymentMode)
{
    return strtolower((string) $paymentMode) === 'cash_on_delivery'
        ? 'Cash on delivery'
        : (strtolower((string) $paymentMode) === 'online' ? 'Online payment' : 'Not specified');
}

function app_payment_status_label($paymentMode, $paymentStatus)
{
    if (strtolower((string) $paymentStatus) === 'success') {
        return strtolower((string) $paymentMode) === 'cash_on_delivery'
            ? 'Paid on delivery'
            : 'Paid online';
    }

    return strtolower((string) $paymentMode) === 'cash_on_delivery'
        ? 'Due on delivery'
        : ucfirst((string) $paymentStatus);
}

function app_car_image_url($imagePath)
{
    $filename = basename((string) $imagePath);

    if ($filename === '' || $filename === '.' || $filename === '..') {
        return 'img/car3.png';
    }

    return 'admin/uploaded-files/cars/' . rawurlencode($filename);
}

function app_car_detail_url($carId, $start, $end, $city = 'Bengaluru')
{
    return 'car-details.php?' . http_build_query([
        'id' => (int) $carId,
        'start' => $start,
        'end' => $end,
        'city' => app_supported_rental_city($city) ?: 'Bengaluru',
    ]);
}
