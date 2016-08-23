<?php
do_action('ddl-enqueue_styles', 'ddl-front-end');
$header =  __( 'There is no Layout assigned', 'ddl-layouts' );
$learn_link = "http://wp-types.com/documentation/user-guides/designing-pages-archive-templates-using-views-plugin#layouts-as-templates-for-content";
$learn_anchor = __( "Using Layouts as Templates for Contents", 'ddl-layouts' );
get_header();
?>

<div class="panel panel-default not-assigned ">
						<div class="panel-heading">
							<?php echo $header; ?>

</div>
<div class="panel-body">
    <div class="not-assigned-body">

        <h4><?php echo WPDDL_Templates_Settings::getInstance()->get_default_message(); ?> </h4>
<?php if( user_can_assign_layouts() ): ?>
        <a class="btn btn-lg btn-primary"
           href="<?php echo admin_url( 'admin.php?page=dd_layouts' ); ?>"
           title="<?php _e( "Layouts", 'ddl-layouts' ); ?>"><?php _e( "Assign a Layout", 'ddl-layouts' ); ?>
        </a>
<?php endif; ?>
    </div>
    <div class="not-assigned-helper">

        <p><?php _e( "Find out more:", 'ddl-layouts' ); ?></p>
        <ul>
            <li>
                <a href="<?php echo $learn_link;?>"
                   target="_blank"
                   title="<?php echo $learn_anchor  ?>">
                    <?php echo $learn_anchor ?>
                </a>
            </li>
            <li>
                <a href="http://wp-types.com/documentation/user-guides/develop-layouts-based-themes/#how-layout-plugins-works"
                   target="_blank"
                   title="<?php _e( "Learn how the Layouts plugin works", 'ddl-layouts' ); ?>">
                    <?php _e( 'Learn how the Layouts plugin works', 'ddl-layouts' ) ?>
                </a>
            </li>
            <li>
                <a href="http://discover-wp.com/site-types/toolset-classifieds-layouts/"
                   target="_blank"
                   title="<?php _e( "Try our reference site built with this theme", 'ddl-layouts' ); ?>">
                    <?php _e( 'Try our reference site built with this theme', 'ddl-layouts' ) ?>
                </a>
            </li>
        </ul>
    </div>
</div>
    <?php if( user_can_assign_layouts() ): ?>
<div class="panel-footer panel-footer-sm text-center">
    <?php _e( "You can see this message because you are logged in as a user who can assign Layouts. <br>Your visitors won't see this message.", 'ddl-layouts' ); ?>
</div>
    <?php endif; ?>
</div>

<?php get_footer();?>