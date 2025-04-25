<?php
/*
--------------------------TODO--------------------------

FEATURE:
[] Add date fields and filters to activities (Ability to set certain activities for certain days of the week OR for specific dates)
[] Test moving cabins to individual pages/integrate calendar to a cabin page
[] Move activity suggestion to the checkout screen and make the UX better

DATABASE:
[] Add price field to reservations table
[] Unify database field names for cabins and activities tables to avoid repetitive function calls

RESEARCH
[X] Airbnb integration? Possible inly if the customer has listed cabins on airbnb and is able to provide .ics URLs for 2 way communication between calendars.
[] Paytrail integration?
[] Unit testing? not sure if that is a thing in WP

QUALITY OF LIFE:
[] Separate scripts to more ceherent form
[] Delete unnecessary and empty files
[] Make the interaction more secure & smoother
[] Make it dummy proof, easy to integrate to new sites
[] Create tests

STYLES:
[] Make styling easier (Color variables: primary, secondary, background, accent etc)
*/

// Fetch available cabins and activities from the database
global $wpdb;
$upload_dir = wp_upload_dir(); // Get the upload directory
$cabins_base_url = $upload_dir['baseurl'] . '/cabins/'; // Define the base URL for cabin images
$activities_base_url = $upload_dir['baseurl'] . '/activities/'; // Define the base URL for activity images

$cabins = $wpdb->get_results("SELECT cab_id, cab_name, cab_data, cab_image, cab_price, cab_location FROM {$wpdb->prefix}hbc_cabins", ARRAY_A);
$activities = $wpdb->get_results("SELECT act_id, act_name, act_location, act_image, act_price, act_data FROM {$wpdb->prefix}hbc_activities", ARRAY_A);
?>
<div id="hbc-booking-container">
    <!-- Cabin List (Left Side) -->
    <div id="hbc-cabin-list">
        <h2>Select a Cabin</h2>
        <div class="hbc-cabins">
            <?php foreach ($cabins as $cabin): ?>
                <div class="hbc-cabin" data-cabin-id="<?php echo esc_attr($cabin['cab_id']); ?>">
                    <img src="<?php echo esc_url($cabins_base_url . $cabin['cab_image']); ?>" alt="<?php echo esc_attr($cabin['cab_name']); ?>" loading="lazy">
                    <h3><?php echo esc_html($cabin['cab_name']); ?></h3>
                    <h4><?php echo esc_html($cabin['cab_price']); ?>€/Night</h4>
                    <p><?php echo esc_html($cabin['cab_data']); ?></p>
                    <button class="select-cabin-btn">Select Cabin</button>
                    <button class="remove-cabin-btn" style="display: none;">Remove Cabin Selection</button>
                    <button class="hbc-detail-btn" data-type="cabin" data-id="<?php echo esc_attr($cabin['cab_id']); ?>">View Details</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Booking Form & Calendar (Right Side) -->
    <div id="hbc-calendar-container">
        <h2>Holiday Booking Calendar Test Environment</h2>
        <div id="hbc-booking-block">
            <div id="hbc-calendar-form">
                <input type="hidden" id="hbc-cabin" name="cabin_id" value="">

                <div class="hbc-input-group">
                    <label for="hbc-name">Name:</label>
                    <input type="text" id="hbc-name" required>
                </div>

                <div class="hbc-input-group">
                    <label for="hbc-email">Email:</label>
                    <input type="email" id="hbc-email" required>
                </div>

                <div class="hbc-input-group">
                    <label for="hbc-phone">Phone Number:</label>
                    <input type="tel" id="hbc-phone" required>
                </div>

                <div id="hbc-datepicker"></div> 
            </div>
            <!-- Price counter and booking button -->
            <div id="hbc-pricing-container">
                <h3>Review your booking</h3>

                <div class="hbc-pricing-group">
                    <h4>Cabin</h4>
                    <p id="hbc-reservation-details">None selected.</p>
                </div>

                <div class="hbc-pricing-group">
                    <h4>Your stay</h4>
                    <p id="hbc-selected-dates">Please select a start and end date.</p>
                </div>

                <div class="hbc-pricing-group">
                    <h4>Activities</h4>
                    <p id="hbc-selected-activities">No activities selected.</p>
                </div>

                <div class="hbc-pricing-group" id="hbc-reservation-disclaimer">
                    <h4>Disclaimer</h4>
                    <p>Please confirm all details before booking.</p>
                </div>

                <div class="hbc-pricing-group" id="hbc-reservation-price">
                    <h4>Total Price</h4>
                    <p>0.00€</p>
                </div>

                <div>
                    <button id="hbc-book-btn" style="display: block">Book Now</button>
                    <div id="hbc-message"></div>
                </div>
            </div>
        </div>
        <!-- Activities Section (Displayed After Dates Selected) -->
        <div id="hbc-activities-container" style="display: none;">
            <h3>Enhance your experience with activities</h3>
            <div class="hbc-activities">
                <?php foreach ($activities as $activity): ?>
                    <div class="hbc-activity" data-activity-id="<?php echo esc_attr($activity['act_id']); ?>">
                        <img src="<?php echo esc_url($activities_base_url . $activity['act_image']); ?>" alt="<?php echo esc_attr($activity['act_name']); ?>" loading="lazy">
                        <h3><?php echo esc_html($activity['act_name']); ?></h3>
                        <h3><?php echo esc_html($activity['act_price']); ?>€</h3>
                        <p><?php echo esc_html($activity['act_data']); ?></p>
                        <button class="select-activity-btn">Select Activity</button>
                        <button class="remove-activity-btn" style="display: none;">Remove Activity Selection</button>
                        <button class="hbc-detail-btn" data-type="activity" data-id="<?php echo esc_attr($activity['act_id']); ?>">View Details</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<div id="hbc-popup-overlay" style="display: none;">
    <div id="hbc-popup">
        <span id="hbc-popup-close">&times;</span>
        <div id="hbc-popup-content"></div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<?php
