<?php

/**
 * Plugin Name: Courier Quote Plugin Update
 * Description: A plugin for calculating courier quotes using Google Maps API.
 * Version: 4.0
 * Author: Sandun Wijerathne
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue scripts and styles.
 */
function cq_enqueue_scripts()
{
    $api_key = get_option('cq_google_maps_api_key');
    wp_enqueue_script('google-maps-api', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places', array(), null, true);
    wp_enqueue_script('courier-quote-script', plugin_dir_url(__FILE__) . 'courier-quote-plugin-update.js', array('jquery', 'google-maps-api'), '3.0', true);
    wp_enqueue_style('courier-quote-style', plugin_dir_url(__FILE__) . 'courier-quote-plugin-update.css', '2.9', true);

    // Pass the admin settings to JS - FIXED LOCALIZATION
    wp_localize_script('courier-quote-script', 'cq_settings', array(
        'costPerMile'         => get_option('cq_cost_per_mile', '1.3'),
        'additionalCost'      => get_option('cq_additional_cost', '50'),
        'mediumVanMultiplier' => get_option('cq_medium_van_multiplier', '1.2'),
        'largeVanMultiplier'  => get_option('cq_large_van_multiplier', '1.3'),
        'xLargeVanMultiplier' => get_option('cq_xlarge_van_multiplier', '1.3'),
        'lutonVanMultiplier'  => get_option('cq_luton_van_multiplier', '1.6'),
        'showQuoteResult'     => get_option('cq_show_quote_result', 'yes')
    ));
}

add_action('wp_enqueue_scripts', 'cq_enqueue_scripts');


/**
 * Create shortcode for displaying the quote form.
 */
function cq_display_quote_form()
{
    ob_start();

    // Get today's date
    $today = date('Y-m-d');
    // Get tomorrow's date
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    // Get next week's date
    $nextWeek = date('Y-m-d', strtotime('+7 days'));

?>
    <div class="courier-quote-container">
        <h2>Get a Courier Service Quote</h2>
        <form id="getQuoteForm">
            <!-- Step 1 -->
            <div class="step active" id="step1" data-step="1">


                <div class="when">
                    <label>When:</label>
                    <div class="time-options">
                        <label><input type="radio" name="when" value="<?= $today; ?>" required> Now</label>
                        <label><input id="to" type="radio" name="when" value="<?= $tomorrow; ?>"> Tomorrow</label>
                        <label><input id="nw" type="radio" name="when" value="<?= $nextWeek; ?>"> Next Week</label>
                        <label><input type="radio" name="when" value="custom"> Choose Date:</label>
                        <input type="date" id="customDate" name="customDate" disabled>
                        <select id="timeSelection" name="time" disabled>
                            <?php
                            for ($hour = 0; $hour < 24; $hour++) {
                                $startTime = sprintf("%02d:00", $hour);
                                $endTime = sprintf("%02d:00", ($hour + 1) % 24);
                                echo "<option value='$startTime - $endTime'>$startTime - $endTime</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="fieldset">
                    <div class="fieldset-wrap">
                        <div>
                            <!-- 					<label for="collectFrom">Collect From:</label> -->
                            <img src="<?php echo plugin_dir_url(__FILE__) ?>img/home-icon-1.svg" class="cmbwL">
                            <input type="text" id="collectFrom" name="collectFrom" placeholder="Collect Location" required>
                        </div>
                        <div>
                            <!-- 					<label for="deliverTo">Deliver To:</label> -->
                            <img src="<?php echo plugin_dir_url(__FILE__) ?>img/home-icon-3.svg" class="cmbwL">
                            <input type="text" id="deliverTo" name="deliverTo" placeholder="Deliver Location" required>
                        </div>
                        <div>
                            <!-- 					<label for="email">Email:</label> -->
                            <img src="<?php echo plugin_dir_url(__FILE__) ?>img/home-icon-4.svg" class="cmbwL">
                            <input type="email" id="email" name="email" placeholder="Your Email Address" required>
                        </div>
                    </div>
                    <button type="button" class="next-btn">Next →</button>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="step" id="step2" data-step="2">
                <section class="custom-progress-container mx-auto mt-4 py-4 rounded-lg hidden md:flex items-center justify-between custom-progress-details mx-6">
                    <div class="lg:flex w-4/5 justify-around">
                        <div class="flex items-center md:px-4 py-1 lg:py-2 lg:py-0"><img src="<?php echo plugin_dir_url(__FILE__) ?>img/2.svg" class="mr-3">
                            <h6 class="text-white text-md font-light">Pickup from: <br><span id="pickuploc" class="font-bold ">KT12 4SG</span></h6>
                        </div>
                        <div class="flex items-center md:px-4 py-1 lg:py-2 lg:py-0"><img src="<?php echo plugin_dir_url(__FILE__) ?>img/2.svg" class="mr-3">
                            <h6 class="text-white text-md font-light">Destination: <br><span id="deliverloc" class="font-bold ">L8 1YR</span></h6>
                        </div>
                        <div class="flex items-center md:px-4 py-1 lg:py-2 lg:py-0"><img src="<?php echo plugin_dir_url(__FILE__) ?>img/van.svg" class="mr-3">
                            <h6 class="text-white text-md font-light">Earliest Delivery: <br><span id="dleviverdtae" class="font-bold ">09:42 if Booked Now</span></h6>
                        </div>
                        <div class="flex items-center md:px-4 py-1 lg:py-2 lg:py-0"><img src="<?php echo plugin_dir_url(__FILE__) ?>img/1.svg" class="mr-3">
                            <h6 class="text-white text-md font-light">Est Transit Time: <br><span id="esttime" class="font-bold ">03:55:26</span></h6>
                        </div>
                    </div><a href="#" class="text-white text-lg font-bold mr-3 link md:px-4 prev-btnn">« Back</a>
                </section>
                <h3>Choose Vehicle</h3>
                <div class="vehicle-options">
                    <!-- Small Van -->
                    <div class="vehicle-option">
                        <label>
                            <input type="radio" name="vehicle" id="smallVan" value="small" data-price="50" required>
                            <img src="<?php echo plugin_dir_url(__FILE__) ?>img/svan.jpg" alt="Small Van">
                            <h4>SMALL VAN</h4>
                            <div>
                                <p><span>Length</span> <span>1.5m</span></p>
                                <p><span>Width</span> <span>1.1m</span></p>
                                <p><span>Height</span> <span>1.2m</span></p>
                                <p><span>Payload</span> <span>400kg</span></p>
                               
								<p><span>Vat</span> <span>+20%</span></p>
								<p class="totaltax"><span>TOTAL WITH VAT</span> <span class="pricewithtax"></span></p>
								<p class="total"><span>TOTAL</span> <span class="price"></span></p>
                            </div>
                        </label>
                    </div>

                    <!-- Medium Van -->
                    <div class="vehicle-option">
                        <label>
                            <input type="radio" name="vehicle" id="mediumVan" value="medium" data-price="50">
                            <img src="<?php echo plugin_dir_url(__FILE__) ?>img/mvan.jpg" alt="Medium Van">
                            <h4>MEDIUM VAN</h4>
                            <div>
                                <p><span>Length</span> <span>2.2m</span></p>
                                <p><span>Width</span> <span>1.3m</span></p>
                                <p><span>Height</span> <span>1.3m</span></p>
                                <p><span>Payload</span> <span>800kg</span></p>
                                <p><span>Vat</span> <span>+20%</span></p>
								<p class="totaltax"><span>TOTAL WITH VAT</span> <span class="pricewithtax"></span></p>
								<p class="total"><span>TOTAL</span> <span class="price"></span></p>
                            </div>

                        </label>
                    </div>

                    <!-- Large Van -->
                    <div class="vehicle-option">
                        <label>
                            <input type="radio" name="vehicle" id="largeVan" value="large" data-price="75">
                            <img src="<?php echo plugin_dir_url(__FILE__) ?>img/lvan.jpg" alt="Large Van">
                            <h4>LARGE VAN</h4>
                            <div>
                                <p><span>Length</span> <span>3.8m</span></p>
                                <p><span>Width</span> <span>1.3m</span></p>
                                <p><span>Height</span> <span>1.7m</span></p>
                                <p><span>Payload</span> <span>1250kg</span></p>
                                <p><span>Vat</span> <span>+20%</span></p>
								<p class="totaltax"><span>TOTAL WITH VAT</span> <span class="pricewithtax"></span></p>
								<p class="total"><span>TOTAL</span> <span class="price"></span></p>
                            </div>
                        </label>
                    </div>

                    <!-- Extra Large Van -->
                    <div class="vehicle-option">
                        <label>
                            <input type="radio" name="vehicle" id="xlargeVan" value="xlarge" data-price="90">
                            <img src="<?php echo plugin_dir_url(__FILE__) ?>img/xlvan.jpg" alt="Extra Large Van">
                            <h4>EXTRA LARGE VAN</h4>
                            <div>
                                <p><span>Length</span> <span>4.2m</span></p>
                                <p><span>Width</span> <span>1.3m</span></p>
                                <p><span>Height</span> <span>1.7m</span></p>
                                <p><span>Payload</span> <span>1100kg</span></p>
                                <p><span>Vat</span> <span>+20%</span></p>
								<p class="totaltax"><span>TOTAL WITH VAT</span> <span class="pricewithtax"></span></p>
								<p class="total"><span>TOTAL</span> <span class="price"></span></p>
                            </div>
                        </label>
                    </div>

                    <!-- Luton Van -->
                    <div class="vehicle-option">
                        <label>
                            <input type="radio" name="vehicle" id="lutonVan" value="luton" data-price="110">
                            <img src="<?php echo plugin_dir_url(__FILE__) ?>img/luvan.jpg" alt="Luton Van">
                            <h4>LUTON VAN BOX/CURTAIN</h4>
                            <div>
                                <p><span>Length</span> <span>4m</span></p>
                                <p><span>Width</span> <span>2m</span></p>
                                <p><span>Height</span> <span>2m</span></p>
                                <p><span>Payload</span> <span>1000kg</span></p>
								<p><span>Vat</span> <span>+20%</span></p>
								<p class="totaltax"><span>TOTAL WITH VAT</span> <span class="pricewithtax"></span></p>
								<p class="total"><span>TOTAL</span> <span class="price"></span></p>
                                <p class="note">(With tail lift)</p>
                            </div>
                        </label>
                    </div>
                </div>
                <button type="button" class="prev-btn">← Previous</button>
                <button type="button" id="getQuote">Get Quote</button>
            </div>
        </form>

        <p id="quoteResult"></p>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('courier_quote_form', 'cq_display_quote_form');

/**
 * Create plugin settings page.
 */
function cq_plugin_settings_page()
{
    add_menu_page(
        'Courier Quote Settings',
        'Courier Quote',
        'manage_options',
        'courier-quote-settings',
        'cq_settings_page_html',
        'dashicons-admin-generic',
        90
    );
}
add_action('admin_menu', 'cq_plugin_settings_page');

/**
 * Display settings page HTML.
 */
function cq_settings_page_html()
{
    if (! current_user_can('manage_options')) {
        return;
    }
?>
    <div class="wrap">
        <h1>Courier Quote Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cq_google_maps_settings');
            do_settings_sections('courier-quote-settings');
            ?>
            <table class="form-table">
                <!-- Google Maps API Key -->
                <tr>
                    <th scope="row"><label for="cq_google_maps_api_key">Google Maps API Key</label></th>
                    <td>
                        <input type="text" id="cq_google_maps_api_key" name="cq_google_maps_api_key" value="<?php echo esc_attr(get_option('cq_google_maps_api_key')); ?>" class="regular-text">
                        <p class="description">Enter your Google Maps API key here. You can get your API key from the <a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud Console</a>. Make sure you enable the "JavaScript API" and "Distance Matrix API".</p>
                    </td>
                </tr>
                <!-- Cost Per Mile -->
                <tr>
                    <th scope="row"><label for="cq_cost_per_mile">Cost Per Mile</label></th>
                    <td>
                        <input type="number" step="0.01" id="cq_cost_per_mile" name="cq_cost_per_mile" value="<?php echo esc_attr(get_option('cq_cost_per_mile', '1.3')); ?>" class="regular-text">
                        <p class="description">Enter the cost per mile (default is 1.3).</p>
                    </td>
                </tr>
                <!-- Additional Cost Per Hour -->
                <tr>
                    <th scope="row"><label for="cq_additional_cost">Additional Cost Per Hour</label></th>
                    <td>
                        <input type="number" step="0.01" id="cq_additional_cost" name="cq_additional_cost" value="<?php echo esc_attr(get_option('cq_additional_cost', '50')); ?>" class="regular-text">
                        <p class="description">Enter the additional cost per hour (default is 50).</p>
                    </td>
                </tr>
                <!-- Medium Van Multiplier -->
                <tr>
                    <th scope="row"><label for="cq_medium_van_multiplier">Medium Van Multiplier</label></th>
                    <td>
                        <input type="number" step="0.01" id="cq_medium_van_multiplier" name="cq_medium_van_multiplier" value="<?php echo esc_attr(get_option('cq_medium_van_multiplier', '1.2')); ?>" class="regular-text">
                        <p class="description">Multiplier for medium van price (default is 1.2).</p>
                    </td>
                </tr>
                <!-- Large Van Multiplier -->
                <tr>
                    <th scope="row"><label for="cq_large_van_multiplier">Large Van Multiplier</label></th>
                    <td>
                        <input type="number" step="0.01" id="cq_large_van_multiplier" name="cq_large_van_multiplier" value="<?php echo esc_attr(get_option('cq_large_van_multiplier', '1.3')); ?>" class="regular-text">
                        <p class="description">Multiplier for large van price (default is 1.3).</p>
                    </td>
                </tr>
                <!-- Extra Large Van Multiplier -->
                <tr>
                    <th scope="row"><label for="cq_xlarge_van_multiplier">Extra Large Van Multiplier</label></th>
                    <td>
                        <input type="number" step="0.01" id="cq_xlarge_van_multiplier" name="cq_xlarge_van_multiplier" value="<?php echo esc_attr(get_option('cq_xlarge_van_multiplier', '1.3')); ?>" class="regular-text">
                        <p class="description">Multiplier for extra large van price (default is 1.3).</p>
                    </td>
                </tr>
                <!-- Luton Van Multiplier -->
                <tr>
                    <th scope="row"><label for="cq_luton_van_multiplier">Luton Van Multiplier</label></th>
                    <td>
                        <input type="number" step="0.01" id="cq_luton_van_multiplier" name="cq_luton_van_multiplier" value="<?php echo esc_attr(get_option('cq_luton_van_multiplier', '1.6')); ?>" class="regular-text">
                        <p class="description">Multiplier for Luton van price (default is 1.6).</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="cq_show_quote_result">Show Quote Result</label></th>
                    <td>
                        <input type="checkbox" id="cq_show_quote_result" name="cq_show_quote_result" value="yes" <?php checked(get_option('cq_show_quote_result', 'yes'), 'yes'); ?>>
                        <p class="description">Check to display the quote result to users.</p>
                    </td>
                </tr>
            </table>


            <h3>How to Use</h3>
            <p>Once the API key and other settings are entered and saved, you can use the [courier_quote_form] shortcode to display the courier quote form anywhere on your website (e.g., posts, pages, or widgets).</p>
            <p>Example Usage:</p>
            <pre><strong>[courier_quote_form]</strong></pre>
            <p>Fill in the addresses and select your preferences to get an instant quote for your courier service.</p>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}

/**
 * Register plugin settings.
 */
function cq_register_settings()
{
    register_setting('cq_google_maps_settings', 'cq_google_maps_api_key', 'sanitize_text_field');
    register_setting('cq_google_maps_settings', 'cq_cost_per_mile', 'floatval');
    register_setting('cq_google_maps_settings', 'cq_additional_cost', 'floatval');
    register_setting('cq_google_maps_settings', 'cq_medium_van_multiplier', 'floatval');
    register_setting('cq_google_maps_settings', 'cq_large_van_multiplier', 'floatval');
    register_setting('cq_google_maps_settings', 'cq_xlarge_van_multiplier', 'floatval');
    register_setting('cq_google_maps_settings', 'cq_luton_van_multiplier', 'floatval');
    register_setting('cq_google_maps_settings', 'cq_show_quote_result', 'sanitize_text_field');
}
add_action('admin_init', 'cq_register_settings');

add_filter('woocommerce_add_to_cart_validation', 'remove_cart_item_before_add_to_cart', 20, 3);
function remove_cart_item_before_add_to_cart($passed, $product_id, $quantity)
{
    if (! WC()->cart->is_empty())
        WC()->cart->empty_cart();
    return $passed;
}
