<?php

require_once './src/Database.php';
require_once './src/Session.php';
require_once './src/car-rental-view.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['isLogged']) || $_SESSION['isLogged'] !== true) {
  $_SESSION['login_message'] = 'Sign in to continue with your booking.';
  header('Location: ./login.php');
  exit();
}

$carIdRaw = isset($_GET['car_id']) && is_string($_GET['car_id']) ? $_GET['car_id'] : '';
$start_date = isset($_GET['start_date']) && is_string($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) && is_string($_GET['end_date']) ? $_GET['end_date'] : '';
$cityRaw = array_key_exists('city', $_GET)
  ? (is_string($_GET['city']) ? $_GET['city'] : '')
  : 'Bengaluru';
$city = app_supported_rental_city($cityRaw);
$pickup = app_parse_rental_datetime($start_date);
$dropoff = app_parse_rental_datetime($end_date);
$pickupAddress = isset($_SESSION['pickup_address']) && is_string($_SESSION['pickup_address'])
  && isset($_SESSION['pickup_city']) && $_SESSION['pickup_city'] === $city
  ? $_SESSION['pickup_address']
  : '';

if (!ctype_digit($carIdRaw) || (int) $carIdRaw < 1 || $city === null || $pickup === null || $dropoff === null || $dropoff <= $pickup) {
  http_response_code(422);
  exit('Invalid booking details. Please return to the fleet and search again.');
}

if ($pickup < new DateTimeImmutable('now')) {
  http_response_code(422);
  exit('Pick-up must be in the future. Please search again with new dates.');
}

$carid = (int) $carIdRaw;
$carStatement = $db->prepare("SELECT * FROM cars WHERE id = ? AND status = 'Active'");
$carStatement->bind_param('i', $carid);
$carStatement->execute();
$car_details = $carStatement->get_result()->fetch_object();

if (!$car_details) {
  http_response_code(404);
  exit('This car is no longer available.');
}

$availabilityStatement = $db->prepare(
  "SELECT COUNT(*) AS conflicts
   FROM booking
   WHERE car = ?
     AND booking_status IN ('Booked', 'Live')
     AND start_date < ?
     AND end_date > ?"
);
$endSql = $dropoff->format('Y-m-d H:i:s');
$startSql = $pickup->format('Y-m-d H:i:s');
$availabilityStatement->bind_param('iss', $carid, $endSql, $startSql);
$availabilityStatement->execute();
$isUnavailable = (int) $availabilityStatement->get_result()->fetch_assoc()['conflicts'] > 0;

if ($isUnavailable) {
  http_response_code(409);
  exit('This car is no longer available for those dates. Please choose another car or trip time.');
}

$customer_id = (int) Session::get('user')->id;
$customerStatement = $db->prepare('SELECT * FROM customers WHERE id = ?');
$customerStatement->bind_param('i', $customer_id);
$customerStatement->execute();
$customer = $customerStatement->get_result()->fetch_object();

if (!$customer) {
  http_response_code(401);
  exit('Your account could not be verified. Please sign in again.');
}

$user = Session::get('user');
$firstname = $user->name;
$lastname = $user->name;
$email = $user->email;
$mobile = $user->phone;
$address = $user->address;
$note = 'demo note';
$start_date = $pickup->format('d-m-Y h:i A');
$end_date = $dropoff->format('d-m-Y h:i A');
$quote = app_calculate_rental_quote($car_details->price_per_day, $pickup, $dropoff);
$price = number_format($quote['total'], 2, '.', '');
$car_name = $car_details->car_name;

include './src/config.php';

