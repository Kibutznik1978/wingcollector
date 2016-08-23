<div class="js-ddl-message-container dd-message-container"></div>

<div class="dd-layouts-wrap">

    <?php // TEMP layouts cell types dialog redesign ?>
    <?php

    ?>

	<div class="dd-layouts-header">
		<div id="icon-edit fa fa-pencil-square-o" class="icon32 icon32-posts-dd_layouts"><br></div>
        <h2>
        <?php  _e('Edit layout',  'ddl-layouts'); ?>
        </h2>
			<!--<span class="js-layout-title dd-layout-title"></span>-->
        <div id="titlediv" class="js-title-div">
            <div id="titlewrap">
                <span id="change_layout_name_message"><?php _e('Please enter a name for this layout','ddl-layouts'); ?></span>
                <input name="layout-title-input" id="title" class="js-layout-title dd-layout-title layout-title-input" value="<?php echo esc_attr(get_the_title($post->ID)); ?>"/>
            </div>
        </div>

        <div id="edit-slug-box" class="hide-if-no-js">
			<label for="layout-slug"><strong><?php _e('Layout slug:','ddl-layouts'); ?></strong></label>
            <span id="layout-slug" name="layout-slug" type="text" class="edit-layout-slug js-edit-layout-slug"><?php echo urldecode( $post->post_name ); ?></span>
            <span id="edit-slug-buttons"><a href="#post_name" class="edit-slug button button-small hide-if-no-js js-edit-slug"><?php _e( 'Edit', 'ddl-layouts' ); ?></a></span>
            <span id="edit-slug-buttons-active" class="js-edit-slug-buttons-active"><a href="#" class="save button button-small js-edit-slug-save">OK</a> <a class="cancel js-cancel-edit-slug" href="#">Cancel</a></span>
         <!--   <i class="icon-gear fa fa-cog edit-layout-settings js-edit-layout-settings" title="<?php _e( 'Set parent layout', 'ddl-layouts' ); ?>"></i> -->
            <button type="button" class="button button-small hide-if-no-js js-trash-layout trash-layout"><i class="fa fa-trash-o" aria-hidden="true"></i><!--<span>Move to trash<span>--></button>
        </div>
	</div>
    <input id="toolset-edit-data" type="hidden" value="<?php echo $post->ID; ?>" data-plugin="layouts" />
    <div class="toolset-video-box-wrap"></div>
</div>

<script type="text/html" id="ddl-layout-not-assigned-to-any">

    <div class="ddl-dialog-header">
        <h2><?php printf(__('%s', 'ddl-layouts'), '{{{ layout_name }}}');?></h2>
        <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
    </div>
    <div class="ddl-dialog-content">
        <?php printf(__('%s', 'ddl-layouts'), '{{{ message }}}'); ?>
    </div>
    <div class="ddl-dialog-footer">
        <button class="button js-edit-dialog-close"><?php _e('Close', 'ddl-layouts'); ?></button>
    </div>

</script>

