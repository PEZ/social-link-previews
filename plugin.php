<?php
/*
Plugin Name: Social Link Previews
Plugin URI: https://gitgub.com/PEZ/social-link-previews
Description: Generate social cards for Facebook, Twitter, etcetera
Version: 1.0
Author: Peter Strömberg
Author URI: https://github.com/PEZ
License: MIT
*/

namespace SLPPlugin;

const SHARE_IMAGE_WIDTH = 1200;
const SHARE_IMAGE_HEIGHT = 628;

// Add image sizes for social cards
function add_image_sizes()
{
    add_image_size('slp-size', SHARE_IMAGE_WIDTH, SHARE_IMAGE_HEIGHT, true);
}
add_action('init', 'SLPPlugin\\add_image_sizes');


function image_url_and_alt_for_post($post_id)
{
    $result = array('url' => null, 'alt' => '');

    $image_id = get_post_thumbnail_id($post_id);
    if ($image_id) {
        $image_src = wp_get_attachment_image_src($image_id, 'slp-size');
        $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        if ($image_src) {
            $result['url'] = $image_src[0];
            $result['alt'] = $image_alt;
            return $result;
        }
    }

    $default_image_post_id = get_option('default_image_post_id');
    if ($default_image_post_id) {
        $default_image_id = get_post_thumbnail_id($default_image_post_id);
        $default_image_src = wp_get_attachment_image_src($default_image_id, 'slp-size');
        $default_image_alt = get_post_meta($default_image_id, '_wp_attachment_image_alt', true);
        if ($default_image_src) {
            $result['url'] = $default_image_src[0];
            $result['alt'] = $default_image_alt;
            return $result;
        }
    }

    return $result;
}

function first_500_characters($raw_content)
{
    $no_shortcodes = strip_shortcodes($raw_content);
    $no_tags = wp_strip_all_tags($no_shortcodes, true);
    return substr($no_tags, 0, 500) . '…';
}

function excerpt_for_post($post)
{
    $excerpt = get_the_excerpt($post->ID);
    if (!empty($excerpt)) {
        return $excerpt;
    }
    return first_500_characters($post->post_content);
}

function social_cards_meta_tags()
{
    global $post;
    if ($post && is_singular()) {
        $post_title = esc_attr(get_the_title($post->ID));
        $post_excerpt = esc_attr(excerpt_for_post($post));
        $image_data = image_url_and_alt_for_post($post->ID);
        $image_url = $image_data['url'];
        $image_alt = $image_data['alt'] ?: 'Illustrating: ' . $post_title . '. (Probably a cute baby 😀)';
        
        // General SEO
        echo '<meta property="description" content="' . $post_excerpt . '"/>';
        
        // Open Graph (Facebook, etcetera, also LinkedIn)
        echo '<meta property="og:title" content="' . $post_title . '"/>';
        echo '<meta property="og:description" content="' . $post_excerpt . '"/>';
        echo '<meta property="og:url" content="' . esc_url(get_permalink($post->ID)) . '"/>';
        echo '<meta property="og:image" content="' . esc_url($image_url) . '"/>';
        echo '<meta property="og:image:width" content="' . SHARE_IMAGE_WIDTH . '"/>';
        echo '<meta property="og:alt" content="' . $image_alt . '"/>';
        echo '<meta property="og:image:height" content="' . SHARE_IMAGE_HEIGHT . '"/>';
        echo '<meta property="og:type" content="article"/>';

        // Twitter
        echo '<meta name="twitter:card" content="summary_large_image" />';
        echo '<meta name="twitter:creator" content="TCSparents" />';
        echo '<meta name="twitter:title" content="' . $post_title . '"/>';
        echo '<meta name="twitter:description" content="' . $post_excerpt . '"/>';
        echo '<meta name="twitter:image" content="' . esc_url($image_url) . '"/>';
        echo '<meta name="twitter:image:alt" content="' . $image_alt . '"/>';
    }
}
add_action('wp_head', 'SLPPlugin\\social_cards_meta_tags', 1);

