<?php

function my_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

function mycustomscript_enqueue() {
    wp_enqueue_script( 'custom-scripts', get_stylesheet_directory_uri() . '/js/midnight.jquery.min.js', array( 'jquery' ));
}
add_action( 'wp_enqueue_scripts', 'mycustomscript_enqueue' );

?>
