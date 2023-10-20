<?php
/*
Plugin Name: Woo Invoices
Description: Custom Woocommerce invoices.
Version: 1.0
*/
//Make sure you link the file correctly using your own localhost url
require_once(WP_PLUGIN_DIR . '/woo-invoices/vendor/autoload.php'); 

add_action('woocommerce_thankyou', 'wi_custom_new_order_email');

function wi_custom_new_order_email( $order_id ) {

        $order = new WC_Order( $order_id );

        $kmo_prod_info = false;

        $date = date('M d, Y');

        if (!empty($order->get_data()['billing']['company'])) {
            $name = $order->get_data()['billing']['company']; 
        } else {
            $name = $order->get_data()['billing']['first_name'] . ' ' . $order->get_data()['billing']['last_name'];
        }
        $email = $order->get_data()['billing']['email'];
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
            $data .=  "<h2>You bought a KMO product so 
            the sum except Vat must be paid by the goverment.
            </h2>";
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
        <table cellspacing='0' cellpadding='0' style='width:100%;vertical-align:top' border='0'>
        <tbody>
        <tr>
        <td valign='top' width='50%' style='padding:12px'>
        </td>
        <td valign='top' width='50%' style='padding:12px'>
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
    $invoice_html = generate_invoice($order, $order_id);

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

     // Generate the PDF invoice
    $pdf_filename = generate_pdf_invoice($order_id, $invoice_html);

    // Get the email headers
    $mailer = WC()->mailer();

    // Attach the temporary invoice file to the email
    $attachments = array($pdf_filename);

    $subject = 'New Customer Order';
    
    // Send the email with the invoice attached
    $mailer->send( $email, $subject, $mailer->wrap_message( $subject, $data ), '', $attachments);     
    
    // Delete the temporary invoice file
    unlink($temp_invoice_file);
}

// Function to generate the PDF invoice using DOMPDF
function generate_pdf_invoice($order_id, $html_content) {
    // Create a DOMPDF instance
    $pdf = new Dompdf\Dompdf();

    // Set PDF document information
    $pdf->set_option('isHtml5ParserEnabled', true);
    $pdf->setPaper('A4', 'portrait');
    $pdf->set_option('isPhpEnabled', true);
    $pdf->set_option('isRemoteEnabled', TRUE);

    // Load the HTML content into DOMPDF
    $pdf->loadHtml($html_content);

    // Render the PDF (choose to save it as a file or output to the browser)
    $pdf->render();

    // Define the directory where you want to temporarily store the PDF
    $output_dir = WP_PLUGIN_DIR . '/woo-invoices/invoices/';

    // Create the directory if it doesn't exist
    if (!file_exists($output_dir)) {
        mkdir($output_dir, 0755, true);
    }

    // Generate a unique filename for the PDF invoice
    $pdf_filename = 'invoice_' . $order_id . '.pdf';

    // Define the full path to the PDF invoice file
    $pdf_file_path = $output_dir . $pdf_filename;

    // Output the PDF to the file
    file_put_contents($pdf_file_path, $pdf->output());

    // Return the file path if needed
    return $pdf_file_path;
}
    