// Settings page

function plugin_menu()
{
    add_options_page(
        'Social Link Previews Settings',
        'Social Link Previews',
        'manage_options',
        'slp-plugin-settings',
        'SLPPlugin\\settings_page'
    );
}

add_action('admin_menu', 'SLPPlugin\\plugin_menu');

function settings()
{
    register_setting('slp-plugin-settings-group', 'default_image_post_id');
}

add_action('admin_init', 'SLPPlugin\\settings');

function original_image_url_for_post($post_id)
{
    $image_id = get_post_thumbnail_id($post_id);
    $image_src = wp_get_attachment_image_src($image_id, 'slp-size');
    if ($image_src) {
        return $image_src[0];
    } else {
        return '';
    }
}

function settings_page() {
    ?>
    <div class="wrap">
        <h1>Social Link Previews Settings</h1>
    
        <form method="post" action="options.php">
            <?php settings_fields('slp-plugin-settings-group'); ?>
            <?php do_settings_sections('slp-plugin-settings-group'); ?>
    
            <table class="form-table">
                <tr valign="top">
                <th scope="row">Default primary image post:</th>
                <td>
                    <select name="default_image_post_id" id="default_image_post_id">
                        <option value="">Select post</option>
                    <?php
                    $default_image_post_id = get_option('default_image_post_id');
                    $posts = get_posts(array('post_type' => 'post', 'numberposts' => -1));
                    foreach ($posts as $post) {
                        echo '<option value="' . $post->ID . '"' . selected($default_image_post_id, $post->ID, false) . '>' . esc_html($post->post_title) . '</option>';
                    }
                    ?>
                    </select>
                    <input type="text" id="post_filter" placeholder="Filter posts">
                    <div style="margin-top: 10px; width: 100%; max-width: 1200px;">
                        <img id="selected_image" 
                             src="<?php echo ($default_image_post_id ? original_image_url_for_post($default_image_post_id) : ''); ?>"
                             alt="Selected image" 
                             style="width: 100%; display: <?php echo ($default_image_post_id ? 'block' : 'none'); ?>;">
                    </div>
                </td>
                </tr>
            </table>
    
            <?php submit_button('Save Changes', 'primary', 'submit', true); ?>
        </form>
    </div>
    <script>
        (function() {
            var postFilter = document.getElementById('post_filter');
            var defaultImagePostId = document.getElementById('default_image_post_id');
            var submitButton = document.getElementById('submit');
            var selectedImage = document.getElementById('selected_image');
            var savedImageId = '<?php echo $default_image_post_id; ?>';
    
            postFilter.addEventListener('input', function() {
                var filterText = postFilter.value.toLowerCase();
                var options = defaultImagePostId.getElementsByTagName('option');
    
                for (var i = 0; i < options.length; i++) {
                    var optionText = options[i].text.toLowerCase();
                    options[i].style.display = (optionText.indexOf(filterText) > -1) ? '' : 'none';
                }
            });
    
            defaultImagePostId.addEventListener('change', function() {
                var selectedValue = defaultImagePostId.value;
                submitButton.disabled = (selectedValue === savedImageId);
    
                if (selectedValue) {
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=get_share_image_url&post_id=' + selectedValue
                    })
                    .then(response => response.text())
                    .then(url => {
                        selectedImage.src = url;
                        selectedImage.style.display = 'block';
                    });
                } else {
                    selectedImage.style.display = 'none';
                }
            });
        })();
    </script>
    <?php
}


function share_image_url_callback() {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if ($post_id > 0) {
        $thumbnail_url = original_image_url_for_post($post_id);
        if ($thumbnail_url) {
            echo $thumbnail_url;
        } else {
            echo '';
        }
    } else {
        echo '';
    }
    wp_die(); // This is required to terminate immediately and return a proper response
}
add_action('wp_ajax_get_share_image_url', 'SLPPlugin\\share_image_url_callback');


function add_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=slp-plugin-settings">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'SLPPlugin\\add_settings_link');