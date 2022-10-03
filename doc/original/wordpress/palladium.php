<?php
/*
Plugin Name: Palladium Cloak
Plugin URI: https://palladium.com/
Description: Use this plugin with any page or post
Version: 1.0
Author: Palladium Support <support@palladium.com>
Author URI: https://palladium.com/
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Hooking up our function to theme setup
add_action( 'init', ['Palladium', 'init']);

class Palladium {

    static public $fields = [
        'pl_filter_enabled',
        'pl_user_id',
        'pl_campaign_id',
        'pl_campaign_key',
        'pl_banner_source'
    ];

    public static function init()
    {
        $instance = new Palladium();

        add_action('add_meta_boxes', [$instance, 'showWidget']);
        add_action('save_post', [$instance, 'save']);
        add_action('template_redirect',[$instance,'execute'],1);


    }

    public function execute()
    {
        $post = get_post();
        $fields = $this->getFilterFields($post->ID);
        $answer = false;
        if (!empty($fields) && $fields['pl_filter_enabled'] == "on") {
            require_once plugin_dir_path( __FILE__ ) . "RequestHandlerClient.php";
            $requestFilter = new RequestHandlerClient(
                $fields['pl_user_id'],
                $fields['pl_campaign_id'],
                $fields['pl_campaign_key'],
                $fields['pl_banner_source']
            );
            $answer = $requestFilter->run();
        }

        if ($answer && $answer['result'] == true && !empty($answer['target'])) {
            header( "Location: {$answer['target']}");
            header('Content-Length: '.rand(1,128));
            exit;
        }
    }

    public function showWidget()
    {
        $screens = ['page', 'post', 'product'];
        foreach ($screens as $screen) {
            add_meta_box(
                'pl_section_id',
                __('Palladium Cloaker Options', 'textdomain'),
                [$this, 'print_widget'],
                $screen
            );
        }
    }

    public function print_widget($post)
    {
        wp_nonce_field('pl_save_widget_nonce', 'pl_widget_nonce');

        $meta_data = $this->filter_post_meta($post->ID);
        $filterEnabled = array_key_exists('pl_filter_enabled', $meta_data) && $meta_data['pl_filter_enabled'] == 'on' ? 'checked' : '';
        $userId = array_key_exists('pl_user_id', $meta_data) ? $meta_data['pl_user_id'] : '';
        $campaignId = array_key_exists('pl_campaign_id', $meta_data) ? $meta_data['pl_campaign_id'] : '';
        $campaignSig = array_key_exists('pl_campaign_key', $meta_data) ? $meta_data['pl_campaign_key'] : '';
        $source = array_key_exists('pl_banner_source', $meta_data) ? $meta_data['pl_banner_source'] : '';

        $html =
            '<label for="pl_filter_enabled" style="width: 20%; display:inline-block; text-align: right;">' . __('Enabled Filter', 'textdomain') . ': </label>
            <input id="pl_filter_enabled" name="pl_filter_enabled" type="checkbox" ' . $filterEnabled . ' class="form-input-tip" />
            <br><br>
            <label for="pl_user_id" style="width: 20%; display:inline-block; text-align: right;">' . __('User ID', 'textdomain') . ': </label>
            <input id="pl_user_id" name="pl_user_id" type="text" value="' . esc_attr($userId) . '"class="form-input-tip" style="width: 50%"/>
            <br><br>
            <label for="pl_campaign_id" style="width: 20%; display:inline-block; text-align: right;">' . __('Campaign ID', 'textdomain') . ': </label>
            <input id="pl_campaign_id" name="pl_campaign_id" type="text" value="' . esc_attr($campaignId) . '" class="form-input-tip" style="width: 50%"/>
            <br><br>
            <label for="pl_campaign_key" style="width: 20%; display:inline-block; text-align: right;">' . __('Campaign Key', 'textdomain') . ': </label>
            <input id="pl_campaign_key" name="pl_campaign_key" type="text" value="' . esc_attr($campaignSig) . '" class="form-input-tip" style="width: 50%"/>
            <br><br>
            <label for="pl_banner_source" style="width: 20%; display:inline-block; text-align: right;">' . __('Banner Source', 'textdomain') . ': </label>
            <select name="pl_banner_source">
                <option value="adwords" ' . ($source == "adwords" ? "selected" : "") . '>Google AdWords</option>
                <option value="facebook" ' . ($source == "facebook" ? "selected" : "") . '>Facebook</option>
                <option value="tiktok" ' . ($source == "tiktok" ? "selected" : "") . '>TikTok</option>
            </select>
            ';

        print $html;
    }

    public function save($post_id)
    {
        if (!array_key_exists('pl_widget_nonce', $_POST)) {
            return;
        }

        if (!wp_verify_nonce($_POST['pl_widget_nonce'], 'pl_save_widget_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (array_key_exists('post_type', $_POST) && 'page' == $_POST['post_type']) {

            if (!current_user_can('edit_page', $post_id)) {
                return;
            }

        } else {

            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        foreach (static::$fields as $field) {
            if ($field == "pl_filter_enabled" && !array_key_exists($field, $_POST)) {
                $_POST['pl_filter_enabled'] = "off";
            } else if (!array_key_exists($field, $_POST)) {
                return;
            }
        }

        // Strip Tags
        $patterns = ['@<(\?|\%)\=?(php)?@', '@(\%|\?)>@'];
        $replacements = ['', ''];

        foreach (static::$fields as $field) {
            update_post_meta($post_id, $field, preg_replace($patterns, $replacements, $_POST[$field]));
        }
    }

    public function filter_post_meta($post_id)
    {
        $meta_data = get_post_meta($post_id, null, true);

        if (!empty($meta_data)) {
            foreach (static::$fields as $field) {
                $meta_data[$field] = array_key_exists($field, $meta_data) ? esc_attr(current($meta_data[$field])) : '';
            }
        }

        return $meta_data;
    }

    public function getFilterFields($post_id)
    {
        $fields = [];
        $meta = $this->filter_post_meta($post_id);
        foreach ($meta as $key => $value) {
            if (in_array($key, self::$fields)) {
                $fields[$key] = $value;
            }
        }
        return $fields;
    }
}
