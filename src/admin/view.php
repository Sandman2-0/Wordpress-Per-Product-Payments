<?php
if (!current_user_can('manage_options')) { //Check if the user is not admin
    return; //If satisfied, stop any further execution
}

//Variables for declaring the path & loading the saved configuration
$config_file = plugin_dir_path(__FILE__) . 'payment_gateway_blocker_config.json';
$config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : []; //Initially loading the config file

//Variable for getting the IDs from the config
$products = wc_get_products(array('limit' => -1));

//Handling form submission (Must be loaded before the HTML)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {                                 //Checks if the form has been submitted
    $config = [];                                                           //Initializing an array for the config data (this overrides the config file)
    foreach ($_POST['product_ids'] as $index => $product_id) {              //Iterates through the submitted product IDs
        $gateway_id = sanitize_text_field($_POST['gateway_ids'][$index]);   //For each product, the blocked gateway is retrieved
        if ($product_id && $gateway_id) {                                   //Check that both product & gateway IDs were successfully retrieved
            $config[] = array(
                'product_id' => intval($product_id),                        //Product ID is converted into an integer if needed (as a failsafe) & stored
                'gateway_id' => $gateway_id                                 //Storing the blocked gateway with the product
            );
        }
    }
    file_put_contents($config_file, json_encode($config)); //Save the IDs to the config file
    echo '<div class="updated"><p>Configuration saved.</p></div>'; //Display a confirmation message within the user interface
}

//User interface
$payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
?>
<div class="wrap">
    <h1>Payment Gateway Blocker</h1>               <!-- Header for the settings page -->
    <form method="post" action="">                 <!-- Begin form for handling configurations -->
        <table id="form-table" class="form-table"> <!-- Begin table for form fields -->
            <tbody>
                <?php if (!empty($config)): ?>            <!-- Check if there is an existing configuration to proceed -->
                    <?php foreach ($config as $entry): ?> <!-- Loop through each configuration entry -->
                        <tr>                              <!-- Begin table row for each option -->
                            <td style="padding-right: 10px;">
                                <button type="button" class="remove-row-button">&times;</button> <!-- Button to remove the row -->
                            </td>
                            <th scope="row">Product</th>                <!-- Name of the first column -->
                            <td>
                                <select name="product_ids[]" style="width: 90%;"> <!-- Creating the product dropdown menu -->
                                    <option value=""></option>                    <!-- Adding an empty default option -->
                                    <?php foreach ($products as $product): ?>     <!-- Loop through products & add them to the menu -->
                                        <option value="<?php echo $product->get_id(); ?>" <?php selected($entry['product_id'], $product->get_id()); ?>><?php echo $product->get_name(); ?></option> <!-- Displaying the available product options & any existing product configuration rows -->
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <th scope="row">Blocked Payment Method</th> <!-- Name of the second column -->
                            <td>
                                <select name="gateway_ids[]" style="width: 100%;">    <!-- Creating the blocked gateway dropdown menu -->
                                    <option value=""></option>                        <!-- Adding an empty default option -->
                                    <?php foreach ($payment_gateways as $gateway): ?> <!-- Loop through available gateways & add them to the menu -->
                                        <option value="<?php echo $gateway->id; ?>" <?php selected($entry['gateway_id'], $gateway->id); ?>><?php echo $gateway->get_title(); ?></option> <!-- Displaying the available gateway blocking options & any existing gateway configuration rows -->
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?> <!-- If there is no existing configuration, proceed here. Note: all comments for the above section also apply here -->
                    <tr>
                        <td style="padding-right: 10px;">
                            <button type="button" class="remove-row-button">&times;</button>
                        </td>
                        <th scope="row">Product</th> 
                        <td>
                            <select name="product_ids[]" style="width: 90%;">
                                <option value=""></option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product->get_id(); ?>"><?php echo $product->get_name(); ?></option> <!-- Only displays the available product options -->
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <th scope="row">Blocked Payment Method</th>
                        <td>
                            <select name="gateway_ids[]" style="width: 100%;"> 
                                <option value=""></option> 
                                <?php foreach ($payment_gateways as $gateway): ?>
                                    <option value="<?php echo $gateway->id; ?>"><?php echo $gateway->get_title(); ?></option> <!-- Only displays the available gateway blocking options -->
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <button type="button" id="add-row-button" class="rowbutton">Add Another Payment Option</button> <!-- Button to add another row -->
        <?php submit_button(); ?> <!-- Adding a submit button -->
    </form> <!-- Closing the form -->
</div>

<script>
    jQuery(document).ready(function ($) {
        $('#add-row-button').click(function () { //If the add row button is clicked, clone and append the last row
            var newRow = $('#form-table tbody tr:last').clone();
            newRow.find('select').each(function() {
                $(this).val(''); //Clear the values in the row.
            });
            $('#form-table tbody').append(newRow);
        });

        $(document).on('click', '.remove-row-button', function () { //If a remove row button is clicked, remove the closest row
            $(this).closest('tr').remove();
        });
    });
</script>

<style>
    .my-plugin-options {
        border: 1px solid #ddd;
        background-color: #f9f9f9;
        padding: 20px;
        margin: 30px;
    }
    
    .rowbutton {
        width: 100%; 
        height: 25px;
        margin-top: 25px; 
        margin-bottom: 15px;
        border-radius: 10px;
        color: #fff; 
        background-color: #6c757d;
        border: none; 
        cursor: pointer;
    }
    
    .remove-row-button{
        color: red; 
        background: none; 
        border: none; 
        font-size: 16px; 
        cursor: pointer
    }
</style>
