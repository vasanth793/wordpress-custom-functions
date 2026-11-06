<?php
/* Template Name: Delivery Status Date Wise */
get_header();

// Check if the user is logged in and has staff or admin role
if (!is_user_logged_in() || !(current_user_can('administrator') || current_user_can('manager'))) {
    echo "<p>You do not have permission to view this page.</p>";
    get_footer();
    exit;
}

// Fetch all orders with specified statuses
$args = array(
    'limit' => -1,
    'status' => array('wc-processing', 'wc-on-hold', 'wc-completed')
);

$orders = wc_get_orders($args);
$deliveries_by_date = [];
$deliveries_by_apartment_today = [];
$deliveries_by_apartment_tomorrow = [];
$today_date = date('d-m-Y');
$tomorrow_date = date('d-m-Y', strtotime('+1 day'));

// Organize delivery data by date and apartment
foreach ($orders as $order) {
    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $apartment = get_post_meta($order->get_id(), '_billing_apartment', true); // Fetch the apartment meta field

    foreach ($order->get_items() as $item_id => $item) {
        $delivery_dates = $item->get_meta('_delivery_times', true);
        
        if (empty($delivery_dates) || !is_array($delivery_dates)) {
            $legacy_dates = $item->get_meta('_delivery_date', true);
            if (is_array($legacy_dates)) {
                $delivery_dates = $legacy_dates;
            } elseif (!empty($legacy_dates)) {
                $delivery_dates = array_map('trim', explode(',', $legacy_dates));
            } else {
                $selected_dates = $item->get_meta('Selected Dates', true);
                if ($selected_dates) {
                    $delivery_dates = array_map('trim', explode(',', $selected_dates));
                } else {
                    $delivery_dates = [];
                }
            }
        }
        
        $quantity = $item->get_quantity(); // Get the quantity of the item

        if (!empty($delivery_dates)) {
            foreach ($delivery_dates as $date) {
                $status = $item->get_meta('_delivery_status_' . $date);
                $formatted_date = date('d-m-Y', strtotime($date));

                // Add to the date-wise array
                $deliveries_by_date[$formatted_date][] = array(
                    'order_id' => $order->get_id(),
                    'product' => $item->get_name(),
                    'customer_name' => $customer_name,
                    'quantity' => $quantity,
                    'apartment' => $apartment, // Add apartment data
                    'status' => $status ? ucfirst($status) : 'Pending'
                );

                // If the date is today, organize by apartment
                if ($formatted_date === $today_date) {
                    $deliveries_by_apartment_today[$apartment][] = array(
                        'order_id' => $order->get_id(),
                        'product' => $item->get_name(),
                        'customer_name' => $customer_name,
                        'quantity' => $quantity,
                        'status' => $status ? ucfirst($status) : 'Pending'
                    );
                }

                // If the date is tomorrow, organize by apartment
                if ($formatted_date === $tomorrow_date) {
                    $deliveries_by_apartment_tomorrow[$apartment][] = array(
                        'order_id' => $order->get_id(),
                        'product' => $item->get_name(),
                        'customer_name' => $customer_name,
                        'quantity' => $quantity,
                        'status' => $status ? ucfirst($status) : 'Pending'
                    );
                }
            }
        }
    }
}

?>

