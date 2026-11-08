<?php
include './header.php';
require_once '../src/Database.php';

if (!isset($_SESSION['admin_car_csrf_token']) || !is_string($_SESSION['admin_car_csrf_token'])) {
    $_SESSION['admin_car_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_car_csrf_token'];

$sql = "SELECT * FROM brands ORDER BY id DESC";
$res = $db->query($sql);
$brands = [];
while ($row = $res->fetch_object()) {
    $brands[] = $row;
}

?>

<div id="content-wrapper">
    <div class="container-fluid">
        <!-- Breadcrumbs-->
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="./dashboard.php">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="./cars.php">Cars</a>
            </li>
            <li class="breadcrumb-item active">Add car</li>
        </ol>
        <?php if (isset($msg)): ?>
            <div class="alert alert-success" role="alert"><?php echo $msg ?></div>
        <?php endif ?>
        <!-- Icon Cards-->
        <div class="card">
            <div class="card-header text-uppercase font-weight-bold">
                <i class="fas fa-plus-circle"></i>
                Add Car
            </div>
            <div class="card-body">
                <form id="formCar" method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                    <div class="form-row">
                        <div class="form-group col-lg-4">
                            <label for="name">Car Name</label>
                            <input type="text" id="car_name" class="form-control" name="car_name"
                                placeholder="Enter car name">
                            <small id="carNameError" class="form-text text-danger"></small>
                        </div>
                        <div class="form-group col-lg-4">
                            <label for="name">Brand</label>
                            <select class="form-control" name="brand">
                            <option value="">--select--</option>
                                <?php foreach ($brands as $brand): ?>
                                  
                                <option value="<?php echo $brand->id ?>"><?php echo $brand->brand_name ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small id="brandError" class="form-text text-danger"></small>
                        </div>
                        <div class="form-group col-lg-4">
                            <label for="name">Car Body Type</label>
                            <input type="text" id="car_body_type" class="form-control" name="car_body_type"
                                placeholder="Enter car body type ">
                            <small id="carBodyTypeError" class="form-text text-danger"></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-lg-4">
                            <label for="name">Transmission</label>
                            <input type="text" id="transmission" class="form-control" name="transmission"
                                placeholder="Enter transmission">
                            <small id="transmissionError" class="form-text text-danger"></small>
                        </div>
                        <div class="form-group col-lg-4">
                            <label for="name">Fuel Type</label>
                            <select class="form-control" name="fuel_type">
                                <option value="Petrol">Petrol</option>
                                <option value="Diesel">Diesel</option>
                                <option value="CNG">CNG</option>
                            </select>
                            <small id="fuelTypeError" class="form-text text-danger"></small>
                        </div>
                        <div class="form-group col-lg-4">
                            <label for="name">Model</label>
                            <input type="text" id="model" class="form-control" name="model"
                                placeholder="Enter model ">
                            <small id="modelError" class="form-text text-danger"></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-lg-3">
                            <label for="name">Seating Capacity</label>
                            <input type="text" id="seating_capacity" class="form-control" name="seating_capacity"
                                placeholder="Enter seating capacity ">
                            <small id="seatingCapacityError" class="form-text text-danger"></small>
                        </div>
                        <div class="form-group col-lg-3">
                            <label for="name">Km Driven</label>
                            <input type="text" id="km_driven" class="form-control" name="km_driven"
                                placeholder="Enter KM driven ">
                            <small id="kmDrivenError" class="form-text text-danger"></small>
                        </div>
                        <div class="form-group col-lg-3">
                            <label for="price_per_day">Indicative Price Per Day (₹)</label>
                            <input type="number" id="price_per_day" class="form-control" name="price_per_day"
                                min="1" max="100000" step="0.01" placeholder="e.g. 1499">
                            <small class="form-text text-muted">Set a 24-hour rate; verify provider pricing and rental terms before publishing.</small>
                            <small id="pricePerDayError" class="form-text text-danger"></small>
                        </div>
                        <div class="form-group col-lg-3">
                            <label for="name">Registration No</label>
                            <input type="text" id="reg_no" class="form-control" name="reg_no"
                                placeholder="Enter reg no">
                            <small id="regNoError" class="form-text text-danger"></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-lg-3">
                            <label for="name">AC</label><br/>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ac"
                                    id="flexRadioDefault1"  value="1">
                                <label class="form-check-label" for="flexRadioDefault1">
                                    Yes
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ac"
                                    id="flexRadioDefault2"  value="0" checked>
                                <label class="form-check-label" for="flexRadioDefault2">
                                    No
                                </label>
                            </div>
                        </div>
                        <div class="form-group col-lg-3">
                            <label for="name">Sun roof</label><br/>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="sun_roof"
                                    id="flexRadioDefault1"  value="1">
                                <label class="form-check-label" for="flexRadioDefault1">
                                    Yes
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="sun_roof"
                                    id="flexRadioDefault2"  value="0" checked>
                                <label class="form-check-label" for="flexRadioDefault2">
                                    No
                                </label>
                            </div>
                        </div>
                        <div class="form-group col-lg-3">
                            <label for="name">Air Bags</label><br/>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="air_bags"
                                    id="flexRadioDefault1"  value="1">
                                <label class="form-check-label" for="flexRadioDefault1">
                                    Yes
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="air_bags"
                                    id="flexRadioDefault2"  value="0" checked>
                                <label class="form-check-label" for="flexRadioDefault2">
                                    No
                                </label>
                            </div>
                        </div>
                        <div class="form-group col-lg-3">
                            <label for="name">Central Lock</label><br/>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="central_lock"
                                    id="flexRadioDefault1" value="1">
                                <label class="form-check-label" for="flexRadioDefault1">
                                    Yes
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="central_lock"
                                    id="flexRadioDefault2"  value="0" checked>
                                <label class="form-check-label" for="flexRadioDefault2">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-lg-12">
                        <label for="name">Description</label>
                        <textarea class="form-control" id="description" name="description" placeholder="Enter description"></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-lg-12">
                        <label for="name">Cars Images</label>
                        <input type="file" multiple name="images[]" class="form-control" placeholder="Select images">
                           <span class="help">Select exactly 3 images of the car.</span>
                            <small id="carImageError" class="form-text text-danger"></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-lg-4">
                            <button type="reset" class="btn btn-danger"><i class="fas fa-eraser"></i> Clear</button>
                            <button type="submit" name="submit" class="btn btn-success"><i class="fas fa-check"></i>
                                Submit</button>
                        </div>
                    </div>
                </form>
                <div id="msg"></div>
            </div>
        </div>
    </div>

    <?php
    include './footer.php';
    ?>

    <script>


        //validation
        const carNameError = document.querySelector('#carNameError');
        const brandError = document.querySelector('#brandError');
        const carBodyTypeError = document.querySelector('#carBodyTypeError');
        const transmissionError = document.querySelector('#transmissionError');
        const fuelTypeError = document.querySelector('#fuelTypeError');
        const modelError = document.querySelector('#modelError');
        const seatingCapacityError = document.querySelector('#seatingCapacityError');
        const kmDrivenError = document.querySelector('#kmDrivenError');
        const pricePerDayError = document.querySelector('#pricePerDayError');
        const regNoError = document.querySelector('#regNoError');
        const carImageError = document.querySelector('#carImageError');

        const formCar = document.querySelector('#formCar');



        formCar.addEventListener('submit', function (event) {
            event.preventDefault();
            let data = new FormData(this);
 
            clearErrorMessage();
            msg.innerHTML = '';
            fetch('../src/car-add.php', {
                method: 'POST',
                body: data
            })
                .then(res => {
                    if (res.status == 200) {
                        res.json().then(json => {
                            msg.innerHTML =
                                '<div class="alert alert-success"><strong><i class="fa fa-check"></i> Success! </strong>' +
                                json.msg + '</div>'
                        })
                    } else {
                        res.json().then(json => {
                            console.log(json.errors)
                            if (json.errors) displayErrors(json.errors);
                            msg.innerHTML =
                                '<div class="alert alert-danger"><strong><i class="fa fa-times"></i> Failed! </strong>' +
                                json.msg + '</div>'
                        })
                    }
                })
                .catch(error => {
                    console.error(error);
                })
        })

        function displayErrors(error) {
            //const fieldCorrectionError = document.querySelector('#fieldCorrectionError');


            carNameError.innerHTML = error.carName;
            brandError.innerHTML = error.brandName;
            carBodyTypeError.innerHTML = error.carBodyType;
            transmissionError.innerHTML = error.trnsmission;
            fuelTypeError.innerHTML = error.fuel_type;
            modelError.innerHTML = error.model;
            seatingCapacityError.innerHTML = error.seating_capacity;
            kmDrivenError.innerHTML = error.km_driven;
            pricePerDayError.innerHTML = error.price_per_day;
            regNoError.innerHTML = error.reg_no;
            carImageError.innerHTML = error.image;
          

        }

        function clearErrorMessage() {


            carNameError.innerHTML = '';
            brandError.innerHTML = '';
            carBodyTypeError.innerHTML = '';
            transmissionError.innerHTML = '';
            fuelTypeError.innerHTML = '';
            modelError.innerHTML = '';
            seatingCapacityError.innerHTML ='';
            kmDrivenError.innerHTML = '';
            pricePerDayError.innerHTML = '';
            regNoError.innerHTML = '';
            carImageError.innerHTML = '';
            

        }
    </script>