// Function to generate the invoice
function generate_invoice($order, $order_id) {
    $order_date = date("Y-m-d", strtotime(get_post_meta($order->get_id(), '_paid_date', true)));

    $kmo_prod_info = false;

    $html_content = '<style>
    .woo-invoice-reports-header > *,
    .woo-invoice-reports-header-bottom > * {
        display: inline-block;
        vertical-align: top;
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
    <div>
        <img src="https://next-rehabandperformance.be/wp-content/themes/next-academy/images/NextAcademy_Logo_2020_RGB2.svg?v=1695642847488" width="231" alt="" class="width-100">
        <div>
            <p>Verkoopdatum: ' . $order_date . '</p>
            <p>Uitgiftedatum: ' . $order_date . '</p>
            <p>Vervaldatum: ' . $order_date . '</p>
            <p>Betaalmethode:</p>
        </div>
    </div>
    <div class="woo-invoice-reports-header-bottom">
        <div id="company">
            <h3>Seller:</h3>
            <p class="name">Next Rehab and Performance</p>
            <p class="details">Fraikinstraat 36, bus 102<br>
            2200 Herentals</p>
            <p>BTW-nummer: BE 0688 927 157</p>
            <p>Bank: Belfius</p>
            <p>Account number: BE11 0689 0896 0048</p>
        </div>
        <div id="buyer">
            <h3>Buyer:</h3>
            <p>'. $order->get_data()['billing']['company'] .'</p>
            <p>'. $order->get_data()['billing']['first_name'] . ' ' . $order->get_data()['billing']['last_name'] .'</p>
            <p>'. $order->get_data()['billing']['address_1'] . ' ' . $order->get_data()['billing']['address_1'] . ' ' . $order->get_data()['billing']['city'] . '</p>
        </div>
    </div>
    <div>
        <h1 style="text-align: center">' .
            date('Y') . '/' . $order->get_data()['id']
        . '</h1>
    </div>
    ';

    // Start the table
    $html_content .= '<table style="font-size: 12px">';
    $html_content .= '<thead style="background: grey">';
    $html_content .= '<tr>';
    $html_content .= '<th>Naam</th>';
    $html_content .= '<th>Hoeveelheid</th>';
    $html_content .= '<th>Eenheid</th>';
    $html_content .= '<th>Nettoprijs</th>';
    $html_content .= '<th>Nettobedrag</th>';
    $html_content .= '<th>Belastingtarief</th>';
    $html_content .= '<th>Belastingbedrag</th>';
    $html_content .= '<th>Brutobedrag</th>';
    $html_content .= '</tr>';
    $html_content .= '</thead>';
    $html_content .= '<tbody>';


    $currentYear = date('Y');

    $invoice_number = $currentYear .'/'. $order->get_data()['id'];

    if (!empty($order->get_data()['billing']['company'])) {
        $name = $order->get_data()['billing']['company']; 
    } else {
        $name = $order->get_data()['billing']['first_name'] . ' ' . $order->get_data()['billing']['last_name'];
    }

    $email = $order->get_data()['billing']['email'];
    $discount = number_format(($order->get_data()['discount_total']), 2); 
    $order_total = number_format((get_post_meta($order->get_id(), '_order_total', true)), 2);

    $net_value = number_format(($order_total - ($order_total * 21/100)), 2); 
    $vat_value = number_format(($order_total * 21/100), 2);

    $tax_percent = '';
    $tax_amount =  number_format($vat_value, 2);

    $order_items = $order->get_items();

    foreach ( $order_items as $item_id => $item ) {
        $item_name = $item->get_data()['name'];
        $item_quantity = $item->get_data()['quantity'];
        $item_total = number_format(($item->get_data()['total']), 2);
        $item_net = number_format(($item_total - $item_total * 21 / 100), 2);
        $item_net_price = number_format(($item_net / $item_quantity), 2);
        $item_taxes = number_format(($item->get_data()['total_tax']), 2);
        $vat_texting = 'BTW 21% (VAT21%)';

        $html_content .= '<tr>';
        $html_content .= '<td>' . $item_name . '</td>';
        $html_content .= '<td>' . $item_quantity . '</td>';
        $html_content .= '<td>Item</td>';
        $html_content .= '<td>' . $item_net_price . '</td>';
        $html_content .= '<td>' . $item_net . '</td>';
        $html_content .= '<td>' . $vat_texting . '</td>';
        $html_content .= '<td>' . $item_taxes . '</td>';
        $html_content .= '<td>' . $item_total . '</td>';
        $html_content .= '</tr>';
    }

    foreach( $order->get_items('fee') as $item_id => $item_fee ){
        // The fee name
        $fee_name = $item_fee->get_name();

        // The fee total amount
        $fee_total = number_format(($item_fee->get_total()), 2);

        // The fee total tax amount
        $fee_total_tax = number_format(($item_fee->get_total_tax()), 2);

        if ($fee_name == 'Nieuw subtotaal (te gebruiken in KMO-dossier)') {
            $vat_texting = '0.00%';
        } else {
            $vat_texting = 'BTW 21% (VAT21%)';
        }

        $html_content .= '<tr>';
        $html_content .= '<td>' . $fee_name . '</td>';
        $html_content .= '<td></td>';
        $html_content .= '<td></td>';
        $html_content .= '<td></td>';
        $html_content .= '<td></td>';
        $html_content .= '<td>' . $vat_texting . '</td>';
        $html_content .= '<td>' . $fee_total_tax . '</td>';
        $html_content .= '<td>' . $fee_total . '</td>';
        $html_content .= '</tr>';
    }

    // Output table rows for each order
    $html_content .= '<tr>';
    $html_content .= '<td colspan="4">Total</td>';
    $html_content .= '<td>' . $net_value . '</td>';
    $html_content .= '<td>' . $tax_percent . '</td>';
    $html_content .= '<td>' . $tax_amount . '</td>';
    $html_content .= '<td>' . $order_total . '</td>';
    $html_content .= '</tr>';


    // Close the table
    $html_content .= '</tbody>';
    $html_content .= '</table>';
    $html_content .= '<br/>';
    $html_content .= '<br/>';
    $html_content .= '<br/>';
    
    if ($kmo_prod_info) {
        $html_content .= '<h2>You bought a KMO product so 
        the sum except Vat must be paid by the goverment.
        </h2>';
    }

    $html_content .= '<p>Order number: ' . $order->get_data()['id'] . '</p>';
        
    // Create a new invoice post
    $invoice_post = array(
        'post_title' => 'Invoice for Order #' . $order_id,
        'post_type' => 'woo_invoices', // Custom post type
        'post_status' => 'publish', // You can change this as needed
    );

    $invoice_id = wp_insert_post($invoice_post);

    // Save specific order data as custom fields
    update_post_meta($invoice_id, '_order_id', $order_id);
    update_post_meta($invoice_id, '_order_date', $order_date);

    return $html_content;
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
    $company = $order->get_data()['billing']['company'];
    $email = $order->get_data()['billing']['email'];
    $billing_phone = $order->get_data()['billing']['phone'];
    $riziv_number = get_post_meta($order->get_id(), '_billing_riziv_nummer', true);
    $billing_ondernemingsnummer = get_post_meta($order->get_id(), '_billing_ondernemingsnummer', true);
    $order_items = get_post_meta($post->ID, '_order_items', true);
    $discount = $order->get_data()['discount_total']; 
    $order_total = get_post_meta($order->get_id(), '_order_total', true);
    $order_status = $order->get_data()['status'];

    // Output HTML for custom fields
    echo '<p><strong>Order Date:</strong> ' . esc_html($order_date) . '</p>';
    echo '<p><strong>Customer:</strong> ' . esc_html($name) . '</p>';
    echo '<p><strong>Company:</strong> ' . esc_html($company) . '</p>';
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
            echo '<hr/>';

        }
        echo '</ul>';
    }

    echo '<p><strong>Discount Total:</strong> ' . esc_html($discount) . '</p>';
    echo '<p><strong>Total:</strong> ' . esc_html($order_total) . '</p>';
    echo '<p><strong>Order Status:</strong> ' . esc_html($order_status) . '</p>';
}

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
            
            if (!empty($order->get_data()['billing']['company'])) {
                $name = $order->get_data()['billing']['company']; 
            } else {
                $name = $order->get_data()['billing']['first_name'] . ' ' . $order->get_data()['billing']['last_name'];
            }
            
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