<?php
class WPDDL_HelpVideos extends Toolset_HelpVideosFactoryAbstract{

    protected function define_toolset_videos(){
        return  array(
            'layout_template' =>  array(
                'name' => 'layouts_template',
                'url' => 'http://d7j863fr5jhrr.cloudfront.net/toolset-layouts-templates.mp4',
                'screens' => array('toolset_page_dd_layouts_edit'),
                'element' => '.toolset-video-box-wrap',
                'title' => __('Render Views Content Templates with Layouts', 'ddl-layouts'),
                'width' => '820px',
                'height' => '470px'
            ),
            'archive_layout' =>  array(
                'name' => 'layouts_archive',
                'url' => 'http://d7j863fr5jhrr.cloudfront.net/toolset-layouts-archives.mp4',
                'screens' => array('toolset_page_dd_layouts_edit'),
                'element' => '.toolset-video-box-wrap',
                'title' => __('Render Views Wordpress Archives with Layouts', 'ddl-layouts'),
                'width' => '820px',
                'height' => '470px'
            ),
        );
    }
}
add_action( 'init', array("WPDDL_HelpVideos", "getInstance") );