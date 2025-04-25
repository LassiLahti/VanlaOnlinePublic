/* admin.js */
// Example: You can add custom JS functionality here
jQuery(document).ready(function($) {
    // Placeholder for future JS logic if needed.
    console.log("HBC Admin Page Loaded");

    // Example: Adding a confirmation dialog on delete
    $('a[href*="delete_cabin"], a[href*="delete_activity"]').click(function(e) {
        var confirmation = confirm("Are you sure you want to delete this item?");
        if (!confirmation) {
            e.preventDefault(); // Prevents the action if not confirmed
        }
    });
});