$html = '';
$checkoutError = '';
if (!isset($_SESSION['checkout_csrf_token']) || !is_string($_SESSION['checkout_csrf_token'])) {
  $_SESSION['checkout_csrf_token'] = bin2hex(random_bytes(32));
}
$checkoutCsrfToken = $_SESSION['checkout_csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
    ? $_POST['csrf_token']
    : '';
  $paymentMethod = isset($_POST['payment_method']) && is_string($_POST['payment_method'])
    ? $_POST['payment_method']
    : '';

  if (!hash_equals($checkoutCsrfToken, $submittedToken)) {
    $checkoutError = 'Your checkout session expired. Reload this page and try again.';
  } elseif ($paymentMethod === 'cash_on_delivery') {
    if ($customer->is_verified !== 'Verified') {
      $checkoutError = 'Your account must be verified before placing a booking.';
    } else {
      $bookingReference = 'BK-COD-' . strtoupper(bin2hex(random_bytes(6)));
      $transactionReference = 'COD' . strtoupper(bin2hex(random_bytes(8)));
      $bookingStatus = 'Booked';
      $paymentMode = 'cash_on_delivery';
      $paymentStatus = 'pending';
      $paymentDate = '';

      try {
        $db->begin_transaction();
        $lockedCarStatement = $db->prepare(
          "SELECT id FROM cars WHERE id = ? AND status = 'Active' FOR UPDATE"
        );
        $lockedCarStatement->bind_param('i', $carid);
        $lockedCarStatement->execute();
        $lockedCar = $lockedCarStatement->get_result()->fetch_object();

        if (!$lockedCar) {
          $db->rollback();
          $checkoutError = 'This car is no longer available. Please choose another car.';
        } else {
          $conflictStatement = $db->prepare(
            "SELECT COUNT(*) AS conflicts
             FROM booking
             WHERE car = ?
               AND booking_status IN ('Booked', 'Live')
               AND start_date < ?
               AND end_date > ?"
          );
          $conflictStatement->bind_param('iss', $carid, $endSql, $startSql);
          $conflictStatement->execute();
          $hasConflict = (int) $conflictStatement->get_result()->fetch_assoc()['conflicts'] > 0;

          if ($hasConflict) {
            $db->rollback();
            $checkoutError = 'This car was just booked for those dates. Please choose another car or trip time.';
          } else {
            $insert = $db->prepare(
              'INSERT INTO booking
                (booking_id, car, customer, start_date, end_date, total_price,
                 booking_status, txnid, payment_mode, payment_status, payment_date)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->bind_param(
              'siissssssss',
              $bookingReference,
              $carid,
              $customer_id,
              $startSql,
              $endSql,
              $price,
              $bookingStatus,
              $transactionReference,
              $paymentMode,
              $paymentStatus,
              $paymentDate
            );
            $insert->execute();
            $db->commit();
            header('Location: ./my-bookings.php?booking=cod');
            exit();
          }
        }
      } catch (mysqli_sql_exception $exception) {
        $db->rollback();
        error_log('Cash-on-delivery booking creation failed: ' . $exception->getMessage());
        $checkoutError = 'We could not save your booking right now. Please try again or choose online payment.';
      }
    }
  } elseif ($paymentMethod === 'online') {
  if ($key === '' || $salt === '') {
    $checkoutError = 'Online payment is not configured. Please choose cash on delivery.';
  } else {
  $_SESSION['cancelled_checkout_return'] = [
    'car_id' => $carid,
    'start' => $pickup->format('Y-m-d\TH:i'),
    'end' => $dropoff->format('Y-m-d\TH:i'),
    'city' => $city,
  ];
  $_POST['amount'] = $price;
  $_POST['start_date'] = $start_date;
  $_POST['end_date'] = $end_date;
  $_POST['customerid'] = (string) $customer_id;
  $_POST['productinfo'] = $car_name;
  $_POST['firstname'] = $firstname;
  $_POST['Lastname'] = $lastname;
  $_POST['email'] = $email;
  $_POST['phone'] = $mobile;
  $_POST['address1'] = (string) $address;
  $_POST['udf1'] = (string) $carid;
  $_POST['udf2'] = $start_date;
  $_POST['udf3'] = $end_date;
  $_POST['udf4'] = (string) $customer_id;
  $_POST['udf5'] = 'PayUBiz_PHP7_Kit';
  $_POST['Zipcode'] = '';
  $_POST['address2'] = '';
  $_POST['city'] = '';
  $_POST['state'] = '';
  $_POST['country'] = '';
  $_POST['Pg'] = '';
  $hash = hash('sha512', $key . '|' . $_POST['txnid'] . '|' . $_POST['amount'] . '|' . $_POST['productinfo'] . '|' . $_POST['firstname'] . '|' . $_POST['email'] . '|' . $_POST['udf1'] . '|' . $_POST['udf2'] . '|' . $_POST['udf3'] . '|' . $_POST['udf4'] . '|' . $_POST['udf5'] . '||||||' . $salt);


  $_SESSION['salt'] = $salt; //save salt in session to use during Hash validation in response
  //sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT)


  $html = '<form action="' . $action . '" id="payment_form_submit" method="post">
      <input type="hidden" id="udf5" name="udf5" value="' . $_POST['udf5'] . '" />
       <input type="hidden" id="udf1" name="udf1" value="' . $_POST['udf1'] . '" />
       <input type="hidden" id="udf2" name="udf2" value="' . $_POST['udf2'] . '" />
       <input type="hidden" id="udf3" name="udf3" value="' . $_POST['udf3'] . '" />
       <input type="hidden" id="udf4" name="udf4" value="' . $_POST['udf4'] . '" />

      <input type="hidden" id="surl" name="surl" value="' . $success_url . '" />
      <input type="hidden" id="furl" name="furl" value="' . $failed_url . '" />
      <input type="hidden" id="curl" name="curl" value="' . $cancelled_url . '" />
      <input type="hidden" id="key" name="key" value="' . $key . '" />
      <input type="hidden" id="txnid" name="txnid" value="' . $_POST['txnid'] . '" />
      <input type="hidden" id="amount" name="amount" value="' . $_POST['amount'] . '" />
      <input type="hidden" id="start_date" name="start_date" value="' . $_POST['start_date'] . '" />
      <input type="hidden" id="end_date" name="end_date" value="' . $_POST['end_date'] . '" />
      <input type="hidden" id="customer_id" name="customerid" value="' . $_POST['customerid'] . '" />

      <input type="hidden" id="productinfo" name="productinfo" value="' . $_POST['productinfo'] . '" />
      <input type="hidden" id="firstname" name="firstname" value="' . $_POST['firstname'] . '" />
      <input type="hidden" id="Lastname" name="Lastname" value="' . $_POST['Lastname'] . '" />
      <input type="hidden" id="Zipcode" name="Zipcode" value="' . $_POST['Zipcode'] . '" />
      <input type="hidden" id="email" name="email" value="' . $_POST['email'] . '" />
      <input type="hidden" id="phone" name="phone" value="' . $_POST['phone'] . '" />
      <input type="hidden" id="address1" name="address1" value="' . $_POST['address1'] . '" />
      <input type="hidden" id="address2" name="address2" value="' . (isset($_POST['address2']) ? $_POST['address2'] : '') . '" />
      <input type="hidden" id="city" name="city" value="' . $_POST['city'] . '" />
      <input type="hidden" id="state" name="state" value="' . $_POST['state'] . '" />
      <input type="hidden" id="country" name="country" value="' . $_POST['country'] . '" />
      <input type="hidden" id="Pg" name="Pg" value="' . $_POST['Pg'] . '" />
      <input type="hidden" id="hash" name="hash" value="' . $hash . '" />
      </form>
      <script type="text/javascript"><!--
        document.getElementById("payment_form_submit").submit();  
      //-->
      </script>';
  }
  } else {
    $checkoutError = 'Choose a payment method to continue.';
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment - Synchlab Coding </title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="css/portfolio.css">
</head>

<body class="cr-checkout">


  <div class="container">
    <div class="row">
      <div class="col-sm-12 form-container">
        <h1>Payment</h1>
        <hr>
        <?php if ($checkoutError !== ''): ?>
          <div class="alert alert-danger" role="alert"><?php echo app_escape($checkoutError); ?></div>
        <?php endif; ?>

        <?php
        //include './header.php';

        $booking_id = uniqid();
        /*$firstname=$_SESSION['fname']; 
        $lastname=$_SESSION['lname'];
        $email=$_SESSION['email'];
        $mobile=$_SESSION['mobile'];
        $address=$_SESSION['address'];
        $note=$_SESSION['note'];
        $carid =  $_SESSION['carid'];
        $start_date = $_SESSION['start_date'];

        $end_date = $_SESSION['end_date'];
        $customer_id = $user->id;
        $price =  $_SESSION['amount'];*/

        $webtitle = 'Synchlab Coding '; // Change web title
        $displayCurrency = 'INR';
        $imageurl = 'https://synchlabcoding.com';

        ?>
        <div class="row">


          <?php if ($customer->is_verified !== 'Verified'): ?>
            <div class="col-lg-4 mx-auto">

              <div class="card ">

                <div class="card-body text-center">

                  <h5>Your profile is not verified</h5>
                  <h3>Upload documents to verify your profile</h3>
                  <a href="./profile.php" class="btn btn-primary btn-sm">verify profile</a>
                </div>

              </div>
            </div>
          <?php else: ?>

            <div class="col-12 col-md-8">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Payer Information</h5>
                  <div class="row">
                    <div class="col-md-6">
                      <p><strong>First Name:</strong> <?php echo $firstname; ?></p>
                      <p><strong>Last Name:</strong> <?php echo $lastname; ?></p>
                      <p><strong>Email:</strong> <?php echo $email; ?></p>
                      <p><strong>Mobile:</strong> <?php echo $mobile; ?></p>
                    </div>
                    <div class="col-md-6">
                      <p><strong>Rental city:</strong> <?php echo app_escape($city); ?>, Karnataka</p>
                      <?php if ($pickupAddress !== ''): ?>
                        <p><strong>Pickup address:</strong> <?php echo app_escape($pickupAddress); ?></p>
                        <p>This address is held in the demo session only; it is not sent to the payment provider or a rental provider.</p>
                      <?php endif; ?>
                      <p><strong>Start Trip Time:</strong> <?php echo $start_date; ?></p>
                      <p><strong>End Trip Time:</strong> <?php echo $end_date; ?></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-12 col-md-4 text-center">
              <div class="card cr-checkout__vehicle-card">
                <img class="card-img-top" src="./<?php echo app_escape(app_car_image_url($car_details->image1)); ?>"
                  alt="<?php echo app_escape($car_name); ?>">
                <div class="card-body">
                  <h5 class="card-title"><?php echo app_escape($car_name); ?></h5>
                  <p class="card-text">₹<?php echo number_format($quote['daily_rate'], 2); ?> × <?php echo $quote['days']; ?>
                    <?php echo $quote['days'] === 1 ? 'day' : 'days'; ?> (24-hour blocks)</p>
                  <p class="card-text">Rental: ₹<?php echo number_format($quote['subtotal'], 2); ?></p>
                  <p class="card-text"><strong>Estimated total: ₹<?php echo number_format($quote['total'], 2); ?></strong></p>
                </div>
              </div>
              <br>

              <form action="" id="payment_form" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo app_escape($checkoutCsrfToken); ?>" />
                <input type="hidden" id="udf5" name="udf5" value="PayUBiz_PHP7_Kit" />
                <input type="hidden" id="udf1" name="udf1" value="<?php echo $carid; ?>" />
                <input type="hidden" id="udf2" name="udf2" value="<?php echo $start_date; ?>" />
                <input type="hidden" id="udf3" name="udf3" value="<?php echo $end_date; ?>" />
                <input type="hidden" id="udf4" name="udf4" value="<?php echo $customer_id; ?>" />

                <div class="dv">

                  <span>
                    <input type="hidden" id="txnid" name="txnid" placeholder="Transaction ID"
                      value="<?php echo "Txn" . rand(10000, 99999999) ?>" /></span>
                </div>

                <div class="dv">

                  <span>
                    <input type="hidden" id="amount" name="amount" placeholder="Amount"
                      value="<?php echo $price; ?>" /></span>
                </div>
                <div class="dv">

                  <span>
                    <input type="hidden" id="start_date" name="start_date" placeholder="start date"
                      value="<?php echo $start_date; ?>" /></span>
                </div>
                <div class="dv">

                  <span>
                    <input type="hidden" id="end_date" name="end_date" placeholder="end date"
                      value="<?php echo $end_date; ?>" /></span>
                </div>
                <div class="dv">

                  <span>
                    <input type="hidden" id="customerid" name="customerid" placeholder="customer_id"
                      value="<?php echo $customer_id; ?>" /></span>
                </div>
                <div class="dv">

                  <span>
                    <input type="hidden" id="productinfo" name="productinfo" placeholder="Product Info"
                      value="<?php echo $car_name; ?>" /></span>
                </div>
                <div class="dv">
                  <span>
                    <input type="hidden" id="firstname" name="firstname" placeholder="First Name"
                      value="<?php echo $firstname; ?>" /></span>
                </div>

                <div class="dv">

                  <span>
                    <input type="hidden" id="Lastname" name="Lastname" placeholder="Last Name"
                      value="<?php echo $lastname; ?>" /></span>
                </div>
                <div class="dv">

                  <span>
                    <input type="hidden" id="Zipcode" name="Zipcode" placeholder="Zip Code" value="" /></span>
                </div>
                <div class="dv">

                  <span>
                    <input type="hidden" id="email" name="email" placeholder="Email ID"
                      value="<?php echo $email; ?>" /></span>
                </div>

                <div class="dv">

                  <span>
                    <input type="hidden" id="phone" name="phone" placeholder="Mobile/Cell Number"
                      value="<?php echo $mobile; ?>" /></span>
                </div>

                <div class="dv">

                  <span>
                    <input type="hidden" id="address1" name="address1" placeholder="Address1"
                      value="<?php echo $address; ?>" /></span>
                </div>

                <div class="dv">

                  <span>
                    <input type="hidden" id="address2" name="address2" placeholder="Address2" value="" /></span>
                </div>

                <div class="dv">

                  <span>
                    <input type="hidden" id="city" name="city" placeholder="City" value="" /></span>
                </div>

                <div class="dv">

                  <span><input type="hidden" id="state" name="state" placeholder="State" value="" /></span>
                </div>

                <div class="dv">

                  <span><input type="hidden" id="country" name="country" placeholder="Country" value="" /></span>
                </div>

                <div class="dv">

                  <span>
                    <!-- Not mandatory but fixed code can be passed to Payment Gateway to show default payment 
        option tab. e.g. NB, CC, DC, CASH, EMI. Refer PDF for more details. //-->
                    <input type="hidden" id="Pg" name="Pg" placeholder="PG" value="" /></span>
                </div>

                <div>
                  <button class="cr-checkout__button cr-checkout__button--online" type="submit" id="btnsubmit" name="payment_method" value="online" <?php echo $key === '' || $salt === '' ? 'disabled' : ''; ?>>
                    <?php echo $key === '' || $salt === '' ? 'Online payment unavailable' : 'Continue to online payment'; ?>
                  </button>
                  <?php if ($key === '' || $salt === ''): ?>
                    <p class="text-muted">Online payments are not configured for this deployment. Cash on delivery is still available.</p>
                  <?php endif; ?>
                </div>
              </form>
              <hr>
              <section class="card cr-checkout__cod-option" aria-labelledby="cod-title">
                <div class="card-body">
                  <h5 class="card-title" id="cod-title">Or choose cash on delivery</h5>
                  <p class="card-text">Reserve this car and pay the rental total of ₹<?php echo number_format($quote['total'], 2); ?> in cash when the vehicle is handed over.</p>
                  <p class="card-text"><small class="text-muted">The booking will show as unpaid until the rental team confirms receiving the cash.</small></p>
                  <form action="" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo app_escape($checkoutCsrfToken); ?>" />
                    <button class="cr-checkout__button cr-checkout__button--cod" type="submit" name="payment_method" value="cash_on_delivery">
                      Book and pay cash on delivery
                    </button>
                  </form>
                </div>
              </section>
              <?php if ($html)
                echo $html; //submit request to PayUBiz  ?>

            </div>
          </div>
        </div>
      <?php endif ?>
    </div>

  </div>
</body>

</html>