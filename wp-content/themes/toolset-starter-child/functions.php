<?php
function my_theme_enqueue_styles() {

    $parent_style = 'parent-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );




function my_previous_next_post() {
    // retrieve the value for next post link
   
    $next_string = "Next Wing &rarr;";
    ob_start(); 
    next_post_link("%link", $next_string);
    $next_link = ob_get_clean(); 
    
    // retrieve the value for previous post link
    $previous_string = "&larr; Previous Wing";
    ob_start(); 
    previous_post_link("%link", $previous_string);
    $previous_link = ob_get_clean(); 
    
    // build output
    $return = PHP_EOL . '<div id="next-previous" class="navigation clearfix">' . PHP_EOL;

    // display previous link if any
    if ($previous_link) {
        $return .= '<div class="nav-previous alignleft">'. PHP_EOL;
        $return .= $previous_link. PHP_EOL;
        $return .= '</div>'. PHP_EOL;
    }

    // display next link if any
    if ($next_link) {
        $return .= '<div class="nav-next alignright">'. PHP_EOL;
        $return .=  $next_link . PHP_EOL;
        $return .= '</div>'. PHP_EOL;
    }

    $return .= '</div>';

    return $return;
}
add_shortcode('previous-next-post-links', 'my_previous_next_post');










    ?>