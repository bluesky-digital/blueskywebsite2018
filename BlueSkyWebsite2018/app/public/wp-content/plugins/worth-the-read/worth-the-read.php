<?php
/**
 * Plugin Name: Worth The Read
 * Plugin URI: http://www.welldonemarketing.com
 * Description: Adds read length progress bar to single posts and pages, as well as an optional reading time commitment label to post titles.
 * Version: 1.4
 * Author: Well Done Marketing
 * Author URI: http://www.welldonemarketing.com
 * License: GPL2
 */

// Load the embedded Redux Framework
if ( file_exists( dirname( __FILE__ ).'/options/framework.php' ) ) {
    require_once dirname(__FILE__).'/options/framework.php';
}

// Load the plugin settings
if ( file_exists( dirname( __FILE__ ).'/wtr-config.php' ) ) {
    require_once dirname(__FILE__).'/wtr-config.php';
}

// add custom metaboxes
function wtr_custom_meta() {
    $options = get_option( 'wtr_settings' );
    $types_builtin = is_array($options['progress-display']) ? $options['progress-display'] : array();
	$types_cpts = array();
	if(isset($options['progress-cpts'])) {
		if(is_array($options['progress-cpts'])) $types_cpts = $options['progress-cpts'];
	}
	$types = array_merge($types_builtin, $types_cpts);
	if(isset($types)) {
		if(is_array($types)) {
		    foreach ($types as $type) {
		        add_meta_box(
		            'wtr_custom',           // Unique ID
		            'Worth The Read',  		// Box title
		            'wtr_custom_html',  	// Content callback, must be of type callable
		            $type,                // Post type
		            'side',					// Context
		            'default'				// Priority
		        );
		    }
		}
	}
}
add_action('add_meta_boxes', 'wtr_custom_meta');

function wtr_custom_html($post) {
	wp_nonce_field( basename( __FILE__ ), 'wtr_nonce' );
    $wtr_stored_meta = get_post_meta( $post->ID );
    ?>
    <p><label class="post-attributes-label" for="menu_order">Disable:</label></p>
    <p>
    <label for="wtr-disable-reading-progress">
        <input type="checkbox" name="wtr-disable-reading-progress" id="wtr-disable-reading-progress" value="yes" <?php if ( isset ( $wtr_stored_meta['wtr-disable-reading-progress'] ) ) checked( $wtr_stored_meta['wtr-disable-reading-progress'][0], 'yes' ); ?> />
        <?php _e( 'Reading progress bar', 'wtr' )?>
    </label>
    </p>
    <p>
    <label for="wtr-disable-time-commitment">
        <input type="checkbox" name="wtr-disable-time-commitment" id="wtr-disable-time-commitment" value="yes" <?php if ( isset ( $wtr_stored_meta['wtr-disable-time-commitment'] ) ) checked( $wtr_stored_meta['wtr-disable-time-commitment'][0], 'yes' ); ?> />
        <?php _e( 'Time commitment label', 'wtr' )?>
    </label>
	</p>
    <?php
}

