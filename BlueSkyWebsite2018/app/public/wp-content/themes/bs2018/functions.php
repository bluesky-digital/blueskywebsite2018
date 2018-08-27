<?php

function my_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

function mycustomscript_enqueue() {
    wp_enqueue_script( 'custom-scripts', get_stylesheet_directory_uri() . '/js/midnight.jquery.min.js', array( 'jquery' ));
}
add_action( 'wp_enqueue_scripts', 'mycustomscript_enqueue' );


function wph_assets_scripts() {
// Google jQuery
wp_deregister_script('jquery');
wp_register_script('jquery', '//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js', false, '1.12.4');
wp_enqueue_script('jquery');

if ( is_page('blog') ) { // CHANGE PAGE SLUG OR REMOVE CONDITIONAL STATEMENT
wp_enqueue_script('mixitup_js', 'mixitup.min.js', true, '2.1.11'); // ENTER PATH TO FILE ON YOUR SERVER
}
}
add_action( 'wp_enqueue_scripts', 'wph_assets_scripts', 15);


?>
