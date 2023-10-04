<?php
/*
Plugin Name: Woo Invoices
Description: Custom Woocommerce invoices.
Version: 1.0
*/

add_action('woocommerce_thankyou', 'wi_custom_new_order_email');
function wi_custom_new_order_email( $order_id ) {

        $order = new WC_Order( $order_id );

        $billing_address = $order->get_billing_address_1(); // for printing or displaying on web page
        $shipping_address = $order->get_shipping_address_1();
        $email = $order->get_billing_email();
        $name = $order->get_billing_first_name()  .' ' . $order->get_billing_last_name();
        $billing_phone = $order->get_billing_phone();
        $date = date('M d, Y');
        $kmo_prod_info = false;
        

        $order_items = $order->get_items();

        foreach ( $order_items as $item_id => $item ) {
            // Get the product ID
            if ($item["Ik wil gebruiken maken van KMO-portefeuille"] == 'Ja') {
                $kmo_prod_info = true;
                break;
            }
        }

        $data   = '';
        $data   .= "<table border='0' cellpadding='0' cellspacing='0' width='600'><tbody><tr>
        <td valign='top' style='background-color:#fdfdfd'>
        <table border='0' cellpadding='20' cellspacing='0' width='100%'>
        <tbody>
        <tr>
        <td valign='top' style='padding:48px'>
        <div style='color:#737373;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:14px;line-height:150%;text-align:left'>
        <span>
        <p style='margin:0 0 16px'>
        You have received an order from $name. The order is as follows:
        </p>
        </span>
        <h2 style='color:#557da1;display:block;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:18px;font-weight:bold;line-height:130%;margin:16px 0 8px;text-align:left'>
        Order # $order_id ( $date )
        </h2>
        <div>
        <div>";
        if( sizeof( $order->get_items() ) > 0 ) {           
            $data   .=    "<table cellspacing='0' cellpadding='6' style='width:100%;border:1px solid #eee' border='1'>
            <thead>
            <tr>
            <th scope='col' style='text-align:left;border:1px solid #eee;padding:12px'>
            Product
            </th>
            <th scope='col' style='text-align:left;border:1px solid #eee;padding:12px'>
            Quantity
            </th>
            <th scope='col' style='text-align:left;border:1px solid #eee;padding:12px'>
            Price
            </th>
            </tr>
            </thead>
            <tbody>";
            $data   .= $order->email_order_items_table( false, true );            
            $data   .=  "</tbody><tfoot>";
            if ( $totals = $order->get_order_item_totals() ) {
                $i = 0;
                foreach ( $totals as $total ) {
                $i++;
                $label =    $total['label'];
                $value = $total['value'];
                $data .= "<tr>
                <th scope='row' colspan='2' style='text-align:left; border: 1px solid #eee;'>$label</th>
                <td style='text-align:left; border: 1px solid #eee;'>$value</td>
                </tr>";
                }
            }
            $data .= "</tfoot></table>";
        }

        if ($kmo_prod_info) {
            $data .=  "<span>KMO details<span/>";
        }
        $data .=  "<span>
        <h2 style='color:#557da1;display:block;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:18px;font-weight:bold;line-height:130%;margin:16px 0 8px;text-align:left'>
        Customer details
        </h2>
        <p style='margin:0 0 16px'>
        <strong>Email:</strong>
        <a href='mailto:' target='_blank'>
        $email
        </a>
        </p>
        <p style='margin:0 0 16px'>
        <strong>Tel:</strong>
        $billing_phone
        </p>
        <table cellspacing='0' cellpadding='0' style='width:100%;vertical-align:top' border='0'>
        <tbody>
        <tr>
        <td valign='top' width='50%' style='padding:12px'>
        <h3 style='color:#557da1;display:block;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:16px;font-weight:bold;line-height:130%;margin:16px 0 8px;text-align:left'>Billing address</h3>
        <p style='margin:0 0 16px'> $billing_address </p>
        </td>
        <td valign='top' width='50%' style='padding:12px'>
        <h3 style='color:#557da1;display:block;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:16px;font-weight:bold;line-height:130%;margin:16px 0 8px;text-align:left'>Shipping address</h3>
        <p style='margin:0 0 16px'> $shipping_address </p>
        </td>
        </tr>
        </tbody>
        </table>
        </span>
        </div>
        </td>
        </tr>
        </tbody>
        </table>
        </td>
        </tr>
        </tbody>
        </table>";

        // Generate the invoice HTML
    $invoice_html = generate_invoice($order);

    // Define the plugin directory where you want to temporarily store the invoice
    $plugin_dir = WP_PLUGIN_DIR . '/woo-invoices/invoices/';

    // Create the directory if it doesn't exist
    if (!file_exists($plugin_dir)) {
        mkdir($plugin_dir, 0755, true);
    }

    // Generate a unique filename for the invoice
    $invoice_filename = 'invoice_' . $order_id . '.html';

    // Define the full path to the temporary invoice file
    $temp_invoice_file = $plugin_dir . $invoice_filename;

    // Save the invoice HTML to the temporary file
    file_put_contents($temp_invoice_file, $invoice_html);

    // Get the email headers
    $mailer = WC()->mailer();

    // Attach the temporary invoice file to the email
    $attachments = array($temp_invoice_file);

    $subject = 'New Customer Order';
    
    // Send the email with the invoice attached
    $mailer->send( $email, $subject, $mailer->wrap_message( $subject, $data ), '', $attachments);     
    
    // Delete the temporary invoice file
    unlink($temp_invoice_file);
}
    
