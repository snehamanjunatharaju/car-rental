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
if (!isset($_SESSION['profile_csrf_token']) || !is_string($_SESSION['profile_csrf_token'])) {
    $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));
}
$profileCsrfToken = $_SESSION['profile_csrf_token'];

$user = Session::get('user');
$customer_id = (int) $user->id;
$statement = $db->prepare('SELECT * FROM customers WHERE id = ?');
$statement->bind_param('i', $customer_id);
$statement->execute();
$profile_details = $statement->get_result()->fetch_object();

$bookingStatement = $db->prepare(
    'SELECT b.id AS booking_record_id, b.booking_id, b.start_date, b.end_date,
            b.booking_status, b.payment_mode, b.payment_status, b.delivery_status,
            c.car_name, c.image1
     FROM booking b
     JOIN cars c ON c.id = b.car
     WHERE b.customer = ?
     ORDER BY b.booking_date DESC, b.id DESC'
);
$bookingStatement->bind_param('i', $customer_id);
$bookingStatement->execute();
$profileBookings = $bookingStatement->get_result()->fetch_all(MYSQLI_ASSOC);

$fileName1 = basename((string) $profile_details->driving_license_image1);
$fileName2 = basename((string) $profile_details->driving_license_image2);
$addressProofFileName = basename((string) $profile_details->address_proof_image);
include './header.php';

