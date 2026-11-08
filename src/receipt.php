<?php
require_once './Database.php';
require_once './Session.php';
require_once './car-rental-view.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['isLogged']) || $_SESSION['isLogged'] !== true) {
    $_SESSION['redirect_url'] = './my-bookings.php';
    header('Location: ../login.php');
    exit();
}

$user = Session::get('user');
$customerId = isset($user->id) ? (int) $user->id : 0;
$bookingIdRaw = isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : '';
if ($customerId < 1 || !ctype_digit($bookingIdRaw) || (int) $bookingIdRaw < 1) {
    http_response_code(400);
    exit('A valid booking identifier is required.');
}

$bookingId = (int) $bookingIdRaw;
$statement = $db->prepare(
    'SELECT b.booking_id, b.start_date, b.end_date, b.total_price, b.booking_status,
            b.payment_mode, b.payment_status, b.payment_date, b.booking_date,
            c.car_name, cbrand.brand_name, u.name AS customer_name, u.email AS customer_email
     FROM booking b
     JOIN cars c ON c.id = b.car
     LEFT JOIN brands cbrand ON cbrand.id = c.brand
     LEFT JOIN customers u ON u.id = b.customer
     WHERE b.id = ? AND b.customer = ?
     LIMIT 1'
);
$statement->bind_param('ii', $bookingId, $customerId);
$statement->execute();
$booking = $statement->get_result()->fetch_assoc();

if (!$booking) {
    http_response_code(404);
    exit('Booking not found.');
}

$booking['brand_name'] = $booking['brand_name'] ?? '';
$html = '<!doctype html><html lang="en"><head><meta charset="UTF-8"><style>
body{font-family:DejaVu Sans,sans-serif;color:#172521;font-size:12px}
h1{color:#153d35;font-size:22px;margin-bottom:4px}
.muted{color:#64716c}.panel{border:1px solid #dfe4dc;padding:18px;margin-top:18px}
table{width:100%;border-collapse:collapse;margin-top:16px}
th,td{text-align:left;padding:9px;border-bottom:1px solid #e5e7df}
th{width:34%;color:#52615a}.notice{margin-top:20px;padding:12px;background:#f2f6e8}
</style></head><body>';
$html .= '<h1>Car rental booking summary</h1><p class="muted">Demo booking summary — not a tax invoice or proof of a supplier reservation.</p>';
$html .= '<div class="panel"><strong>' . app_escape($booking['booking_id']) . '</strong>';
$html .= '<table>';
$rows = [
    'Customer' => $booking['customer_name'],
    'Email' => $booking['customer_email'],
    'Vehicle' => trim($booking['brand_name'] . ' ' . $booking['car_name']),
    'Pick-up' => $booking['start_date'],
    'Return' => $booking['end_date'],
    'Booking status' => $booking['booking_status'],
    'Payment method' => app_payment_method_label($booking['payment_mode']),
    'Payment status' => app_payment_status_label($booking['payment_mode'], $booking['payment_status']),
    'Payment date' => $booking['payment_date'] ?: 'Not paid',
    'Estimated total' => 'INR ' . number_format((float) $booking['total_price'], 2),
];
foreach ($rows as $label => $value) {
    $html .= '<tr><th>' . app_escape($label) . '</th><td>' . app_escape($value) . '</td></tr>';
}
$html .= '</table><p class="notice">This project uses illustrative fleet data and estimated rates. Confirm the final price, taxes, deposits, rental terms, and availability with the provider.</p></div></body></html>';

$previousErrorReporting = error_reporting();
error_reporting($previousErrorReporting & ~E_DEPRECATED);
try {
    require_once './dompdf/autoload.inc.php';
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
} finally {
    error_reporting($previousErrorReporting);
}
$dompdf->stream('booking-' . $bookingId . '-summary.pdf', ['Attachment' => true]);
