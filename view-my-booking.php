<?php
require_once './src/Database.php';
require_once './src/Session.php';
require_once './src/car-rental-view.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['isLogged']) || $_SESSION['isLogged'] !== true) {
    header('Location: ./login.php');
    exit();
}

$user = Session::get('user');
$customerId = isset($user->id) ? (int) $user->id : 0;
$bookingIdRaw = isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : '';
$booking_details = null;

if ($customerId < 1 || !ctype_digit($bookingIdRaw) || (int) $bookingIdRaw < 1) {
    http_response_code(400);
} else {
    $bookingId = (int) $bookingIdRaw;
    $statement = $db->prepare(
        'SELECT b.*, c.image1, c.image2, c.image3
         FROM booking b
         JOIN cars c ON c.id = b.car
         WHERE b.id = ? AND b.customer = ?'
    );
    $statement->bind_param('ii', $bookingId, $customerId);
    $statement->execute();
    $booking_details = $statement->get_result()->fetch_object();

    if (!$booking_details) {
        http_response_code(404);
    }
}

include './header.php';

if (!$booking_details) {
    ?>
    <main id="main" class="cr-account-page">
        <section class="section-bg cr-profile">
            <div class="container cr-profile__container">
                <h1>Booking not found</h1>
                <p>This booking is unavailable or does not belong to your account.</p>
                <a class="btn btn-primary" href="./my-bookings.php">Back to my bookings</a>
            </div>
        </section>
    </main>
    <?php
    include './footer.php';
    exit();
}
?>

<main id="main" class="cr-account-page">
    <section class="section-bg cr-profile">
        <div class="container cr-profile__container">
            <div class="row cr-profile__row">
            
                <div class="container">
                    <div class="row">
                        <div class="col-md-4">
                            <!-- Carousel -->
                            <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                                <ol class="carousel-indicators">
                                    <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
                                    <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
                                    <li data-target="#carouselExampleIndicators" data-slide-to="2"></li>
                                </ol>
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                        <img src="./<?php echo app_escape(app_car_image_url($booking_details->image1)); ?>"
                                            class="d-block w-100" alt="...">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="./<?php echo app_escape(app_car_image_url($booking_details->image2)); ?>"
                                            class="d-block w-100" alt="...">
                                    </div>
                                    <div class="carousel-item">
                                        <img src="./<?php echo app_escape(app_car_image_url($booking_details->image3)); ?>"
                                            class="d-block w-100" alt="...">
                                    </div>
                                </div>
                                <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button"
                                    data-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="sr-only">Previous</span>
                                </a>
                                <a class="carousel-control-next" href="#carouselExampleIndicators" role="button"
                                    data-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="sr-only">Next</span>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <!-- Card -->
                            <div class="card">
        <div class="card-header">
            Booking details
        </div>
        <div class="card-body">
            <table class="table table-bordered cr-profile__details-table">
                <tr>
                    <td><strong>Booking Id: </strong><?php echo app_escape($booking_details->booking_id) ?></td>
                    <td><strong>Booking Date: </strong><?php echo app_escape($booking_details->booking_date) ?></td>

                </tr>
                <tr>
                    <td><strong>Start Time.: </strong><?php echo app_escape($booking_details->start_date) ?></td>
                    <td><strong>End Time: </strong><?php echo app_escape($booking_details->end_date) ?></td>

                </tr>
                <tr>
                    <td colspan="3"><strong>Total Price:</strong> <?php echo app_escape($booking_details->total_price) ?></td>
                </tr>
                <tr>
                    <td><strong>Booking Status: </strong><span class="badge badge-pill badge-success"><?php echo app_escape($booking_details->booking_status); ?></span></td>
                    <td><strong>Payment method: </strong><?php echo app_escape(app_payment_method_label($booking_details->payment_mode)); ?></td>
                </tr>
                <tr>
                    <td><strong>Payment Status: </strong><span class="badge badge-pill badge-info"><?php echo app_escape(app_payment_status_label($booking_details->payment_mode, $booking_details->payment_status)); ?></span></td>
                    <td>
                        <?php if (strtolower((string) $booking_details->payment_mode) === 'cash_on_delivery' && strtolower((string) $booking_details->payment_status) !== 'success'): ?>
                            Pay in cash when the vehicle is handed over.
                        <?php else: ?>
                            &nbsp;
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <a class="btn btn-outline-primary" href="./src/receipt.php?id=<?php echo (int) $booking_details->id; ?>">
                Download booking summary
            </a>
            <?php if ($booking_details->booking_status === 'Booked' && strtolower((string) $booking_details->payment_status) === 'success'): ?>
                <?php $deliveryPosition = app_delivery_status_position($booking_details->delivery_status); ?>
                <h5>Vehicle delivery progress</h5>
                <p class="text-muted">This is updated by the rental team and is not live GPS tracking.</p>
                <ol class="list-group list-group-horizontal-md mb-3">
                    <?php foreach (app_delivery_status_steps() as $stepIndex => $deliveryStep): ?>
                        <li class="list-group-item flex-fill <?php echo $stepIndex <= $deliveryPosition ? 'list-group-item-success' : ''; ?>">
                            <?php echo app_escape($deliveryStep); ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <p>Current delivery status: <strong><?php echo app_escape($booking_details->delivery_status); ?></strong></p>
            <?php endif; ?>
        </div>
    </div>            
                        
                        </div>

                    </div>
                    
                </div>


            </div>
        </div>
    </section><!-- #intro -->
</main>
<?php include './footer.php' ?>