?>
<main id="main" class="cr-account-page">
    <section class="section-bg cr-profile">
        <div class="container cr-profile__container">
            
            <div class="row cr-profile__row">
                <div class="col-12">
                    <p class="cr-profile__eyebrow">Your account</p>
                    <h1 class="cr-profile__title">My profile</h1>
                    <section class="cr-profile__verification" aria-labelledby="verification-title">
                        <div>
                            <h2 id="verification-title">Document verification</h2>
                            <p>
                                <?php if (empty($profile_details->phone)): ?>
                                    You have not uploaded your documents yet.
                                <?php elseif ($profile_details->is_verified === 'Pending'): ?>
                                    Your submitted documents are awaiting admin review. This is separate from your car booking status.
                                <?php elseif ($profile_details->is_verified === 'Verified'): ?>
                                    Your documents have been approved. This is separate from your car booking status.
                                <?php else: ?>
                                    Document status: <?php echo htmlspecialchars((string) $profile_details->is_verified, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>. Contact support if you need help.
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="cr-profile__status <?php echo $profile_details->is_verified === 'Verified' ? 'cr-profile__status--verified' : 'cr-profile__status--pending'; ?>">
                            <?php echo htmlspecialchars((string) $profile_details->is_verified, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                        </span>
                    </section>
                </div>
                <div class="col-lg-12 my-3">
                    <section class="card cr-profile__bookings" aria-labelledby="my-bookings-title">
                        <div class="card-body">
                            <h2 id="my-bookings-title">My car bookings</h2>
                            <?php if (!$profileBookings): ?>
                                <p>You do not have a confirmed booking yet. Document verification and car booking are separate.</p>
                                <a class="btn btn-primary" href="./cars.php">Browse available cars</a>
                            <?php else: ?>
                                <?php foreach ($profileBookings as $profileBooking): ?>
                                    <div class="cr-profile__booking-item">
                                        <h5>
                                            <a href="./view-my-booking.php?id=<?php echo (int) $profileBooking['booking_record_id']; ?>">
                                                <?php echo htmlspecialchars((string) $profileBooking['car_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                            </a>
                                        </h5>
                                        <p>Booking ID: <?php echo htmlspecialchars((string) $profileBooking['booking_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                                        <p>Booking status: <strong><?php echo htmlspecialchars((string) $profileBooking['booking_status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong></p>
                                        <p>Payment method: <strong><?php echo htmlspecialchars(app_payment_method_label($profileBooking['payment_mode']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong></p>
                                        <p>Payment status: <strong><?php echo htmlspecialchars(app_payment_status_label($profileBooking['payment_mode'], $profileBooking['payment_status']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong></p>
                                        <?php if (strtolower((string) $profileBooking['payment_mode']) === 'cash_on_delivery' && strtolower((string) $profileBooking['payment_status']) !== 'success'): ?>
                                            <p>Pay in cash when the vehicle is handed over.</p>
                                        <?php endif; ?>
                                        <?php if ($profileBooking['booking_status'] === 'Booked' && strtolower((string) $profileBooking['payment_status']) === 'success'): ?>
                                            <p>Vehicle delivery progress: <strong><?php echo htmlspecialchars((string) $profileBooking['delivery_status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                                                <small class="text-muted">(manual update; not live GPS)</small>
                                            </p>
                                        <?php endif; ?>
                                        <a href="./view-my-booking.php?id=<?php echo (int) $profileBooking['booking_record_id']; ?>">View booking and progress</a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
                <div class="col-lg-12 my-2">
                    <div class="card w-100 cr-profile__intro">
                        <div class="card-body">
                                     
                            <h2>Profile and verification documents</h2>
                            <p>Update your contact details and submit documents for review.</p>
                           
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card cr-profile__form-card">
                        <div class="card-body">
                            <form id="formProfile" method="post" action=""
                                enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($profileCsrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="name">Name</label>
                                        <input type="hidden" class="form-control" id="id" name="id"
                                            value="<?php echo (int) $user->id ?>">
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="<?php echo htmlspecialchars((string) $profile_details->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" placeholder="Name" required>
                                        <small id="nameError" class="form-text text-danger"></small>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars((string) $profile_details->email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" placeholder="Email" required>
                                        <small id="emailError" class="form-text text-danger"></small>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="inputAddress2">Phone</label>
                                        <input type="text" class="form-control" name="phone" id="phone"
                                        value="<?php echo htmlspecialchars((string) $profile_details->phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" placeholder="Phone" required>
                                        <small id="phoneError" class="form-text text-danger"></small>
                                    </div>
                                </div>
                    
                                <div class="form-row">
                                    <div class="form-group col-md-12">
                                        <label for="inputCity">Address</label>
                                        <textarea type="text" class="form-control" id="address" name="address"
                                            placeholder="Apartment, studio, or floor" required><?php echo htmlspecialchars((string) ($profile_details->address ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                                        <small id="addressError" class="form-text text-danger"></small>
                                    </div>
                                </div>
                                <div class="form-row">

                                    <div class="form-group col-md-4">
                                        <label for="app-no">Driving License Number</label>
                                        <input type="text" class="form-control" id="driving-license-no" name="driving-license-no"
                                            value="<?php echo htmlspecialchars((string) ($profile_details->driving_license_no ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" placeholder="Driving licence number" required>
                                        <small id="dlNoError" class="form-text text-danger"></small>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="pdf">Driving License Front Image <strong class="text-danger"></strong></label>
                                        <input type="file" class="form-control" id="driving-license-image1" name="driving-license-image1" accept="image/jpeg,image/png">
                                        <small class="form-text text-muted">JPG or PNG, up to 300 KB. Leave blank to keep the current file.</small>
                                        <small id="drivingLicenseImage1Error" class="form-text text-danger"></small>
                                    </div>
   
                                    <div class="form-group col-md-4">
                                        <label for="pdf">Driving License Back Image  <strong class="text-danger"></strong></label>
                                        <input type="file" class="form-control" id="driving-license-image2" name="driving-license-image2" accept="image/jpeg,image/png">
                                        <small class="form-text text-muted">JPG or PNG, up to 300 KB. Leave blank to keep the current file.</small>
                                        <small id="drivingLicenseImage2Error" class="form-text text-danger"></small>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                  
                                    </div>
                                    <div class="form-group col-md-4">
                                    <?php if($fileName1 !== ''): ?>

                                        <img src="./admin/uploaded-files/customer-doc/DL/<?php echo htmlspecialchars($fileName1, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            height="100px" width="100px" alt="...">
                                        <?php endif ?>
                                    </div>
                                    <div class="form-group col-md-4">
                                    <?php if($fileName2 !== ''): ?>

                                        <img src="./admin/uploaded-files/customer-doc/DL/<?php echo htmlspecialchars($fileName2, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            height="100px" width="100px" alt="...">
                                        <?php endif ?>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="inputCity">Select Address Proof</label>
                                         <select class="form-control">
                                             <option value=""></option>
                                          </select>
                                        <small id="addressProofError" class="form-text text-danger"></small>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="app-no">ID Proof Number</label>
                                        <input type="text" class="form-control" id="address-proof-no" name="address-proof-no"
                                            value="<?php echo htmlspecialchars((string) ($profile_details->address_proof_no ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" placeholder="Document number" required>
                                        <small id="addressProofNoError" class="form-text text-danger"></small>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="pdf">Address Proof Image  <strong class="text-danger"></strong></label>
                                        <input type="file" class="form-control" id="address-proof-image" name="address-proof-image" accept="image/jpeg,image/png">
                                        <small class="form-text text-muted">JPG or PNG, up to 300 KB. Leave blank to keep the current file.</small>
                                        <small id="addressProofImageError" class="form-text text-danger"></small>
                                    </div>
                                    <div class="form-group col-md-4">
                                    <?php if($addressProofFileName !== ''): ?>

                                        <img src="./admin/uploaded-files/customer-doc/address-proof/<?php echo htmlspecialchars($addressProofFileName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            height="100px" width="100px" alt="Current address proof document">
                                        <?php endif ?>
                                    </div>
                                </div>
                                <div class="form-row text-right">
                                    <div class="form-group col-lg-4 col-sm-12 offset-lg-8">
                                        <button type="submit" class="btn btn-primary btn-block">submit</button>
                                    </div>
                                </div>
                            </form>
                            <div id="msg"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section><!-- #intro -->
</main>
<?php include './footer.php' ?>

<script>
const nameError = document.querySelector('#nameError');
const emailError = document.querySelector('#emailError');
const phoneError = document.querySelector('#phoneError');
const addressError = document.querySelector('#addressError');
const dlNoError = document.querySelector('#dlNoError');
const drivingLicenseImage1Error = document.querySelector('#drivingLicenseImage1Error');
const drivingLicenseImage2Error = document.querySelector('#drivingLicenseImage2Error');
const addressProofError = document.querySelector('#addressProofError');
const addressProofNoError = document.querySelector('#addressProofNoError');
const addressProofImageError = document.querySelector('#addressProofImageError');


const formProfile = document.querySelector('#formProfile');
const profileMessage = document.querySelector('#msg');
const profileSubmit = formProfile.querySelector('[type="submit"]');

function showProfileMessage(type, message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type;
    alert.textContent = message;
    profileMessage.replaceChildren(alert);
}

formProfile.addEventListener('submit', function(event) {
    event.preventDefault();
    const data = new FormData(this);
    clearErrorMessage();
    profileMessage.replaceChildren();
    profileSubmit.disabled = true;
    fetch('./src/profile-update.php', {
            method: 'POST',
            body: data
        })
        .then(res => {
            return res.json().then(json => {
                if (!res.ok) {
                    if (json.errors) displayErrors(json.errors);
                    throw new Error(json.msg || 'Could not update your profile.');
                }
                return json;
            });
        })
        .then(json => {
            showProfileMessage('success', json.msg);
        })
        .catch(error => {
            showProfileMessage('danger', error.message || 'Could not update your profile. Check your connection and try again.');
        })
        .finally(() => {
            profileSubmit.disabled = false;
        });
})

function displayErrors(error) {
    nameError.textContent = error.name || '';
    emailError.textContent = error.email || '';
    phoneError.textContent = error.phone || '';
    addressError.textContent = error.address || '';
    dlNoError.textContent = error.driving_license_no || '';
    drivingLicenseImage1Error.textContent = error.driving_license_image1 || '';
    drivingLicenseImage2Error.textContent = error.driving_license_image2 || '';
    addressProofNoError.textContent = error.address_proof_no || '';
    addressProofImageError.textContent = error.address_proof_image || '';

}

function clearErrorMessage() {
    nameError.textContent = '';
    emailError.textContent = '';
    phoneError.textContent = '';
    addressError.textContent = '';
    dlNoError.textContent = '';
    drivingLicenseImage1Error.textContent = '';
    drivingLicenseImage2Error.textContent = '';
    addressProofNoError.textContent = '';
    addressProofImageError.textContent = '';

}
</script>