// save the custom meta
function wtr_meta_save( $post_id ) {
    // Checks save status
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    $is_valid_nonce = ( isset( $_POST[ 'wtr_nonce' ] ) && wp_verify_nonce( $_POST[ 'wtr_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
 
    // Exits script depending on save status
    if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
        return;
    }
 
    // Checks for input and sanitizes/saves if needed
	if( isset( $_POST[ 'wtr-disable-reading-progress' ] ) ) {
	    update_post_meta( $post_id, 'wtr-disable-reading-progress', 'yes' );
	} else {
	    update_post_meta( $post_id, 'wtr-disable-reading-progress', '' );
	}
	 
	// Checks for input and saves
	if( isset( $_POST[ 'wtr-disable-time-commitment' ] ) ) {
	    update_post_meta( $post_id, 'wtr-disable-time-commitment', 'yes' );
	} else {
	    update_post_meta( $post_id, 'wtr-disable-time-commitment', '' );
	}

}
add_action( 'save_post', 'wtr_meta_save' );


# load front-end assets
add_action( 'wp_enqueue_scripts', 'wtr_enqueued_assets' );
function wtr_enqueued_assets() {
	# don't load js and css on homepage unless this is set to display there
	$options = get_option( 'wtr_settings' );
	$types = is_array($options['progress-display']) ? $options['progress-display'] : array();
	$load_scripts = true;
	if(is_front_page() && !in_array('home', $types)) {
		$load_scripts = false;
	}
	if($load_scripts) {
		wp_enqueue_script( 'wtr-js', plugin_dir_url( __FILE__ ) . 'js/wtr.js', array( 'jquery' ), false, true );
		wp_enqueue_style( 'wtr-css', plugin_dir_url( __FILE__ ) . 'css/wtr.css', false, false, 'all');
	}
}

# wrap content in div with php variables in data attributes
add_filter( 'the_content', 'wtr_wrap_content', 10, 2 ); 
function wtr_wrap_content( $content ) { 
	global $post;
	$options = get_option( 'wtr_settings' );
	$placement = $options['progress-placement'];
	$placement_offset = empty($options['progress-offset']) ? 0 : $options['progress-offset'];
	$content_offset = empty($options['content-offset']) ? 0 : $options['content-offset'];
	$placement_touch = $options['progress-placement-touch'];
	$placement_offset_touch = empty($options['progress-offset-touch']) ? 0 : $options['progress-offset-touch'];
	$width = $options['progress-thickness'];
	$fgopacity = $options['progress-foreground-opacity'];
	$mutedopacity = $options['progress-muted-opacity'];
	$mute = isset($options['progress-fixed-opacity']) ? $options['progress-fixed-opacity'] : '';
	$transparent = isset($options['progress-transparent-background']) ? $options['progress-transparent-background'] : '';
	$touch = isset($options['progress-touch']) ? $options['progress-touch'] : '';
	$comments = get_comment_count($post->ID)['approved'] > 0 ? $options['progress-comments'] : 0;
	$comments_bg = $options['progress-comments-background'];
	$fg = $options['progress-foreground'];
	$bg = $options['progress-background'];
	$fg_muted = $options['progress-muted-foreground'];
	$types_builtin = is_array($options['progress-display']) ? $options['progress-display'] : array();
	$types_cpts = array();
	if(isset($options['progress-cpts'])) {
		if(is_array($options['progress-cpts'])) $types_cpts = $options['progress-cpts'];
	}
	$types = array_merge($types_builtin, $types_cpts);
	// override with single post/page settings
	$disable = get_post_meta($post->ID, 'wtr-disable-reading-progress', true);
	if ( !empty($types) && is_singular($types) && !$disable) {
		$content = '<div id="wtr-content" 
	    	data-bg="' . $bg . '" 
	    	data-fg="' . $fg . '" 
	    	data-width="' . $width . '" 
	    	data-mute="' . $mute . '" 
	    	data-fgopacity="' . $fgopacity . '" 
	    	data-mutedopacity="' . $mutedopacity . '" 
	    	data-placement="' . $placement . '" 
	    	data-placement-offset="' . $placement_offset . '" 
	    	data-content-offset="' . $content_offset . '" 
	    	data-placement-touch="' . $placement_touch . '" 
		    data-placement-offset-touch="' . $placement_offset_touch . '" 
	    	data-transparent="' . $transparent . '" 
	    	data-touch="' . $touch . '" 
	    	data-comments="' . $comments . '" 
	    	data-commentsbg="' . $comments_bg . '" 
	    	data-location="page" 
	    	data-mutedfg="' . $fg_muted . '" 
	    	>' . $content . '</div>';
	}
	return $content;
}

# display on the home page 
add_action( 'wp_footer', 'wtr_wrap_home', 10, 2 ); 
function wtr_wrap_home() {
	global $post;
	$options = get_option( 'wtr_settings' );
	$placement = $options['progress-placement'];
	$placement_offset = empty($options['progress-offset']) ? 0 : $options['progress-offset'];
	$content_offset = empty($options['content-offset']) ? 0 : $options['content-offset'];
	$placement_touch = $options['progress-placement-touch'];
	$placement_offset_touch = empty($options['progress-offset-touch']) ? 0 : $options['progress-offset-touch'];
	$width = $options['progress-thickness'];
	$fgopacity = $options['progress-foreground-opacity'];
	$mutedopacity = $options['progress-muted-opacity'];
	$mute = isset($options['progress-fixed-opacity']) ? $options['progress-fixed-opacity'] : '';
	$transparent = isset($options['progress-transparent-background']) ? $options['progress-transparent-background'] : '';
	$touch = isset($options['progress-touch']) ? $options['progress-touch'] : '';
	if(is_object($post)) {
		$comments = get_comment_count($post->ID)['approved'] > 0 ? $options['progress-comments'] : 0;
	} else {
		$comments = 0;
	}
	$comments_bg = $options['progress-comments-background'];
	$fg = $options['progress-foreground'];
	$bg = $options['progress-background'];
	$fg_muted = $options['progress-muted-foreground'];
	$types_home = false;
	if(isset($options['progress-display'])) {
		if(is_array($options['progress-display'])) {
			if(in_array('home', $options['progress-display'])) {
				$types_home = true;
			}
		}
	}
	// override with single post/page settings
	$disable = get_post_meta($post->ID, 'wtr-disable-reading-progress', true);
	# only do this if the home page is not showing a static page
	# because this would fall under the "page" post type instead
	if(is_front_page() && is_home() && $types_home && !$disable) {
		echo '<div id="wtr-content" 
		    	data-bg="' . $bg . '" 
		    	data-fg="' . $fg . '" 
		    	data-width="' . $width . '" 
		    	data-mute="' . $mute . '" 
		    	data-fgopacity="' . $fgopacity . '" 
		    	data-mutedopacity="' . $mutedopacity . '" 
		    	data-placement="' . $placement . '" 
		    	data-placement-offset="' . $placement_offset . '" 
		    	data-content-offset="' . $content_offset . '" 
		    	data-placement-touch="' . $placement_touch . '" 
		    	data-placement-offset-touch="' . $placement_offset_touch . '" 
		    	data-transparent="' . $transparent . '" 
		    	data-touch="' . $touch . '" 
		    	data-comments="' . $comments . '" 
		    	data-commentsbg="' . $comments_bg . '" 
		    	data-location="home" 
		    	data-mutedfg="' . $fg_muted . '" 
		    	></div>';
	}
}

# adds a handle to where the progress bar should end
function wtr_end() {
	return '<span class="wtr-end"></span>';
}
# create the end progress shortcode
add_shortcode( 'wtr-end', 'wtr_end' );

# wrap comments in div so we can get ahold of a total comment section height
# one of these two actions will usually run, but never at the same time
add_action( 'comment_form_after', 'wtr_wrap_comments' );
add_action( 'comment_form_closed', 'wtr_wrap_comments' );
function wtr_wrap_comments() {
	global $post;
	if(is_object($post)) {
		if(get_comment_count($post->ID)['approved'] > 0) echo '<div id="wtr-comments-end"></div>';
	}
}
# if the theme doesn't use either of those actions, try another one
if(!has_action( 'wtr_wrap_comments' )) add_action( 'wp_footer', 'wtr_wrap_comments_footer' );
function wtr_wrap_comments_footer() {
	global $post;
	# don't add this on homepage unless this is set to display there
	$options = get_option( 'wtr_settings' );
	$types_home = false;
	if(isset($options['progress-display'])) {
		if(is_array($options['progress-display'])) {
			if(in_array('home', $options['progress-display'])) {
				$types_home = true;
			}
		}
	}
	$show_div = true;
	if(is_front_page() && !$types_home) {
		$show_div = false;
	}
	if(is_object($post)) {
		if(get_comment_count($post->ID)['approved'] > 0 && $show_div) echo '<div id="wtr-comments-end" class="at-footer"></div>';
	}
}

# time commitment placement
add_action('loop_start','wtr_conditional_title');
function wtr_conditional_title($query){
	global $wp_query;
	if($query === $wp_query) {
		add_filter( 'the_title', 'wtr_filter_title', 10, 2);
	} else {
		remove_filter( 'the_title', 'wtr_filter_title', 10, 2);
	}
}
function wtr_filter_title( $title, $post_id ) {
	$options = get_option( 'wtr_settings' );
	$types_builtin = is_array($options['time-display']) ? $options['time-display'] : array();
	$types_cpts = array();
	if(isset($options['time-cpts'])) {
		if(is_array($options['time-cpts'])) $types_cpts = $options['time-cpts'];
	}
	$types = array_merge($types_builtin, $types_cpts);
	$placement = $options['time-placement'];
    global $post;
    if($post->ID == $post_id && in_the_loop()) {
    	if((is_singular($types) && !empty($types))) {
    	    if($placement=='before-title') {
    	    	$title = wtr_time_commitment() . $title;
    	    }elseif($placement=='after-title') {
    	    	$title = $title . wtr_time_commitment();
    	    }
    	}
    }
    return $title;
}
add_filter( 'the_content', 'wtr_filter_content', 10, 2);
function wtr_filter_content( $content ) {
	$options = get_option( 'wtr_settings' );
	$types_builtin = is_array($options['time-display']) ? $options['time-display'] : array();
	$types_cpts = array();
	if(isset($options['time-cpts'])) {
		if(is_array($options['time-cpts'])) $types_cpts = $options['time-cpts'];
	}
	$types = array_merge($types_builtin, $types_cpts);
	$placement = $options['time-placement'];
	if((is_singular($types) && !empty($types))) {
	    if($placement=='before-content') {
	    	$content = wtr_time_commitment() . $content;
	    }
	}
    return $content;
}

function wtr_time_commitment() {
	$out = '';
	global $post;
	$options = get_option( 'wtr_settings' );
	// override with single post/page settings
	$disable = get_post_meta($post->ID, 'wtr-disable-time-commitment', true);
	if($disable) return;
	// get raw content with no html tags
	$word_count = strip_tags(get_post_field( 'post_content', $post->ID ));
	$count_method = $options['time-method'];
	if($count_method=='space') {
		$word_count = count(preg_split('/\s+/', $word_count));
	} else {
		$word_count = str_word_count($word_count);
	}
	$wpm = empty($options['time-wpm']) ? 200 : $options['time-wpm'];
	$time_length = round($word_count / $wpm);
	// minimum read time is 1 minute
	if($time_length == 0) $time_length = 1; 
	$time_format = empty($options['time-format']) ? '# min read' : $options['time-format'];
    $time_label = str_replace('#', '<span class="wtr-time-number">' . $time_length . '</span>', $time_format);
    $time_typography = $options['time-typography'];
    $placement = $options['time-placement'];
    $cssblock = isset($options['time-block-level']) ? $options['time-block-level'] : '';
    $cssblock = $cssblock ? ' block' : '';
	$out .= '<span class="wtr-time-wrap' . $cssblock . ' ' . $placement . '">' . $time_label . '</span>';
	return $out;
}

# add the custom css to the head
add_action('wp_head','wtr_custom_css');
function wtr_custom_css() {
	$options = get_option( 'wtr_settings' );
	$css = $options['time-css'];
	if(!empty($css)) echo '<style type="text/css">' . $css . '</style>';
}

# create the time commitment shortcode
add_shortcode( 'wtr-time', 'wtr_time_commitment' );

?>