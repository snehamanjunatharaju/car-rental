<?php
require_once ("./src/Database.php");
include ("./src/config.php");
if ($key === '' || $salt === '') {
    http_response_code(503);
    exit('Online payment is not configured.');
}
$postdata = $_POST;
//print_r($postdata);die;
$msg = '';
$status = '';
if (isset($postdata['key'])) {
    //print_r($postdata);die;
    $key = $postdata['key'];
    $txnid = $postdata['txnid'];
    $amount = $postdata['amount'];
    $productInfo = $postdata['productinfo'];
    $firstname = $postdata['firstname'];
    $lastname = $postdata['lastname'];
    $phone = $postdata['phone'];
    $email = $postdata['email'];
    $address1 = $postdata['address1'];
    $udf5 = $postdata['udf5'];
    $carid = $postdata['udf1'];
    $start_date = $postdata['udf2'];
    $end_date = $postdata['udf3'];
    $customer_id = $postdata['udf4'];
    $status = $postdata['status'];
    $resphash = $postdata['hash'];
    //Calculate response hash to verify 
    $keyString = $key . '|' . $txnid . '|' . $amount . '|' . $productInfo . '|' . $firstname . '|' . $email . '|' . $postdata['udf1'] . '|' . $postdata['udf2'] . '|' . $postdata['udf3'] . '|' . $postdata['udf4'] . '|' . $udf5 . '|||||';
    $keyArray = explode("|", $keyString);
    $reverseKeyArray = array_reverse($keyArray);
    $reverseKeyString = implode("|", $reverseKeyArray);
    $CalcHashString = strtolower(hash('sha512', $salt . '|' . $status . '|' . $reverseKeyString)); //hash without additionalcharges

    //check for presence of additionalcharges parameter in response.
    $additionalCharges = "";

    if (isset($postdata["additionalCharges"])) {
        $additionalCharges = $postdata["additionalCharges"];
        //hash with additionalcharges
        $CalcHashString = strtolower(hash('sha512', $additionalCharges . '|' . $salt . '|' . $status . '|' . $reverseKeyString));
    }
    //Comapre status and hash. Hash verification is mandatory.
    if ($status == 'success' && $resphash == $CalcHashString) {
        $msg = "Transaction Successful, Hash Verified...<br />";
        //Do success order processing here...
    } else {
        //tampered or failed
        $msg = "Payment failed for Hash not verified...";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Status - Synchlab Coding </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-sm-12 form-container">
                <h1>Payment Status</h1>
                <hr>


                <div class="row">
                    <div class="col-8">
                        <?php

                        if ($status == 'success' && $resphash == $CalcHashString && $txnid != '') {
                            $subject = 'Booking has been successful..';
                            $currency = 'INR';
                            $date = new DateTime(null, new DateTimezone("Asia/Kolkata"));
                            $payment_date = $date->format('Y-m-d H:i:s');
                         
                            $countts = null; // Define $countts in a wider scope
                        
                            $sql = "SELECT count(*) FROM booking WHERE txnid='$txnid'";
                            //echo $sql;die;
                            if ($result = $db->query($sql)) {
                                $row = $result->fetch_row();
                                $countts = $row[0];
                               
                            }

                            if ($txnid != '') {

                                if ($countts <= 0) {


                                    $booking_id = 'BK' . uniqid();
                                    //echo $booking_id;die;
                                    $startDateTime = DateTime::createFromFormat('d-m-Y h:i A', $start_date);
                               
                                    $formattedStartDate = $startDateTime->format('Y-m-d H:i:s');

                                    $endDateTime = DateTime::createFromFormat('d-m-Y h:i A', $end_date);
                               
                                    $formattedEndDate = $endDateTime->format('Y-m-d H:i:s');

                                    //booking_id, car, customer,start_date,end_date, total_price,booking_status,txnid,payment_mode,payment_date
                                    /* $sql = "INSERT INTO payments(start_date, end_date, customer_id, firstname, lastname, amount, status, txnid, pid, payer_email, currency, mobile, address, note, payment_date) 
                                             VALUES ('$formattedStartDate','$end_date','$customer_id','$firstname', '$lastname', '$amount', '$status', '$txnid', '$pid', '$email', '$currency', '$phone', '$address1', '$note', '$payment_date')";*/

                                    $sql = "INSERT INTO booking(booking_id, car, customer, start_date, end_date, total_price, booking_status, txnid, payment_mode, payment_status, payment_date) 
        VALUES ('$booking_id','$carid','$customer_id','$formattedStartDate', '$formattedEndDate', '$amount', 'Booked', '$txnid', 'online', '$status', '$payment_date')";
                                  
                                  //echo $sql;die;

                                    mysqli_query($db, $sql);
                                }



                                echo '<h2 style="color:#33FF00;">' . $subject . '</h2><hr>';

                                $sql = "SELECT * FROM booking WHERE txnid='$txnid'";
                                $result = mysqli_query($db, $sql);

                                while ($row = mysqli_fetch_assoc($result)) {
                                    $dbdate = $row['payment_date'];
                                    $booking_status =  $row['booking_status'];
                                    $booking_id =  $row['booking_id'];
                                }

                                echo '<table class="table">';
                                echo '<tr>';
                                echo '<th>Booking ID:</th>';
                                echo '<td>' . $booking_id . '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                echo '<th>Paid Amount:</th>';
                                echo '<td>' . $amount . ' ' . $currency . '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                echo '<th>Booking Status:</th>';
                                echo '<td style="color:green;">' . $booking_status . ' </td>';
                                echo '</tr>';
                                echo '<tr>';
                                echo '<th>Payment Status:</th>';
                                echo '<td>' . $status . '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                echo '<th>Payer Email:</th>';
                                echo '<td>' . $email . '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                echo '<th>Name:</th>';
                                echo '<td>' . $firstname . ' ' . $lastname . '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                echo '<th>Mobile No:</th>';
                                echo '<td>' . $phone . '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                echo '<th>Date :</th>';
                                echo '<td>' . $dbdate . '</td>';
                                echo '</tr>';
                                echo '</table>';
                            }
                        } else {
                            $html = "<p><div class='errmsg'>Invalid Transaction. Please Try Again</div></p>";
                            $error_found = 1;
                        }

                        if (isset($html)) {
                            echo $html;
                        }
                        ?>
                    </div>
                    <div class="col-4 text-center">
                        <?php
                        if (!isset($error_found)) {
                            $sql = "SELECT * FROM cars WHERE id = '$carid'";
                           // echo $sql;
                            $res = $db->query($sql);
                            $car_details = $res->fetch_object();
                            $fileName1 = explode('/', $car_details->image1)[4];

                            echo '<div class="card" style="width: 18rem;">
  <img class="card-img-top" src="./admin/uploaded-files/cars/' . $fileName1 . '" alt="Card image cap">
  <div class="card-body">
    <h5 class="card-title">' . $car_details->car_name . '</h5>
  </div>
</div>';
                        }
                        ?>
                        <br>
                        <a href="./my-bookings.php" class="btn btn-primary">Go to My Bookings</a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</body>

</html>