// Function to generate the invoice
function generate_invoice($order) {
    // Get order data
    $order_id = $order->get_id();
    $order_date = $order->get_date_created()->format('Y-m-d H:i:s');
    $billing_address = $order->get_formatted_billing_address();
    $order_items = $order->get_items();

    // Start building the HTML invoice
    $html_invoice = '<html><head><style>';
    // Add your CSS styles here to format the invoice as desired.
    $html_invoice .= '
        /* Your CSS styles for the invoice */
        body {
            font-family: Arial, sans-serif;
        }
        /* Add more styles as needed */
    ';
    $html_invoice .= '</style></head><body>';

    // Invoice header
    $html_invoice .= '<h1>Invoice for Order #' . $order_id . '</h1>';
    $html_invoice .= '<p>Order Date: ' . $order_date . '</p>';
    $html_invoice .= '<h2>Billing Address</h2>';
    $html_invoice .= '<address>' . $billing_address . '</address>';

    // Invoice table
    $html_invoice .= '<h2>Order Items</h2>';
    $html_invoice .= '<table border="1">';
    $html_invoice .= '<tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr>';

    foreach ($order_items as $item_id => $item) {
        $product = $item->get_product();
        $product_name = $product->get_name();
        $quantity = $item->get_quantity();
        $price = $item->get_subtotal();
        $total = $item->get_total();

        $html_invoice .= '<tr>';
        $html_invoice .= '<td>' . $product_name . '</td>';
        $html_invoice .= '<td>' . $quantity . '</td>';
        $html_invoice .= '<td>' . wc_price($price) . '</td>';
        $html_invoice .= '<td>' . wc_price($total) . '</td>';
        $html_invoice .= '</tr>';
    }

    $html_invoice .= '</table>';

    // Invoice total
    $html_invoice .= '<h2>Total Amount: ' . wc_price($order->get_total()) . '</h2>';

    // Close the HTML document
    $html_invoice .= '</body></html>';

    // Create a new invoice post
    $invoice_post = array(
        'post_title' => 'Invoice for Order #' . $order_id,
        'post_type' => 'woo_invoices', // Custom post type
        'post_status' => 'publish', // You can change this as needed
    );

    $invoice_id = wp_insert_post($invoice_post);

    // Save specific order data as custom fields
    update_post_meta($invoice_id, '_order_id', $order_id);
    update_post_meta($invoice_id, '_invoice_number', 'order_id_not_working');
    update_post_meta($invoice_id, '_invoice_id', $invoice_id);
    update_post_meta($invoice_id, '_order_date', $order_date);
    update_post_meta($invoice_id, '_billing_address', $billing_address);
    update_post_meta($invoice_id, '_shipping_address', $shipping_address);
    update_post_meta($invoice_id, '_email', $email);
    update_post_meta($invoice_id, '_billing_phone', $billing_phone);
    update_post_meta($invoice_id, '_order_items', $order_items);
    update_post_meta($invoice_id, '_order_status', $order_status);

    // Set the post content to the invoice HTML
    if ($invoice_id) {
        update_post_meta($invoice_id, '_invoice_html', $html_invoice);
    }

    return $html_invoice;
}

