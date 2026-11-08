<?php
$detailUrl = app_car_detail_url(
    $car['id'],
    $startValue,
    $endValue,
    $city
);
$carImage = app_car_image_url($car['image1']);
?>
<article class="cr-car-card">
    <a class="cr-car-card__image" href="<?php echo app_escape($detailUrl); ?>" tabindex="-1" aria-hidden="true">
        <img src="<?php echo app_escape($carImage); ?>"
            alt=""
            loading="lazy"
            onerror="this.onerror=null;this.src='img/car3.png';">
        <span class="cr-car-card__badge"><?php echo app_escape($car['car_body_type'] ?: 'Available'); ?></span>
        <?php if ((int) $car['id'] === (int) $budgetCarId): ?>
            <span class="cr-car-card__badge cr-car-card__badge--budget">Budget pick</span>
        <?php endif; ?>
    </a>
    <div class="cr-car-card__body">
        <div class="cr-car-card__heading">
            <div>
                <p class="cr-car-card__brand"><?php echo app_escape($car['brand_name'] ?: 'Car Rental'); ?></p>
                <h3><?php echo app_escape($car['car_name']); ?></h3>
            </div>
            <p class="cr-car-card__price">
                <strong>₹<?php echo number_format((float) $car['price_per_day']); ?></strong>
                <span>approx. per day</span>
                <small>Rental is charged in 24-hour blocks</small>
            </p>
        </div>
        <ul class="cr-car-card__specs" aria-label="Vehicle specifications">
            <li><?php echo app_escape($car['transmission']); ?></li>
            <li><?php echo app_escape($car['fuel_type']); ?></li>
            <li><?php echo app_escape($car['seating_capacity']); ?> seats</li>
        </ul>
        <?php if (isset($car['match_score'], $car['match_reasons']) && is_array($car['match_reasons'])): ?>
            <div class="cr-car-card__match">
                <strong><?php echo (int) $car['match_score']; ?>% preference fit</strong>
                <ul>
                    <?php foreach ($car['match_reasons'] as $reason): ?>
                        <li><?php echo app_escape($reason); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <a class="cr-car-card__link" href="<?php echo app_escape($detailUrl); ?>">
            View this car <span aria-hidden="true">→</span>
        </a>
    </div>
</article>