<div class="delivery-status" style="display: flex; margin: 180px auto 0px auto; max-width: 1400px; flex-wrap: wrap;">
    
    <!-- Your sidebar and main content goes here... -->

    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Menu</h3>
        <ul>
            <li><a href="<?php echo site_url('/add-product'); ?>" class="<?php echo (is_page('add-product') ? 'active-menu' : ''); ?>">Add Products</a></li>
            <li><a href="<?php echo site_url('/status'); ?>" class="<?php echo (is_page('status') ? 'active-menu' : ''); ?>">Delivery</a></li>
            <li><a href="<?php echo site_url('/order-list'); ?>" class="<?php echo (is_page('order-list') ? 'active-menu' : ''); ?>">Order Details</a></li>
            <li><a href="<?php echo site_url('/edit-products'); ?>" class="<?php echo (is_page('edit-products') ? 'active-menu' : ''); ?>">Edit Products</a></li>
            <li><a href="<?php echo site_url('/add-offers'); ?>" class="<?php echo (is_page('add-offers') ? 'active-menu' : ''); ?>">Add Offers</a></li>
            <li><a href="<?php echo site_url('/apartments'); ?>" class="<?php echo (is_page('apartments') ? 'active-menu' : ''); ?>">Add Apartments</a></li>
            <li><a href="<?php echo site_url('/category'); ?>" class="<?php echo (is_page('category') ? 'active-menu' : ''); ?>">Add Category</a></li>
            
        </ul>
    </div>

    <div class="main-content" style="flex: 1; padding: 20px;">
        <h2 style="margin-bottom: 20px;">Delivery Status by Date</h2>

        <!-- Download PDF Button -->
        <button id="download-pdf" class="filter-button" style="padding: 10px 20px; margin-bottom: 20px;">
            Download PDF
        </button>

        <!-- All, Today, Tomorrow Buttons, and Date Picker -->
    <button id="all-button" onclick="filterResults('all')" class="filter-button" style="padding: 10px 20px; margin-bottom: 20px;">
        All
    </button>
    <button id="today-button" onclick="filterResults('today')" class="filter-button" style="padding: 10px 20px; margin-bottom: 20px;">
        Today Delivery's
    </button>
    <button id="tomorrow-button" onclick="filterResults('tomorrow')" class="filter-button" style="padding: 10px 20px; margin-bottom: 20px;">
        Tomorrow Delivery's
    </button>
    <input type="date" id="date-picker" onchange="filterResults('datePicker')" style="padding: 10px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #ddd;">
    
    <?php if (empty($deliveries_by_date)) : ?>
        <p>No delivery statuses available.</p>
    <?php else : ?>
        <!-- Show All Dates -->
        <?php foreach ($deliveries_by_date as $date => $deliveries) : ?>
            <div class="date-section" data-date="<?php echo esc_attr($date); ?>">
                <h3 style="margin-top: 20px; border-bottom: 1px solid #ddd;"><?php echo esc_html($date); ?></h3>
                <div class="table-wrapper">
                    <table class="responsive-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 8px;">Order ID</th>
                            <th style="border: 1px solid #ddd; padding: 8px;">Customer Name</th>
                            <th style="border: 1px solid #ddd; padding: 8px;">Product</th>
                            <th style="border: 1px solid #ddd; padding: 8px;">Quantity</th>
                            <th style="border: 1px solid #ddd; padding: 8px;">Apartment</th> <!-- New column for apartment -->
                            <th style="border: 1px solid #ddd; padding: 8px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deliveries as $delivery) : ?>
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['order_id']); ?></td>
                                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['customer_name']); ?></td>
                                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['product']); ?></td>
                                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['quantity']); ?></td>
                                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['apartment']); ?></td> <!-- Display apartment -->
                                <td style="border: 1px solid #ddd; padding: 8px; color: <?php echo ($delivery['status'] === 'Delivered') ? 'green' : (($delivery['status'] === 'Rejected') ? 'red' : 'black'); ?>;">
                                    <?php echo esc_html($delivery['status']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Today Delivery's Table by Apartment -->
        <div id="today-apartment-delivery" style="display: none;">
            <?php foreach ($deliveries_by_apartment_today as $apartment => $deliveries) : ?>
                <div class="apartment-section" data-apartment="<?php echo esc_attr($apartment); ?>">
                    <h3 style="margin-top: 20px; border-bottom: 1px solid #ddd;"><?php echo esc_html($apartment); ?> - Today Deliveries</h3>
                    <div class="table-wrapper">
                        <table class="responsive-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid #ddd; padding: 8px;">Order ID</th>
                                <th style="border: 1px solid #ddd; padding: 8px;">Customer Name</th>
                                <th style="border: 1px solid #ddd; padding: 8px;">Product</th>
                                <th style="border: 1px solid #ddd; padding: 8px;">Quantity</th>
                                <th style="border: 1px solid #ddd; padding: 8px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveries as $delivery) : ?>
                                <tr>
                                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['order_id']); ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['customer_name']); ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['product']); ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['quantity']); ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px; color: <?php echo ($delivery['status'] === 'Delivered') ? 'green' : 'black'; ?>;">
                                        <?php echo esc_html($delivery['status']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tomorrow Delivery's Table by Apartment -->
        <div id="tomorrow-apartment-delivery" style="display: none;">
            <?php foreach ($deliveries_by_apartment_tomorrow as $apartment => $deliveries) : ?>
                <div class="apartment-section" data-apartment="<?php echo esc_attr($apartment); ?>">
                    <h3 style="margin-top: 20px; border-bottom: 1px solid #ddd;"><?php echo esc_html($apartment); ?> - Tomorrow Deliveries</h3>
                    <div class="table-wrapper">
                        <table class="responsive-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid #ddd; padding: 8px;">Order ID</th>
                                <th style="border: 1px solid #ddd; padding: 8px;">Customer Name</th>
                                <th style="border: 1px solid #ddd; padding: 8px;">Product</th>
                                <th style="border: 1px solid #ddd; padding: 8px;">Quantity</th>
                                <th style="border: 1px solid #ddd; padding: 8px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveries as $delivery) : ?>
                                <tr>
                                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['order_id']); ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['customer_name']); ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['product']); ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo esc_html($delivery['quantity']); ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px; color: <?php echo ($delivery['status'] === 'Delivered') ? 'green' : 'black'; ?>;">
                                        <?php echo esc_html($delivery['status']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
    </div>
</div>

<style>
/* Active button styling */
.filter-button {
    background-color: #2E1C12;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.active-filter {
    background-color: #FE8806;
}
.container {
    display: flex;
    margin: 150px auto 0;
    max-width: 1200px;
    width: 100%;
}

.sidebar {
    width: 25%;
    padding: 20px;
    background-color: #2e1c12;
    border-right: 1px solid #ddd;
    box-shadow: 0 2px 10px #2e1c12;
}

.sidebar h3 {
    margin-top: 0;
    color: #fff;
}

.sidebar ul {
    list-style-type: none;
    padding-left: 0;
    color: #fff;
}

.sidebar ul li {
    margin-bottom: 10px;
}

.sidebar ul li a {
    text-decoration: none;
    color: #fff;
    padding: 5px;
    border-radius: 4px;
    transition: background-color 0.3s, color 0.3s;
}

.main-content {
    flex: 1;
    padding: 20px;
    max-height: 80vh;
    overflow-y: auto;
}

.active-menu {
    color: #FE8806 !important;
    font-weight: bold;
    background-color: #f2f2f2;
    padding: 10px;
    border-radius: 4px;
    display: block;
}

.table-wrapper {
    width: 100%;
    margin-bottom: 20px;
}

.responsive-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed; /* Ensures table adjusts to available width */
}

