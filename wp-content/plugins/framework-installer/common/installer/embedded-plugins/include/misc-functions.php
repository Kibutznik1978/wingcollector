<?php

function installer_ep_is_fresh_wp_install() {

    static $check = null;
    if ($check !== null) {
        return $check;
    }
    $posts = get_posts('post_type=any');
    if (count($posts) == 2) {
        //Two posts, confirm if these are default WordPress post and page
        $valid_posts=array();
        foreach ($posts as $k=>$v) {
            $the_title=$v->post_title;
            if ((( $the_title == __('Hello world!') )) || (( $the_title == __('Sample Page') ))) {
                $valid_posts[]=$the_title;
            }
        }
        $count_this=count($valid_posts);
        if ($count_this===2) {
            return TRUE;
        } else {
            return FALSE;
        }
    } else {
        return FALSE;
    }

    //Stay safe, return FALSE
    return FALSE;
}


function installer_ep_get_configuration($theme_name = null){

    if(isset(WP_Installer()->installer_embedded_plugins)){
        if(is_null($theme_name)){
            $theme_name = wp_get_theme()->get('Name');
        }
        $config = WP_Installer()->installer_embedded_plugins->get_config($theme_name);

    }

    return $config;
}