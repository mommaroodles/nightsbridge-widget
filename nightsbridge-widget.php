<?php
/**
 * Plugin Name: Nightsbridge Widget
 * Plugin URI: https://wpdevs.co.za
 * Description: Allow visitors to check NightsBridge room availability and make bookings directly directly on your WordPress website, by querying the NightsBridge API.
 * Version: 1.0.0
 * Author: Melanie Shepherd
 * Author URI: https://wpdevs.co.za
 * Text Domain:  nightsbridge
 * License URI: https://mit-license.org/
 */

defined('ABSPATH') || exit;

if ( ! defined( 'NIGHTSBRIDGE_VERSION' ) ) {
	define( 'NIGHTSBRIDGE_VERSION', '1.0.0' );
}

if (!function_exists('plugin_dir_path')) {
    require_once(ABSPATH . 'wp-includes/plugin.php');
}

if ( ! defined( 'NIGHTSBRIDGE_PLUGIN_FILE' ) ) {
	define( 'NIGHTSBRIDGE_PLUGIN_FILE', __FILE__ );
}


/**
 * Enqueues scripts and styles for Nightsbridge widget
 * Loads on the user-selected page
 */ 
function enqueue_nightsbridge_widget_scripts() {
    $options = get_option('nb_settings');
    if (!isset($options)) {
        trigger_error('Error: Selected page not found in Nightsbridge settings', E_USER_NOTICE);
    } else {
        $options = get_option('nb_settings');
    }
    $selected_page = !empty($options['nb_page_slug']) ? sanitize_title($options['nb_page_slug']) : '';
    if (!empty($selected_page) && is_page($selected_page)) {
       
        // Enqueue CSS files
        wp_enqueue_style('nightsbridge-flatpickr-material-blue', 'https://cdn.nightsbridge.com/flatpickr/material_blue-1.0.css', array(), '1.0');
        wp_enqueue_style('nightsbridge-flatpickr', plugin_dir_url(__FILE__) . 'css/flatpickr.css', array(), NIGHTSBRIDGE_VERSION);
        wp_enqueue_style('nightsbridge-widget-style', plugin_dir_url(__FILE__) . 'css/nb_DateWidgetStyle.css', array(), NIGHTSBRIDGE_VERSION);
        wp_enqueue_style('nightsbridge-style', plugin_dir_url(__FILE__) . 'css/style.css', array(), NIGHTSBRIDGE_VERSION);
        
        // Enqueue JS files
        wp_enqueue_script('nightsbridge-flatpickr', 'https://cdn.nightsbridge.com/flatpickr/flatpickr.min-1.0.js', array(), '1.0', true);
        wp_enqueue_script('nightsbridge-widget-script', plugin_dir_url(__FILE__) . 'js/nightsbridge-widget-v2.js', array('nightsbridge-flatpickr'), NIGHTSBRIDGE_VERSION, true);
        
        // Localize script with settings
        $bbid = isset($options['nb_bbid']) ? sanitize_text_field($options['nb_bbid']) : '';
        $custom_format = isset($options['nb_custom_format']) ? sanitize_text_field($options['nb_custom_format']) : 'd-M-Y';
        $language = isset($options['nb_language']) ? sanitize_text_field($options['nb_language']) : 'en-GB';

        wp_localize_script('nightsbridge-widget-script', 'nbConfig', array(
                'bbid' => $bbid,
                'customFormat' => $custom_format,
                'language' => $language
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_nightsbridge_widget_scripts');

/**
 * Enqueues scripts and styles for the admin settings page
 */
function nb_enqueue_admin_scripts($hook_suffix) {
    if ($hook_suffix === 'settings_page_nb_settings') {
        wp_enqueue_script('nb-copy-to-clipboard', plugin_dir_url(__FILE__) . 'js/nb-copy-to-clipboard.js', array('jquery'), NIGHTSBRIDGE_VERSION, true);
        wp_enqueue_style('nb-admin-style', plugin_dir_url(__FILE__) . 'css/admin-style.css', array(), NIGHTSBRIDGE_VERSION);
    }
}
add_action('admin_enqueue_scripts', 'nb_enqueue_admin_scripts');

/**
 * Admin menu and settings initialization
 */
add_action('admin_menu', 'nb_add_admin_menu');
add_action('admin_init', 'nb_settings_init');

/**
 * Display admin notices for missing NightsBridge Widget settings.
 */
function nb_admin_notices() {
    // Retrieve the plugin settings from the options table.
    $options = get_option('nb_settings');
    
    // Get the current admin screen object.
    $screen = get_current_screen();
    
    // Check if we are on the settings page for the plugin.
    if ($screen->id === 'settings_page_nb_settings') {
        // Display a warning notice if the Booking ID (BBID) is missing.
        if (array_key_exists('nb_bbid', $options) && empty($options['nb_bbid'])) {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('NightsBridge Widget: Please enter your NightsBridge Booking ID (BBID).', 'nightsbridge') . '</p></div>';
        }
        
        // Display a warning notice if the page slug for the widget is missing.
        if (array_key_exists('nb_page_slug', $options) && empty($options['nb_page_slug'])) {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('NightsBridge Widget: Please select a page for the widget.', 'nightsbridge') . '</p></div>';
        }
        
        // Display a warning notice if the page slug for the widget is null.
        if (array_key_exists('nb_page_slug', $options) && is_null($options['nb_page_slug'])) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('NightsBridge Widget: The page slug for the widget is null.', 'nightsbridge') . '</p></div>';
        }
    }
}
// Hook the function to display admin notices.
add_action('admin_notices', 'nb_admin_notices');

/**
 * Adds an options page under the "Settings" menu for the NightsBridge Booking Widget.
 * This function checks if the current user has the "manage_options" capability
 * and, if so, adds a submenu page titled "NightsBridge Booking Widget Settings".
 * The page allows users to configure the NightsBridge plugin settings.
 *
 * @since 1.0.0
 */

function nb_add_admin_menu() {
    if (current_user_can('manage_options')) {
        add_options_page(
                __('NightsBridge Booking Widget Settings', 'nightsbridge'),
                __('NightsBridge', 'nightsbridge'),
                'manage_options',
                'nb_settings',
                'nb_options_page'
        );
    }
}

/**
 * Register and configure plugin admin settings
 */
function nb_settings_init() {
    register_setting('nb_settings_group', 'nb_settings', array(
            'sanitize_callback' => 'nb_sanitize_settings'
    ));
    
    add_settings_section(
            'nb_settings_section',
            __('NightsBridge Widget Settings', 'nightsbridge'),
            'nb_settings_section_callback',
            'nb_settings_group'
            );
    
    add_settings_field(
            'nb_bbid',
            __('NightsBridge ID (BBID)', 'nightsbridge'),
            'nb_bbid_render',
            'nb_settings_group',
            'nb_settings_section',
            array('description' => __('Connect your NightsBridge account (required)', 'nightsbridge'))
            );
    
    add_settings_field(
            'nb_custom_format',
            __('Date Format', 'nightsbridge'),
            'nb_custom_format_render',
            'nb_settings_group',
            'nb_settings_section',
            array('description' => __('Custom date format (e.g., d-M-Y)', 'nightsbridge'))
            );
    
    add_settings_field(
            'nb_language',
            __('Language', 'nightsbridge'),
            'nb_language_render',
            'nb_settings_group',
            'nb_settings_section',
            array('description' => __('Select widget language', 'nightsbridge'))
            );
    
    add_settings_field(
            'nb_page_slug',
            __('Widget Page', 'nightsbridge'),
            'nb_page_slug_render',
            'nb_settings_group',
            'nb_settings_section',
            array('description' => __('Select the page where the widget will appear', 'nightsbridge'))
            );

    add_settings_field(
            'nb_primary_color',
            __('Primary Color', 'nightsbridge'),
            'nb_primary_color_render',
            'nb_settings_group',
            'nb_settings_section',
            array('description' => __('Select the primary color for the widget', 'nightsbridge'))
            );
    
    add_settings_field(
            'nb_button_text_color',
            __('Button Text Color', 'nightsbridge'),
            'nb_button_text_color_render',
            'nb_settings_group',
            'nb_settings_section',
            array('description' => __('Select the text color for the button', 'nightsbridge'))
            );
    
    add_settings_field(
            'nb_button_hover_color',
            __('Button Hover Color', 'nightsbridge'),
            'nb_button_hover_color_render',
            'nb_settings_group',
            'nb_settings_section',
            array('description' => __('Select the hover background color for the button', 'nightsbridge'))
            );

    add_settings_field(
            'nb_button_border_radius',
            __('Button Border Radius', 'nightsbridge'),
            'nb_button_border_radius_render',
            'nb_settings_group',
            'nb_settings_section',
            array('description' => __('Specify the border radius for the button in pixels (e.g., 3px, 5px)', 'nightsbridge'))
);

    add_settings_field(
        'nb_button_text',
        __('Button Text', 'nightsbridge'),
        'nb_button_text_render',
        'nb_settings_group',
        'nb_settings_section',
        array('description' => __('Specify the text for the button', 'nightsbridge'))
            );
}

/**
 * Sanitize the admin input settings for the NightsBridge Widget.
 *
 * @param array $input The input settings array.
 * @return array The sanitized settings array.
 */
 function nb_sanitize_settings($input) {
    $new_input = array();
    
    if (array_key_exists('nb_bbid', $input)) {
        $new_input['nb_bbid'] = is_string($input['nb_bbid']) ? sanitize_text_field($input['nb_bbid']) : '';
    } else {
        $new_input['nb_bbid'] = '';
    }
    
    if (array_key_exists('nb_custom_format', $input)) {
        $new_input['nb_custom_format'] = is_string($input['nb_custom_format']) ? sanitize_text_field($input['nb_custom_format']) : 'd-M-Y';
    } else {
        $new_input['nb_custom_format'] = 'd-M-Y';
    }
    
    if (array_key_exists('nb_language', $input)) {
        $new_input['nb_language'] = is_string($input['nb_language']) ? sanitize_text_field($input['nb_language']) : 'en-GB';
    } else {
        $new_input['nb_language'] = 'en-GB';
    }
    
    if (array_key_exists('nb_page_slug', $input)) {
        $new_input['nb_page_slug'] = is_string($input['nb_page_slug']) ? sanitize_title($input['nb_page_slug']) : '';
    } else {
        $new_input['nb_page_slug'] = '';
    }
    
    if (array_key_exists('nb_primary_color', $input)) {
        $new_input['nb_primary_color'] = is_string($input['nb_primary_color']) ? sanitize_hex_color($input['nb_primary_color']) : '#000000';
    } else {
        $new_input['nb_primary_color'] = '#000000';
    }
    
    if (array_key_exists('nb_button_text_color', $input)) {
    	$new_input['nb_button_text_color'] = is_string($input['nb_button_text_color']) ? sanitize_hex_color($input['nb_button_text_color']) : '#ffffff';
    } else {
    	$new_input['nb_button_text_color'] = '#ffffff';
    }
    
    if (array_key_exists('nb_button_hover_color', $input)) {
    	$new_input['nb_button_hover_color'] = is_string($input['nb_button_hover_color']) ? sanitize_hex_color($input['nb_button_hover_color']) : '#b6cc6a';
    } else {
    	$new_input['nb_button_hover_color'] = '#b6cc6a';
    }
    
    if (array_key_exists('nb_button_border_radius', $input)) {
        $new_input['nb_button_border_radius'] = is_string($input['nb_button_border_radius']) ? sanitize_text_field($input['nb_button_border_radius']) : '';
    } else {
        $new_input['nb_button_border_radius'] = '';
    }

    if (array_key_exists('nb_button_text', $input)) {
        $new_input['nb_button_text'] = is_string($input['nb_button_text']) ? sanitize_text_field($input['nb_button_text']) : 'Check Availability';
    } else {
        $new_input['nb_button_text'] = 'Check Availability';
    }
    
    return $new_input;
}

/**
 * Admin Settings field render functions
 */
function nb_bbid_render($args) {
    $options = get_option('nb_settings');
    $value = (array_key_exists('nb_bbid', $options) && is_string($options['nb_bbid'])) ? esc_attr($options['nb_bbid']) : '';
    ?>
    <input type="text" name="nb_settings[nb_bbid]" value="<?php echo $value; ?>">
    <p class="description"><?php echo esc_html($args['description']); ?></p>
    <?php
}

/**
 * Renders the input field for setting a custom date format in the plugin settings.
 *
 * @param array $args Arguments used to render the setting field, including a description.
 *
 * @since 1.0.0
 */
function nb_custom_format_render($args) {
    $options = get_option('nb_settings');
    $value = array_key_exists('nb_custom_format', $options) ? esc_attr($options['nb_custom_format']) : 'd-M-Y';
    ?>
    <input type="text" name="nb_settings[nb_custom_format]" value="<?php echo $value; ?>">
    <p class="description"><?php echo esc_html($args['description']); ?></p>
    <?php
}

/**
 * Renders a dropdown list of supported languages for the NightsBridge Widget.
 *
 * @param array $args Arguments used to render the setting field, including a description.
 *
 * @since 1.0.0
 */
function nb_language_render($args) {
    $options = get_option('nb_settings');
    $langs = array(
        'en-GB' => 'English (UK)',
        'en-US' => 'English (US)',
        'af-ZA' => 'Afrikaans'
    );
    // Check if the language option is set in the plugin settings.
    $selected = isset($options['nb_language']) ? $options['nb_language'] : 'en-GB';
    ?>
    <select name="nb_settings[nb_language]">
        <?php foreach ($langs as $code => $name) : ?>
            <option value="<?php echo esc_attr($code); ?>" <?php selected($selected, $code); ?>>
                <?php echo esc_html($name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php echo esc_html($args['description']); ?></p>
    <?php
}

/**
 * Renders the input field for setting the primary color in the plugin settings.
 *
 * @param array $args Arguments used to render the setting field, including a description.
 *
 * @since 1.0.0
 */
function nb_primary_color_render($args) {
    $options = get_option('nb_settings');
    $value = isset($options['nb_primary_color']) && is_string($options['nb_primary_color']) ? sanitize_hex_color($options['nb_primary_color']) : '#000000';
    ?>
    <input type="text" name="nb_settings[nb_primary_color]" value="<?php echo esc_attr($value); ?>" class="nbw-color-picker">
    <p class="description"><?php echo esc_html($args['description']); ?></p>
    <?php
}

/**
 * Generates the page select dropdown for the settings page.
 *
 * @param array $args The array of arguments passed to the add_settings_field function.
 * @since 1.0.0
 */
function nb_page_slug_render($args) {
    $options = get_option('nb_settings');
    $selected = !empty($options['nb_page_slug']) ? sanitize_title($options['nb_page_slug']) : '';
    $pages = get_pages(); // Get all WordPress pages
    if (empty($pages)) {
        echo '<p class="description">' . esc_html__('No pages found.', 'nightsbridge') . '</p>';
        return;
    }
    ?>
    <select name="nb_settings[nb_page_slug]">
        <option value=""><?php echo esc_html__('Select a page', 'nightsbridge'); ?></option>
        <?php foreach ($pages as $page) : ?>
            <option value="<?php echo esc_attr($page->post_name); ?>" <?php selected($selected, $page->post_name); ?>>
                <?php echo esc_html($page->post_title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php echo esc_html($args['description']); ?></p>
    <p class="description"><?php echo esc_html__('The NightsBridge Plugin uses two shortcodes to render the widget on the front-end:', 'nightsbridge'); ?></p>
    <h3><?php echo esc_html__('Shortcodes', 'nightsbridge'); ?></h3>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
        <span><?php echo esc_html__('[nb_availability_check] - The shortcode for the Check Availability Widget', 'nightsbridge'); ?></span>
        <button type="button" class="button nb-copy-button" data-shortcode="[nb_availability_check]"><?php echo esc_html__('Copy Shortcode', 'nightsbridge'); ?></button>
    </div>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
        <span><?php echo esc_html__('[nb_availability_search] - The shortcode and shows accommodation, dates available in calendar form', 'nightsbridge'); ?></span>
        <button type="button" class="button nb-copy-button" data-shortcode="[nb_availability_search]"><?php echo esc_html__('Copy Shortcode', 'nightsbridge'); ?></button>
    </div>
    <h3><?php echo esc_html__('Information', 'nightsbridge'); ?></h3>
    <p><?php echo esc_html__('To add the Widgets copy and paste the shortcodes into the selected page. This is in order to load the Javascript and CSS for the widget only on the page selected as opposed to all WordPress pages. This improves page load speeds and avoids any conflict with other plugins.', 'nightsbridge'); ?></p>
    <?php
}

/**
 * Outputs the content for the settings section header.
 *
 * @since 1.0.0
 */
function nb_settings_section_callback() {
    try {
        $message = __('Configure your NightsBridge widget settings below.', 'nightsbridge');
        if ($message === null) {
            throw new Exception('Translation function returned null.');
        }
        echo esc_html($message);
    } catch (Exception $e) {
        error_log('Error in nb_settings_section_callback: ' . $e->getMessage());
        echo esc_html__('An error occurred while loading the settings section.', 'nightsbridge');
    }
}

/**
 * Render Nightsbridge settings page
 */
function nb_options_page() {
    try {
        $page_title = get_admin_page_title();
        if ($page_title === null) {
            throw new Exception('Admin page title is null.');
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($page_title); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('nb_settings_group');
                wp_nonce_field('nb_settings_action', 'nb_settings_nonce');
                do_settings_sections('nb_settings_group');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    } catch (Exception $e) {
        error_log('Error rendering options page: ' . $e->getMessage());
        echo '<div class="notice notice-error"><p>' . esc_html__('An error occurred while rendering the settings page.', 'nightsbridge') . '</p></div>';
    }
}

/**
 * Shortcode to display availability search grid view. Shortcode: [nb_availability_search]
 */
function display_nightsbridge_availability_search() {
    $options = get_option('nb_settings');
    if ($options === null) {
        throw new Exception('Options are null.');
    }
    $bbid = !empty($options['nb_bbid']) ? esc_attr($options['nb_bbid']) : '';
    if (empty($bbid)) {
        return '<p class="nb-error">' . esc_html__('Please set a Nightsbridge Booking ID in the plugin settings.', 'nightsbridge') . '</p>';
    }
    ob_start();
    ?>
       <div id="availability_search">
            <div id="nb_gridwidget"></div>
            <script type="text/javascript" src="https://www.nightsbridge.co.za/bridge/view?gridwidget=1&bbid=<?php echo esc_attr($bbid); ?>&height=720&width=1000"></script>
        </div>
        <?php
    return ob_get_clean();
}
add_shortcode('nb_availability_search', 'display_nightsbridge_availability_search');

/**
 * Shortcode to display booking button. Shortcode: [nb_availability_check]
 */
function display_nightsbridge_availability_check() {
    try {
        $options = get_option('nb_settings');
        if ($options === false) {
            throw new Exception('Failed to retrieve plugin settings.');
        }

        $bbid = !empty($options['nb_bbid']) ? esc_attr($options['nb_bbid']) : '';
        ob_start();
        if (empty($bbid)) {
            echo '<p class="nb-error">' . esc_html__('Please set a NightsBridge Booking ID in the plugin settings.', 'nightsbridge') . '</p>';
        } else {
            ?>
                <br><br>
                <div class="nb_grid-container">
                    <div class="nb_grid-header" data-title="Arrival">
                        <label class="nb_header" id="nb_checkInHeader"><?php echo esc_html__('Arrival', 'nightsbridge'); ?></label>
                    </div>
                    <div class="nb_grid-header" data-title="Departure">
                        <label class="nb_header" id="nb_checkOutHeader"><?php echo esc_html__('Departure', 'nightsbridge'); ?></label>
                    </div>
                    <div></div> 
                    <div data-title="Arrival">
                        <label id="check_in" class="arriv_depart"><?php echo esc_html__('Arrival', 'nightsbridge'); ?></label>
                        <div class="nb_datePicker">
                            <input class="form-control" type="text" id="nb_CheckInDate" placeholder="DD-MM-YYYY">
                        </div>
                    </div>
                    <div data-title="Departure">
                        <label id="check_out" class="arriv_depart"><?php echo esc_html__('Departure', 'nightsbridge'); ?></label>
                        <div class="nb_datePicker">
                            <input class="form-control" type="text" id="nb_CheckOutDate" placeholder="DD-MM-YYYY">
                        </div>
                    </div>
                    <div>
                        <button id="nb_checkAvailabilityBtn" class="nb_btn" type="submit" value="Check Availability">
                            <span class="nb_buttonText"><?php echo esc_html__('CHECK AVAILABILITY', 'nightsbridge'); ?></span>
                        </button>
                    </div>
                </div>
            </div>
            <br>
            <div id="availabilityModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn">&times;</span>
                    <iframe id="availabilityIframe" src="" style="width: 100%; height: 800px; border: none;"></iframe>
                </div>
            </div>
            <?php
        }
        return ob_get_clean();
    } catch (Exception $e) {
        error_log('Error in display_nightsbridge_widget_availability_check: ' . $e->getMessage());
        return '<p class="nb-error">' . esc_html__('An error occurred while loading the availability check.', 'nightsbridge') . '</p>';
    }
}
add_shortcode('nb_availability_check', 'display_nightsbridge_availability_check');

/**
 * Apply custom styles
 */
function nbw_apply_custom_styles() {
    try {
        $options = get_option('nb_settings');
        if ($options === null) {
            throw new Exception('Failed to retrieve plugin settings.');
        }

        $primary_color = !empty($options['nb_primary_color']) ? sanitize_hex_color($options['nb_primary_color']) : '#000000';
        $button_text_color = !empty($options['nb_button_text_color']) ? sanitize_hex_color($options['nb_button_text_color']) : '#ffffff';
        $button_hover_color = !empty($options['nb_button_hover_color']) ? sanitize_hex_color($options['nb_button_hover_color']) : '#b6cc6a';
        $button_border_radius = !empty($options['nb_button_border_radius']) ? sanitize_text_field($options['nb_button_border_radius']) : '';
        $button_text = !empty($options['nb_button_text']) ? sanitize_text_field($options['nb_button_text']) : 'Check Availability';

        ?>
        <style type="text/css">
            :root {
                --nb-primary-color: <?php echo esc_attr($primary_color); ?>;
                --nb-button-text-color: <?php echo esc_attr($button_text_color); ?>;
                --nb-button-hover-color: <?php echo esc_attr($button_hover_color); ?>;
                --nb-button-border-radius: <?php echo esc_attr($button_border_radius); ?>;
            }
            .nb_btn {
                background-color: var(--nb-primary-color);
                color: var(--nb-button-text-color) !important;
                border-radius: var(--nb-button-border-radius);
            }
            .nb_btn:hover {
                background-color: var(--nb-button-hover-color);
                color: var(--nb-button-text-color) !important;
            }
        </style>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                var button = document.getElementById('nb_checkAvailabilityBtn');
                if (button) {
                    button.value = "<?php echo esc_js($button_text); ?>";
                    button.querySelector('.nb_buttonText').textContent = "<?php echo esc_js($button_text); ?>";
                }
            });
        </script>
        <?php
    } catch (Exception $e) {
        error_log('Error applying custom styles: ' . $e->getMessage());
        echo '<style type="text/css">.nb_btn { display: none; }</style>';
    }
}
add_action('wp_head', 'nbw_apply_custom_styles');

/**
 * Enqueue color picker script and styles
 */
function nbw_enqueue_color_picker($hook_suffix) {
    try {
        if ($hook_suffix !== 'settings_page_nb_settings') {
            return;
        }
        wp_enqueue_style('wp-color-picker');
        if (file_exists(plugin_dir_path(__FILE__) . 'js/nbw-color-picker.js')) {
            wp_enqueue_script('nbw-color-picker', plugins_url('js/nbw-color-picker.js', __FILE__), array('wp-color-picker'), false, true);
        } else {
            throw new Exception('Color picker script not found.');
        }
    } catch (Exception $e) {
        error_log('Error enqueuing color picker: ' . $e->getMessage());
    }
}
add_action('admin_enqueue_scripts', 'nbw_enqueue_color_picker');

/**
 * Renders the input field for setting the button text color in the plugin settings.
 *
 * @param array $args Arguments used to render the setting field, including a description.
 *
 * @since 1.0.0
 */
function nb_button_text_color_render($args) {
    $options = get_option('nb_settings');
    if (isset($options['nb_button_text_color'])) {
        $value = sanitize_hex_color($options['nb_button_text_color']);
    } else {
        $value = '#ffffff';
    }
    ?>
    <input type="text" name="nb_settings[nb_button_text_color]" value="<?php echo esc_attr($value); ?>" class="nbw-color-picker">
    <p class="description"><?php echo esc_html($args['description']); ?></p>
    <?php
}

/**
 * Renders the input field for setting the button hover color in the plugin settings.
 *
 * @param array $args Arguments used to render the setting field, including a description.
 *
 * @since 1.0.0
 */
function nb_button_hover_color_render($args) {
    $options = get_option('nb_settings');
    if (isset($options['nb_button_hover_color'])) {
        $value = sanitize_hex_color($options['nb_button_hover_color']);
    } else {
        $value = '#b6cc6a';
    }
    ?>
    <input type="text" name="nb_settings[nb_button_hover_color]" value="<?php echo esc_attr($value); ?>" class="nbw-color-picker">
    <p class="description"><?php echo esc_html($args['description']); ?></p>
    <?php
}

/**
 * Renders the input field for setting the button border radius in the plugin settings.
 *
 * @param array $args Arguments used to render the setting field, including a description.
 *
 * @since 1.0.0
 */
function nb_button_border_radius_render($args) {
    $options = get_option('nb_settings');
    $value = isset($options['nb_button_border_radius']) ? esc_attr($options['nb_button_border_radius']) : '4px';
    ?>
    <input type="text" name="nb_settings[nb_button_border_radius]" value="<?php echo esc_attr($value); ?>">
    <p class="description"><?php echo esc_html($args['description']); ?></p>
    <?php
}

/**
 * Renders the input field for setting the button text in the plugin settings.
 *
 * @param array $args Arguments used to render the setting field, including a description.
 *
 * @since 1.0.0
 */
function nb_button_text_render($args) {
    $options = get_option('nb_settings');
    $value = isset($options['nb_button_text']) ? esc_attr($options['nb_button_text']) : 'Check Availability';
    ?>
    <input type="text" name="nb_settings[nb_button_text]" value="<?php echo esc_attr($value); ?>">
    <p class="description"><?php echo esc_html($args['description']); ?></p>
    <?php
}

/**
 * Load text domain for translations
 */
add_action('plugins_loaded', function() {
	load_plugin_textdomain('nightsbridge', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});

/**
 * Cleanup on plugin uninstall
 */
register_uninstall_hook(__FILE__, 'nb_uninstall');
function nb_uninstall() {
    $options = get_option('nb_settings');
    if ($options === null) {
        error_log('Failed to retrieve plugin settings during uninstall.');
        return;
    }
    delete_option('nb_settings');
}