<script type="text/html" id="ddl-layout-children-assignment_display">

    <div class="ddl-dialog-header">
        <h2><?php printf(__('%s', 'ddl-layouts'), '{{{ layout_name }}}'); ?></h2>
        <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
    </div>
    <div class="ddl-dialog-content">
        <?php printf(__('%s', 'ddl-layouts'), '{{{ message }}}'); ?>
        <# if( typeof children !== 'undefined' ){#>
            <div class="children-box-preview">
                <ul>
                    <#
                        _.each(children, function(child, index, list){

                        if( child.hasOwnProperty('id') && child.id !== 1 && child.hasOwnProperty('items') &&
                        child.items.length ){
                        #>


                        <#
                            _.each(child.items, function(item, i, l ){

                            if( item.hasOwnProperty('posts') && item.posts.length ){
                            #>

                            <#
                                _.each(item.posts, function(post){
                                if( post.link ) {
                                #>
                                <li><a href="{{{post.link}}}" target="_blank">{{{post.post_title}}}</a></li>
                                <#
                                    }
                                    });
                                    #>

                                    <#
                                        }
                                        if( item.hasOwnProperty('types') && item.types.length ){
                                        #>

                                        <#
                                            _.each(item.types, function(post){
                                            if( post.link ) {
                                            #>
                                            <li><a href="{{{post.link}}}" target="_blank">{{{post.singular}}}</a></li>
                                            <#
                                                }
                                                });
                                                #>

                                                <#
                                                    }
                                                    if( item.hasOwnProperty('loops') && item.loops.length ){

                                                    #>

                                                    <#
                                                        _.each(item.loops, function(post){
                                                        if( post.href ) {
                                                        #>
                                                        <li><a href="{{{post.href}}}" target="_blank">{{{post.title}}}</a></li>
                                                        <#
                                                            }
                                                            });
                                                            #>
                                                            <#
                                                                }
                                                                });
                                                                #>


                                                                <#    }
                                                                    });#>
                </ul>
            </div>
            <#
                }
                #>
    </div>
    <div class="ddl-dialog-footer">
        <button class="button js-edit-dialog-close"><?php _e('Close', 'ddl-layouts'); ?></button>
    </div>

</script>




<script type="text/html" id="ddl-layout-children-no-assignment_display">

    <div class="ddl-dialog-header">
        <h2><?php printf(__('%s', 'ddl-layouts'), '{{{ layout_name }}}'); ?></h2>
        <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
    </div>
    <div class="ddl-dialog-content">
        <?php printf(__('%s', 'ddl-layouts'), '{{{ message }}}'); ?>
        <#
            if( typeof children !== 'undefined' && children[0].hasOwnProperty('items') ){

            #>
            <div class="children-box-preview">
                <ul>
                    <#
                        _.each(children[0].items, function(child, index, list){
                            var url = window.location.href.split(/[?#]/)[0],
                                url = url+'?page=dd_layouts_edit&layout_id='+child.id+'&action=edit';
                        #>
                                <li><a href="{{{url}}}">{{{child.post_title}}}</a></li>
                     <#   }); #>
                </ul>
            </div>
            <#
                }
                #>
    </div>
    <div class="ddl-dialog-footer">
        <button class="button js-edit-dialog-close"><?php _e('Close', 'ddl-layouts'); ?></button>
    </div>

</script>





<script type="text/html" id="ddl-layout-assigned-to-many">
    <div class="ddl-dialog-header">
        <h2><?php _e('Select which page or post to use to view this Layout', 'ddl-layouts');?></h2>
        <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
    </div>
    <div class="ddl-dialog-content ddl-layout-assigned-to-many">
        <ul>
            <#
                var type = '', count = 0;
                _.each(links, function(v){

                #>

                <#
                    var padding_top = count > 0 ? 'padding-top' : '';
                    if( type !== v.type ){

                    type = v.type;

                    #>
        <?php  printf(__('%s', 'ddl-layouts'), '<li class="post-type {{ padding_top }}">{{{ v.types }}}:</li>'); ?>

                    <#

                        }

                        if( v.href != '' && v.href != '#'){

                        #>
                        <li><a href="{{ v.href }}" title="{{{ v.title }}}" target="_blank" class="js-layout-preview-link">
                            {{{ v.title }}}
                        </a>
                        </li>



                        <#
                         count++;
                        }
                        else if( v.href == '#' ){
                        #>
                        <li>
                            {{{ v.title }}}
                        </li>
                        
                        <#
                        count++;
                        }
                     }); #>
        </ul>
    </div>
    <div class="ddl-dialog-footer">

        <button class="button js-edit-dialog-close"><?php _e('Close', 'ddl-layouts'); ?></button>
    </div>

</script>

<div class="ddl-dialogs-container">
    <div class="ddl-dialog auto-width" id="js-view-layout-dialog-container"></div>
</div>

<script type="text/html" id="js-virtual-form-tpl">
    <form method='post' action="{{ href }}" target="_blank" id="js-virtual-form-preview">
        <input type='hidden' name='layout_preview' id="js-layout-preview-json" />
    </form>
</script>