// Register a custom post type for invoices
function register_invoice_post_type() {
    $labels = array(
        'name' => 'Woo Invoices',
        'singular_name' => 'Woo Invoice',
        'menu_name' => 'Woo Invoices',
    );

    $args = array(
        'labels' => $labels,
        'public' => false, // Set to true if you want to display invoices in the admin area
        'show_ui' => true,
        'supports' => array('title', 'editor'),
    );

    register_post_type('woo_invoices', $args);
}

add_action('init', 'register_invoice_post_type');

// Add custom meta box to 'woo_invoices' post editor
function add_invoice_custom_fields_meta_box() {
    add_meta_box(
        'invoice_custom_fields',
        'Invoice Custom Fields',
        'display_invoice_custom_fields',
        'woo_invoices',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_invoice_custom_fields_meta_box');

// Display custom fields in the meta box
function display_invoice_custom_fields($post) {
    // Retrieve and display custom fields
    $order_id = get_post_meta($post->ID, '_order_id', true);

    $order = wc_get_order($order_id);
    
    $order_number = get_post_meta($post->ID, '_order_number', true);
    $order_date = get_post_meta($post->ID, '_order_date', true);
    $name = $order->get_data()['billing']['first_name'] . ' ' . $order->get_data()['billing']['last_name'];
    $email = $order->get_data()['billing']['email'];
    $billing_phone = $order->get_data()['billing']['phone'];
    $riziv_number = get_post_meta($order->get_id(), '_billing_riziv_nummer', true);
    $billing_ondernemingsnummer = get_post_meta($order->get_id(), '_billing_ondernemingsnummer', true);
    $order_items = get_post_meta($post->ID, '_order_items', true);
    $discount = $order->get_data()['discount_total']; 
    $order_total = get_post_meta($order->get_id(), '_order_total', true);
    $order_status = $order->get_data()['status'];

    var_dump($order->get_data());


// Check if the order object exists.
if ($order) {
    // Get all metadata for the order.
    $order_metadata = get_post_meta($order->get_id());
    // Loop through and display or work with the metadata.
    foreach ($order_metadata as $key => $value) {
        echo "Meta Key: " . esc_html($key) . "<br>";
        echo "Meta Value: " . esc_html($value[0]) . "<br>";
    }
}
    // Output HTML for custom fields
    echo '<p><strong>Order Date:</strong> ' . esc_html($order_date) . '</p>';
    echo '<p><strong>Customer:</strong> ' . esc_html($name) . '</p>';
    echo '<p><strong>Email:</strong> ' . esc_html($email) . '</p>';
    echo '<p><strong>Phone:</strong> ' . esc_html($billing_phone) . '</p>';
    if ($riziv_number) {
        echo '<p><strong>Riziv Number:</strong> ' . esc_html($riziv_number) . '</p>';
    }   
    if ($billing_ondernemingsnummer) {
        echo '<p><strong>Ondernemings Number:</strong> ' . esc_html($billing_ondernemingsnummer) . '</p>';
    } 

    // Display order items (format as needed)
    if (!empty($order_items)) {
        echo '<p><strong>Order Items:</strong></p>';
        echo '<ul>';
        foreach ($order_items as $item) {
            echo '<li>Name: ' . esc_html($item['name']) . '</li>';
            echo '<li>Quantity: ' . esc_html($item['quantity']) . '</li>';
            echo '<li>Total: ' . esc_html($item['total']) . '</li>';
            echo '<li>KMO: ' . esc_html($item["Ik wil gebruiken maken van KMO-portefeuille"]) . '</li>';
        }
        echo '</ul>';
    }

    echo '<p><strong>Discount Total:</strong> ' . esc_html($discount) . '</p>';
    echo '<p><strong>Total:</strong> ' . esc_html($total) . '</p>';
    echo '<p><strong>Order Status:</strong> ' . esc_html($order_status) . '</p>';
}

//Add custom field to the order when it's created.
add_action( 'woocommerce_checkout_create_order', 'add_custom_field_on_placed_order', 10, 2 );
function add_custom_field_on_placed_order( $order, $data ){
    $order->update_meta_data( '_custom_field_name', 'ordercreated' );
}
add_action('woocommerce_checkout_create_order', 'add_custom_field_to_order');

// Create an admin menu item
function wi_report_menu() {
    add_menu_page(
        'Woo Invoices Reports',
        'Woo Invoices Reports',
        'manage_options',
        'woo-invoices-reports',
        'woo_invoices_reports_page'
    );
}
add_action('admin_menu', 'wi_report_menu');

// Create the admin page content
function woo_invoices_reports_page() {
    ?>
<div class="wrap">
    <h2>Woo Invoices Reports</h2>
    <form method="post">
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" required>
        <br><br>
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" required>
        <br><br>
        <input type="submit" name="generate_report" value="Generate Report">
    </form>
    <?php
        if (isset($_POST['generate_report'])) {
            // Process the selected period and display order IDs
            $start_date = sanitize_text_field($_POST['start_date']);
            $end_date = sanitize_text_field($_POST['end_date']);
            
            // Retrieve and display order IDs for the selected period
            display_orders_for_period($start_date, $end_date);
        }
        ?>
</div>
<?php
}

// Function to retrieve and display order IDs for the selected period
function display_orders_for_period($start_date, $end_date) {
    // Query orders for the selected period
    $args = array(
        'post_type' => 'shop_order',
        'posts_per_page' => -1,
        'post_status' => 'wc-completed',
        'date_query' => array(
            'after' => $start_date,
            'before' => $end_date,
            'inclusive' => true, // Include the start and end dates

        ),
    );

    $orders = new WP_Query($args);

    if ($orders->have_posts()) {
        $grossValueSum = 0;
        $netValueSum = 0;
        $vatValueSum = 0;
        $tax0PercentValueSum = 0;
        $taxValueSum = 0;
        $discountSum = 0;
        $totalSum = 0;

        // Initialize arrays to keep track of different products and their quantities
        $differentProducts = array();
        $totalItems = array();

        $html_content = '<style>
            .woo-invoice-reports-header{
                display: flex;
                justify-content: space-between;
            } 
            table {
                border-collapse: collapse;
                width: 100%;
            }

            th, td {
                border: 1px solid #dddddd;
                text-align: left;
                padding: 8px;
            }

            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
        </style>
        ';

        $html_content .=  '
        <header class="woo-invoice-reports-header">
                <img src="https://next-rehabandperformance.be/wp-content/themes/next-academy/images/NextAcademy_Logo_2020_RGB2.svg?v=1695642847488" width="231" alt="" class="width-100">
                <div id="company">
                    <p class="name">Next Rehab and Performance</p>
                    <p class="details">Fraikinstraat 36, bus 102<br>
                    2200 Herentals</p>
                </div>
            </header>
        ';
        
        $html_content .= '<h3>Invoices for the selected period: ' . $start_date . ' - ' . $end_date . '</h3>';

        // Start the table
        $html_content .= '<table style="font-size: 12px">';
        $html_content .= '<thead style="background: grey">';
        $html_content .= '<tr>';
        $html_content .= '<th>Invoice Number</th>';
        $html_content .= '<th>Issue Date</th>';
        $html_content .= '<th>Sale Date</th>';
        $html_content .= '<th>Due Date</th>';
        $html_content .= '<th>Customer Name</th>';
        $html_content .= '<th>Different courses</th>';
        $html_content .= '<th>Total items</th>';
        $html_content .= '<th>Gross Value</th>';
        $html_content .= '<th>Net Value</th>';
        $html_content .= '<th>Tax BTW 21 (VAT 21)% Value</th>';
        $html_content .= '<th>Tax 0% Value</th>';
        $html_content .= '<th>Tax Value</th>';
        $html_content .= '</tr>';
        $html_content .= '</thead>';
        $html_content .= '<tbody>';
        
        while ($orders->have_posts()) {
            $orders->the_post();
            
            $order = wc_get_order(get_the_ID());

            $currentYear = date('Y');

            $invoice_number = $currentYear .'/'. $order->get_data()['id'];
            $order_date = date("Y-m-d", strtotime(get_post_meta($order->get_id(), '_paid_date', true)));
            $name = $order->get_data()['billing']['first_name'] . ' ' . $order->get_data()['billing']['last_name'];
            $email = $order->get_data()['billing']['email'];
            $discount = $order->get_data()['discount_total']; 
            $order_total = get_post_meta($order->get_id(), '_order_total', true);
            
            $net_value = number_format(($order_total - ($order_total * 21/100)), 2); 
            $vat_value = number_format(($order_total * 21/100), 2);

            $tax_percent = '';
            $tax_amount =  number_format($vat_value, 2);

            // Calculate and add values to sums
            $grossValueSum += number_format($order_total, 2);
            $netValueSum += number_format($net_value, 2);
            $vatValueSum += number_format($vat_value, 2);
            $tax_percent_sum = '';
            $tax_amount_sum += number_format($tax_amount, 2);

            // Initialize arrays for products and quantities in this order
            $orderProducts = array();
            $orderQuantities = array();

            // Get line items (products) in the order
            $line_items = $order->get_items();

            foreach ($line_items as $item_id => $item_data) {
                $product_id = $item_data->get_product_id();
                $product_name = $item_data->get_name();
                $quantity = $item_data->get_quantity();

                // Track different products in this order
                $orderProducts[$product_id] = $product_name;

                // Track quantities of each product in this order
                if (isset($orderQuantities[$product_id])) {
                    $orderQuantities[$product_id] += $quantity;
                } else {
                    $orderQuantities[$product_id] = $quantity;
                }
            }

            // Count different products and total items in this order
            $differentProductsCount = count($orderProducts);
            $totalItemsCount = array_sum($orderQuantities);

            // Update the arrays for different products and total items
            $differentProducts[] = $differentProductsCount;
            $totalItems[] = $totalItemsCount;
            
            // Output table rows for each order
            $html_content .= '<tr>';
            $html_content .= '<td>' . $invoice_number . '</td>';
            $html_content .= '<td>' . $order_date . '</td>';
            $html_content .= '<td>' . $order_date . '</td>';
            $html_content .= '<td>' . $order_date . '</td>';
            $html_content .= '<td>' . $name . '</td>';
            $html_content .= '<td>' . $differentProductsCount . '</td>'; // Different Products column
            $html_content .= '<td>' . $totalItemsCount . '</td>'; // Total Items column
            $html_content .= '<td>' . $order_total . '</td>';
            $html_content .= '<td>' . $net_value . '</td>';
            $html_content .= '<td>' . $vat_value . '</td>';
            $html_content .= '<td>' . $tax_percent . '</td>';
            $html_content .= '<td>' . $tax_amount . '</td>';
            $html_content .= '</tr>';
        }

        // Add rows for displaying the sums at the end of the table
        $html_content .= '<tr>';
        $html_content .= '<td colspan="7"><strong>Total:</strong></td>';
        $html_content .= '<td><strong>' . number_format($grossValueSum, 2) . '</strong></td>';
        $html_content .= '<td><strong>' . number_format($netValueSum, 2) . '</strong></td>';
        $html_content .= '<td><strong>' . number_format($vatValueSum, 2) . '</strong></td>';
        $html_content .= '<td><strong>' . $tax_percent_sum . '</strong></td>';
        $html_content .= '<td><strong>' . number_format($tax_amount_sum, 2) . '</strong></td>';
        $html_content .= '</tr>';

            $netValueSum += number_format($net_value, 2);
            $vatValueSum += number_format($vat_value, 2);
            $tax_percent_sum = '';
            $tax_amount_sum += number_format($tax_amount, 2);
        // Close the table
        $html_content .= '</tbody>';
        $html_content .= '</table>';
        $html_content .= '<br/>';
        $html_content .= '<br/>';
        $html_content .= '<br/>';

        
        // Save the HTML content to a file
        $filename = 'order_report-' . date("Y-m-d H-i-s") . '.html';
        $file_path = WP_PLUGIN_DIR . '/woo-invoices/reports/' . $filename; 

        if (file_put_contents($file_path, $html_content) !== false) {
            // Provide a link to the saved HTML file with target="_blank" to open in a new window
           echo '<p>Report saved. <a href="' . plugins_url('/woo-invoices/reports/' . $filename) . '" target="_blank">Open Report</a></p>';
        } else {
            echo '<p>Error saving the report.</p>';
        }
        wp_reset_postdata();
    } else {
        echo '<p>No orders found for the selected period.</p>';
    }
}