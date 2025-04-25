<?php

// Admin Page Content
function hbc_admin_dash_page() {
    global $wpdb;

    // Define the number of items per page
    $items_per_page = 5;

    // Get the current page number
    $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
    $offset = ($paged - 1) * $items_per_page;

    // Get cabins with pagination
    $cabins = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT cab_id, cab_name, cab_location, cab_image, cab_price FROM {$wpdb->prefix}hbc_cabins LIMIT %d, %d",
            $offset, $items_per_page
        ),
        ARRAY_A
    );

    // Get activities with pagination
    $activities = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT act_id, act_name, act_location, act_image, act_price FROM {$wpdb->prefix}hbc_activities LIMIT %d, %d",
            $offset, $items_per_page
        ),
        ARRAY_A
    );

    // Get total number of cabins and activities for pagination
    $total_cabins = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hbc_cabins");
    $total_activities = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hbc_activities");

    // Define base URLs for cabin and activity images
    $upload_dir = wp_upload_dir();
    $cabins_base_url = $upload_dir['baseurl'] . '/cabins/';
    $activities_base_url = $upload_dir['baseurl'] . '/activities/';

    ?>
    <div class="wrap">
        <h1>Manage Cabins & Activities</h1>

        <!-- Add New Cabin Form -->
        <h2>Add New Cabin</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="text" name="cab_name" placeholder="Cabin Name" required>
            <input type="text" name="cab_location" placeholder="Location" required>
            <input type="number" name="cab_price" placeholder="Price" required>
            <input type="text" name="cab_data" placeholder="Additional Information" rows="4"></input> <!-- New Data Field -->
            <input type="file" name="cab_image" required>
            <button type="submit" name="add_cabin">Add Cabin</button>
        </form>

        <!-- Existing Cabins Table -->
        <h2>Existing Cabins</h2>
        <table>
            <tr><th>ID</th><th>Name</th><th>Location</th><th>Image</th><th>Price</th><th>Actions</th></tr>
            <?php foreach ($cabins as $cab): ?>
                <tr>
                    <td><?php echo $cab['cab_id']; ?></td>
                    <td><?php echo $cab['cab_name']; ?></td>
                    <td><?php echo $cab['cab_location']; ?></td>
                    <td><img src="<?php echo $cabins_base_url . $cab['cab_image']; ?>" width="50" height="30"></td>
                    <td><?php echo $cab['cab_price']; ?>€/Night</td>
                    <td><a href="?delete_cabin=<?php echo $cab['cab_id']; ?>">Delete</a></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Pagination for Cabins -->
        <div class="pagination">
            <?php
            echo paginate_links(array(
                'total' => ceil($total_cabins / $items_per_page),
                'current' => $paged,
                'format' => '?paged=%#%',
                'add_args' => false,
            ));
            ?>
        </div>

        <!-- Add New Activity Form -->
        <h2>Add New Activity</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="text" name="act_name" placeholder="Activity Name" required>
            <input type="text" name="act_location" placeholder="Location" required>
            <input type="number" name="act_price" placeholder="Price" required>
            <input type="text" name="act_data" placeholder="Additional Information" rows="4"></input> <!-- New Data Field -->
            <input type="file" name="act_image" required>
            <button type="submit" name="add_activity">Add Activity</button>
        </form>

        <!-- Existing Activities Table -->
        <h2>Existing Activities</h2>
        <table>
            <tr><th>ID</th><th>Name</th><th>Location</th><th>Image</th><th>Price</th><th>Actions</th></tr>
            <?php foreach ($activities as $act): ?>
                <tr>
                    <td><?php echo $act['act_id']; ?></td>
                    <td><?php echo $act['act_name']; ?></td>
                    <td><?php echo $act['act_location']; ?></td>
                    <td><img src="<?php echo $activities_base_url . $act['act_image']; ?>" width="50" height="30"></td>
                    <td><?php echo $act['act_price']; ?>€</td>
                    <td><a href="?delete_activity=<?php echo $act['act_id']; ?>">Delete</a></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Pagination for Activities -->
        <div class="pagination">
            <?php
            echo paginate_links(array(
                'total' => ceil($total_activities / $items_per_page),
                'current' => $paged,
                'format' => '?paged=%#%',
                'add_args' => false,
            ));
            ?>
        </div>
    </div>
    <?php
}