.responsive-table th,
.responsive-table td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
    word-wrap: break-word; /* Wraps text to prevent overflow */
}

.responsive-table th {
    background-color: #f4f4f4;
}

@media (max-width: 768px) {
    .responsive-table th,
    .responsive-table td {
        font-size: 14px; /* Smaller font size for mobile */
        padding: 8px; /* Reduce padding for mobile */
    }

    .responsive-table td {
        white-space: normal; /* Allow text to wrap on smaller screens */
    }
}

@media (max-width: 480px) {
    .responsive-table th,
    .responsive-table td {
        font-size: 12px; /* Further reduce font size for very small screens */
        padding: 6px;
    }
}
</style>

<!-- Include HTML2PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.js"></script>

<script>

function filterResults(type) {
        const allButton = document.getElementById('all-button');
        const todayButton = document.getElementById('today-button');
        const tomorrowButton = document.getElementById('tomorrow-button');
        const datePicker = document.getElementById('date-picker');
        const todaySection = document.querySelectorAll('.date-section');
        const todayApartmentSection = document.getElementById('today-apartment-delivery');
        const tomorrowApartmentSection = document.getElementById('tomorrow-apartment-delivery');
        
        allButton.classList.remove('active');
        todayButton.classList.remove('active');
        tomorrowButton.classList.remove('active');

        // Hide all date sections
        todaySection.forEach(function(section) {
            section.style.display = 'none';
        });
        
        todayApartmentSection.style.display = 'none'; // Hide apartment-based section for today
        tomorrowApartmentSection.style.display = 'none'; // Hide apartment-based section for tomorrow
        
        if (type === 'all') {
            todaySection.forEach(function(section) {
                section.style.display = 'block';
            });
            allButton.classList.add('active');
        } else if (type === 'today') {
            todayApartmentSection.style.display = 'block'; // Show today apartment deliveries
            todayButton.classList.add('active');
        } else if (type === 'tomorrow') {
            tomorrowApartmentSection.style.display = 'block'; // Show tomorrow apartment deliveries
            tomorrowButton.classList.add('active');
        } else if (type === 'datePicker') {
            const selectedDate = datePicker.value;
            if (selectedDate) {
                const parts = selectedDate.split('-');
                if (parts.length === 3) {
                    const formattedDate = parts[2] + '-' + parts[1] + '-' + parts[0]; // DD-MM-YYYY
                    todaySection.forEach(function(section) {
                        if (section.getAttribute('data-date') === formattedDate) {
                            section.style.display = 'block';
                        } else {
                            section.style.display = 'none';
                        }
                    });
                }
            } else {
                todaySection.forEach(function(section) {
                    section.style.display = 'block';
                });
                allButton.classList.add('active');
            }
        }
    }

// JavaScript to trigger the PDF download
document.getElementById('download-pdf').addEventListener('click', function() {
    // Get the entire content of the page or specific table
    const element = document.querySelector('.main-content'); // You can change this selector to target specific sections
    
    // Set options for PDF formatting
    const options = {
        filename: 'delivery_status.pdf',
        html2canvas: { scale: 4 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    // Generate and download the PDF
    html2pdf().from(element).set(options).save();
});
</script>

<?php get_footer(); ?>
