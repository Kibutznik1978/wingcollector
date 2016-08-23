<?php

class WPV_style extends Toolset_Style
{

    public function __construct($handle, $path = 'wordpress_default', $deps = array(), $ver = false, $media = 'screen')
    {
        parent::__construct($handle, $path, $deps, $ver, $media);
    }
}

class WPV_script extends Toolset_Script
{
    public function __construct($handle, $path = 'wordpress_default', $deps = array(), $ver = false, $in_footer = false)
    {
        parent::__construct( $handle, $path, $deps, $ver, $in_footer);
    }
}

class WPV_scripts_manager extends Toolset_Assets_Manager
{

    protected function __initialize_styles()
    {
        return parent::__initialize_styles();
    }


    protected function __initialize_scripts()
    {
        return parent::__initialize_scripts();
    }
}
add_action('init', array('WPV_scripts_manager', 'getInstance'), 99);