// Handle Add Cabin
function hbc_handle_add_cabin() {
    if (isset($_POST['add_cabin'])) {
        // Check if the user is logged in and has permissions
        if (!current_user_can('manage_options')) {
            return; // No permissions to add cabin
        }

        // Get the upload directory info
        $upload_dir = wp_upload_dir();
        $cabin_dir = $upload_dir['basedir'] . '/cabins'; // Define the cabins folder within the uploads directory

        // Create the cabins folder if it doesn't exist
        if (!file_exists($cabin_dir)) {
            mkdir($cabin_dir, 0755, true); // Make the directories recursively if needed
        }

        // Upload the cabin image
        if (isset($_FILES['cab_image']) && !empty($_FILES['cab_image']['name'])) {
            // Move the uploaded image to the cabins folder
            $upload_file = $cabin_dir . '/' . basename($_FILES['cab_image']['name']);
            
            // Move the uploaded file to the cabins folder
            if (move_uploaded_file($_FILES['cab_image']['tmp_name'], $upload_file)) {
                global $wpdb;
                $wpdb->insert("{$wpdb->prefix}hbc_cabins", [
                    'cab_name' => sanitize_text_field($_POST['cab_name']),
                    'cab_location' => sanitize_text_field($_POST['cab_location']),
                    'cab_price' => floatval($_POST['cab_price']),
                    'cab_data' => sanitize_textarea_field($_POST['cab_data']),
                    'cab_image' => basename($upload_file),
                ]);
                wp_redirect(admin_url('admin.php?page=hbc_reservations'));
                exit;
            } else {
                echo 'Error uploading file: Unable to move file to cabin directory';
            }
        }
    }
}
add_action('admin_init', 'hbc_handle_add_cabin');


// Handle Add Activity
function hbc_handle_add_activity() {
    if (isset($_POST['add_activity'])) {
        // Check if the user is logged in and has permissions
        if (!current_user_can('manage_options')) {
            return; // No permissions to add activity
        }

        // Get the upload directory info
        $upload_dir = wp_upload_dir();
        $activity_dir = $upload_dir['basedir'] . '/activities'; // Define the activities folder within the uploads directory

        // Create the activities folder if it doesn't exist
        if (!file_exists($activity_dir)) {
            mkdir($activity_dir, 0755, true); // Make the directories recursively if needed
        }

        // Upload the activity image
        if (isset($_FILES['act_image']) && !empty($_FILES['act_image']['name'])) {
            // Move the uploaded image to the activities folder
            $upload_file = $activity_dir . '/' . basename($_FILES['act_image']['name']);
            
            // Move the uploaded file to the activities folder
            if (move_uploaded_file($_FILES['act_image']['tmp_name'], $upload_file)) {
                global $wpdb;
                $wpdb->insert("{$wpdb->prefix}hbc_activities", [
                    'act_name' => sanitize_text_field($_POST['act_name']),
                    'act_location' => sanitize_text_field($_POST['act_location']),
                    'act_price' => floatval($_POST['act_price']),
                    'act_data' => sanitize_textarea_field($_POST['act_data']),
                    'act_image' => basename($upload_file), 
                ]);
                wp_redirect(admin_url('admin.php?page=hbc_reservations'));
                exit;
            } else {
                echo 'Error uploading file: Unable to move file to activity directory';
            }
        }
    }
}
add_action('admin_init', 'hbc_handle_add_activity');


// Handle Delete Cabin
function hbc_handle_delete_cabin() {
    if (isset($_GET['delete_cabin'])) {
        // Check if the user is logged in and has permissions
        if (!current_user_can('manage_options')) {
            return; // No permissions to delete cabin
        }

        global $wpdb;
        $cab_id = intval($_GET['delete_cabin']);
        $wpdb->delete("{$wpdb->prefix}hbc_cabins", ['cab_id' => $cab_id]);

        wp_redirect(admin_url('admin.php?page=hbc_reservations'));
        exit;
    }
}
add_action('admin_init', 'hbc_handle_delete_cabin');

// Handle Delete Activity
function hbc_handle_delete_activity() {
    if (isset($_GET['delete_activity'])) {
        // Check if the user is logged in and has permissions
        if (!current_user_can('manage_options')) {
            return; // No permissions to delete activity
        }

        global $wpdb;
        $act_id = intval($_GET['delete_activity']);
        $wpdb->delete("{$wpdb->prefix}hbc_activities", ['act_id' => $act_id]);

        wp_redirect(admin_url('admin.php?page=hbc_reservations'));
        exit;
    }
}
add_action('admin_init', 'hbc_handle_delete_activity');
?>