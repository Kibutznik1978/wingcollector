/* global _refsitesSettings */
window.wp = window.wp || {};

var refsite_install_current_step = 0;
var refsite_install_current_site_id = 0;
var refsite_install_check_num_posts = true;
var refsite_install_step_id_prefix = 'wpvdemo_step_';

jQuery(document).ready(function($){
	
	if (window.location.hash) {
		if (window.location.hash.indexOf('installsite') == 1) { 
			//Discover WP import mode, let's checked for W3TC repeat query string arguments so it won't interfere with the imports.
			if (window.location.href.indexOf("repeat=w3tc") > -1) {
				
				//Repeat=w3tc exists!
				//Get hash passed
				var hash_arguments=window.location.hash;
				
				//Get the loaded URL and remove the W3TC repeat argument
				var url_loaded=location.href.replace(/&?repeat=([^&]$|[^&]*)/i, "");
				
				//Let's formulate the modified URL
				var url_modified= url_loaded+hash_arguments;
				
				//Let's do another redirect using the modified URL
				window.location =	url_modified;  			
			}
		}
	} else {
		/** DISCOVER-WP SAFARI BROWSER INCOMPATIBILITY WITH W3TC 'repeat=w3tc'*/
		/** START */
		/** No hash detected */
		/** Check if there is repeat=w3tc in the URL */
		if (window.location.href.indexOf("repeat=w3tc") > -1) {					
				//Step1: We verify that we are installing site here.
				var currently_installing= _refsitesSettings.refsites_discoverwp_now_installing;
				if ( currently_installing == 'yes' ) {
					 //OK we are installing a site...
					 //Retrieve correct installation URL
					 var correct_install_url=_refsitesSettings.refsites_discoverwp_redirection_fix;
					 if (correct_install_url) {
						//Let's do another redirect to handle this case
						window.location =	correct_install_url; 						 
					 }
				}
        	 	
        	 	/** END */ 				
			
		}	  
	}
	
    // Store the refsite data and settings for organized and quick access
    // refsites.data.settings, refsites.data.refsites
    window.refsites = window.refsites || {};
    refsites.data = _refsitesSettings;
    var l10n = refsites.data.l10n;

    // Setup app structure
    _.extend(refsites, {model: {}, view: {}, routes: {}, router: {}, template: wp.template});

    refsites.Model = Backbone.Model.extend({
        // Adds attributes to the default data coming through the .org refsites api
        // Map `id` to `slug` for shared code
        initialize: function () {
            var description;

            // Set the attributes
            this.set({
                // slug is for installation, id is for existing.
                id: this.get('slug') || this.get('id')
            });

            // Map `section.description` to `description`
            // as the API sometimes returns it differently
            if (this.has('sections')) {
                description = this.get('sections').description;
                this.set({description: description});
            }
        }
    });

    // Main view controller for admin.php?page=manage-refsites
    // Unifies and renders all available views
    refsites.view.Appearance = wp.Backbone.View.extend({

        el: '#wpbody-content .wrap .refsite-browser',

        window: $( window ),
        // Pagination instance
        page: 0,

        // Sets up a throttler for binding to 'scroll'
        initialize: function( options ) {
            // Scroller checks how far the scroll position is
            _.bindAll( this, 'scroller' );

            this.SearchView = options.SearchView ? options.SearchView : refsites.view.Search;
            // Bind to the scroll event and throttle
            // the results from this.scroller
            this.window.bind( 'scroll', _.throttle( this.scroller, 300 ) );
        },

        // Main render control
        render: function() {
            // Setup the main refsite view
            // with the current refsite collection
            this.view = new refsites.view.Refsites({
                collection: this.collection,
                parent: this
            });

            // Render search form.
            this.search();

            // Render and append
            this.view.render();
            this.$el.empty().append( this.view.el ).addClass('rendered');
            this.$el.append( '<br class="clear"/>' );
        },

        // Defines search element container
        searchContainer: $( '#wpbody h2:first' ),

        // Search input and view
        // for current refsite collection
        search: function() {
            var view,
                self = this;

            // Don't render the search if there is only one refsite
            if ( refsites.data.refsites.length === 1 ) {
                return;
            }

            view = new this.SearchView({
                collection: self.collection,
                parent: this
            });

            // Render and append after screen title
            view.render();
            this.searchContainer
                .append( $.parseHTML( '<label class="screen-reader-text" for="wp-filter-search-input">' + l10n.search + '</label>' ) )
                .append( view.el );
        },

        // Checks when the user gets close to the bottom
        // of the mage and triggers a refsite:scroll event
        scroller: function() {
            var self = this,
                bottom, threshold;

            bottom = this.window.scrollTop() + self.window.height();
            threshold = self.$el.offset().top + self.$el.outerHeight( false ) - self.window.height();
            threshold = Math.round( threshold * 0.9 );

            if ( bottom > threshold ) {
                this.trigger( 'refsite:scroll' );
            }
        }
    });

    // Set up the Collection for our refsite data
    // @has 'id' 'name' 'screenshot' 'author' 'authorURI' 'version' 'active' ...
    refsites.Collection = Backbone.Collection.extend({

        model: refsites.Model,

        // Search terms
        terms: '',

        // Controls searching on the current refsite collection
        // and triggers an update event
        doSearch: function( value ) {

            // Don't do anything if we've already done this search
            // Useful because the Search handler fires multiple times per keystroke
            if ( this.terms === value ) {
                return;
            }

            // Updates terms with the value passed
            this.terms = value;

            // If we have terms, run a search...
            if ( this.terms.length > 0 ) {
                this.search( this.terms );
            }

            // If search is blank, show all refsites
            // Useful for resetting the views when you clean the input
            if ( this.terms === '' ) {
                this.reset( refsites.data.refsites );
            }

            // Trigger an 'update' event
            this.trigger( 'update' );
        },

        // Performs a search within the collection
        // @uses RegExp
        search: function( term ) {
            var match, results, haystack;

            // Start with a full collection
            this.reset( refsites.data.refsites, { silent: true } );

            // Escape the term string for RegExp meta characters
            term = term.replace( /[-\/\\^$*+?.()|[\]{}]/g, '\\$&' );

            // Consider spaces as word delimiters and match the whole string
            // so matching terms can be combined
            term = term.replace( / /g, ')(?=.*' );
            match = new RegExp( '^(?=.*' + term + ').+', 'i' );

            // Find results
            // _.filter and .test
            results = this.filter( function( data ) {
                haystack = _.union( data.get( 'title' ), data.get( 'id' ), data.get( 'tutorial_title' ), data.get( 'short_description' ), data.get( 'tagline' ) );

                if ( match.test( data.get( 'author' ) ) && term.length > 2 ) {
                    data.set( 'displayAuthor', true );
                }

                return match.test( haystack );
            });

            if ( results.length === 0 ) {
                this.trigger( 'query:empty' );
            } else {
                $( 'body' ).removeClass( 'no-results' );
            }

            this.reset( results );
        },

        // Paginates the collection with a helper method
        // that slices the collection
        paginate: function( instance ) {
            var collection = this;
            instance = instance || 0;

            // refsites per instance are set at 20
            collection = _( collection.rest( 20 * instance ) );
            collection = _( collection.first( 20 ) );

            return collection;
        },

        count: false,

        // Handles requests for more refsites
        // and caches results
        //
        // When we are missing a cache object we fire an apiCall()
        // which triggers events of `query:success` or `query:fail`
        query: function( request ) {
            /**
             * @static
             * @type Array
             */
            var queries = this.queries,
                self = this,
                query, isPaginated, count;

            // Store current query request args
            // for later use with the event `refsite:end`
            this.currentQuery.request = request;

            // Search the query cache for matches.
            query = _.find( queries, function( query ) {
                return _.isEqual( query.request, request );
            });

            // If the request matches the stored currentQuery.request
            // it means we have a paginated request.
            isPaginated = _.has( request, 'page' );

            // Reset the internal api page counter for non paginated queries.
            if ( ! isPaginated ) {
                this.currentQuery.page = 1;
            }

            // Otherwise, send a new API call and add it to the cache.
            if ( ! query && ! isPaginated ) {
                query = this.apiCall( request ).done( function( data ) {

                    // Update the collection with the queried data.
                    if ( data.refsites ) {
                        self.reset( data.refsites );
                        count = data.info.results;
                        // Store the results and the query request
                        queries.push( { refsites: data.refsites, request: request, total: count } );
                    }

                    // Trigger a collection refresh event
                    // and a `query:success` event with a `count` argument.
                    self.trigger( 'update' );
                    self.trigger( 'query:success', count );

                    if ( data.refsites && data.refsites.length === 0 ) {
                        self.trigger( 'query:empty' );
                    }

                }).fail( function() {
                    self.trigger( 'query:fail' );
                });
            } else {
                // If it's a paginated request we need to fetch more refsites...
                if ( isPaginated ) {
                    return this.apiCall( request, isPaginated ).done( function( data ) {
                        // Add the new refsites to the current collection
                        // @todo update counter
                        self.add( data.refsites );
                        self.trigger( 'query:success' );

                        // We are done loading refsites for now.
                        self.loadingRefsites = false;

                    }).fail( function() {
                        self.trigger( 'query:fail' );
                    });
                }

                if ( query.refsites.length === 0 ) {
                    self.trigger( 'query:empty' );
                } else {
                    $( 'body' ).removeClass( 'no-results' );
                }

                // Only trigger an update event since we already have the refsites
                // on our cached object
                if ( _.isNumber( query.total ) ) {
                    this.count = query.total;
                }

                this.reset( query.refsites );
                if ( ! query.total ) {
                    this.count = this.length;
                }

                this.trigger( 'update' );
                this.trigger( 'query:success', this.count );
            }
        },

        // Local cache array for API queries
        queries: [],

        // Keep track of current query so we can handle pagination
        currentQuery: {
            page: 1,
            request: {}
        },

        // Send request to api.wordpress.org/refsites
        apiCall: function( request, paginated ) {
            return wp.ajax.send( 'query-refsites', {
                data: {
                    // Request data
                    request: _.extend({
                        per_page: 100,
                        fields: {
                            description: true,
                            tested: true,
                            requires: true,
                            rating: true,
                            downloaded: true,
                            downloadLink: true,
                            last_updated: true,
                            homepage: true,
                            num_ratings: true
                        }
                    }, request)
                },

                beforeSend: function() {
                    if ( ! paginated ) {
                        // Spin it
                        $( 'body' ).addClass( 'loading-content' ).removeClass( 'no-results' );
                    }
                }
            });
        },

        // Static status controller for when we are loading refsites.
        loadingRefsites: false
    });

    // This is the view that controls each refsite item
    // that will be displayed on the screen
    refsites.view.Refsite = wp.Backbone.View.extend({

        // Wrap refsite data on a div.refsite element
        className: 'refsite',

        // Reflects which refsite view we have
        // 'grid' (default) or 'detail'
        state: 'grid',

        // The HTML template for each element to be rendered
        html: refsites.template( 'refsite' ),

        events: {
            'click': refsites.isInstall ? 'preview': 'expand',
            'keydown': refsites.isInstall ? 'preview': 'expand',
            'touchend': refsites.isInstall ? 'preview': 'expand',
            'keyup': 'addFocus',
            'touchmove': 'preventExpand'
        },

        touchDrag: false,

        render: function() {
            var data = this.model.toJSON();
            // Render refsites using the html template
            this.$el.html( this.html( data ) ).attr({
                tabindex: 0,
                'aria-describedby' : data.id + '-action ' + data.id + '-name'
            });

            // Renders active refsite styles
            this.activeRefsite();

            if ( this.model.get( 'displayAuthor' ) ) {
                this.$el.addClass( 'display-author' );
            }

            if ( this.model.get( 'installed' ) ) {
                this.$el.addClass( 'is-installed' );
            }
        },

        // Adds a class to the currently active refsite
        // and to the overlay in detailed view mode
        activeRefsite: function() {
            if ( this.model.get( 'active' ) ) {
                this.$el.addClass( 'active' );
            }
        },

        // Add class of focus to the refsite we are focused on.
        addFocus: function() {
            var $refsiteToFocus = ( $( ':focus' ).hasClass( 'refsite' ) ) ? $( ':focus' ) : $(':focus').parents('.refsite');

            $('.refsite.focus').removeClass('focus');
            $refsiteToFocus.addClass('focus');
        },

        // Single refsite overlay screen
        // It's shown when clicking a refsite
        expand: function( event ) {
            var self = this;

            event = event || window.event;

            // 'enter' and 'space' keys expand the details view when a refsite is :focused
            if ( event.type === 'keydown' && ( event.which !== 13 && event.which !== 32 ) ) {
                return;
            }

            // Bail if the user scrolled on a touch device
            if ( this.touchDrag === true ) {
                return this.touchDrag = false;
            }

            // Prevent the modal from showing when the user clicks
            // one of the direct action buttons
            if ( $( event.target ).is( '.refsite-actions a' ) ) {
                return;
            }
            
            // Set focused refsite to current element
            refsites.focusedRefsite = this.$el;
            
            this.trigger( 'refsite:expand', self.model.cid );
        },

        preventExpand: function() {
            this.touchDrag = true;
        },

        preview: function( event ) {
            var self = this,
                current, preview;

            // Bail if the user scrolled on a touch device
            if ( this.touchDrag === true ) {
                return this.touchDrag = false;
            }

            // Allow direct link path to installing a refsite.
            if ( $( event.target ).hasClass( 'button-primary' ) ) {
                return;
            }

            // 'enter' and 'space' keys expand the details view when a refsite is :focused
            if ( event.type === 'keydown' && ( event.which !== 13 && event.which !== 32 ) ) {
                return;
            }

            // pressing enter while focused on the buttons shouldn't open the preview
            if ( event.type === 'keydown' && event.which !== 13 && $( ':focus' ).hasClass( 'button' ) ) {
                return;
            }

            event.preventDefault();

            event = event || window.event;

            // Set focus to current refsite.
            refsites.focusedRefsite = this.$el;

            // Construct a new Preview view.
            preview = new refsites.view.Preview({
                model: this.model
            });

            // Render the view and append it.
            preview.render();
            this.setNavButtonsState();

            // Hide previous/next navigation if there is only one refsite
            if ( this.model.collection.length === 1 ) {
                preview.$el.addClass( 'no-navigation' );
            } else {
                preview.$el.removeClass( 'no-navigation' );
            }

            // Append preview
            $( 'div.wrap' ).append( preview.el );

            // Listen to our preview object
            // for `refsite:next` and `refsite:previous` events.
            this.listenTo( preview, 'refsite:next', function() {

                // Keep local track of current refsite model.
                current = self.model;

                // If we have ventured away from current model update the current model position.
                if ( ! _.isUndefined( self.current ) ) {
                    current = self.current;
                }

                // Get next refsite model.
                self.current = self.model.collection.at( self.model.collection.indexOf( current ) + 1 );

                // If we have no more refsites, bail.
                if ( _.isUndefined( self.current ) ) {
                    self.options.parent.parent.trigger( 'refsite:end' );
                    return self.current = current;
                }

                preview.model = self.current;

                // Render and append.
                preview.render();
                this.setNavButtonsState();
                $( '.next-refsite' ).focus();
            })
                .listenTo( preview, 'refsite:previous', function() {

                    // Keep track of current refsite model.
                    current = self.model;

                    // Bail early if we are at the beginning of the collection
                    if ( self.model.collection.indexOf( self.current ) === 0 ) {
                        return;
                    }

                    // If we have ventured away from current model update the current model position.
                    if ( ! _.isUndefined( self.current ) ) {
                        current = self.current;
                    }

                    // Get previous refsite model.
                    self.current = self.model.collection.at( self.model.collection.indexOf( current ) - 1 );

                    // If we have no more refsites, bail.
                    if ( _.isUndefined( self.current ) ) {
                        return;
                    }

                    preview.model = self.current;

                    // Render and append.
                    preview.render();
                    this.setNavButtonsState();
                    $( '.previous-refsite' ).focus();
                });

            this.listenTo( preview, 'preview:close', function() {
                self.current = self.model;
            });
        },

        // Handles .disabled classes for previous/next buttons in refsite installer preview
        setNavButtonsState: function() {
            var $refsiteInstaller = $( '.refsite-install-overlay' ),
                current = _.isUndefined( this.current ) ? this.model : this.current;

            // Disable previous at the zero position
            if ( 0 === this.model.collection.indexOf( current ) ) {
                $refsiteInstaller.find( '.previous-refsite' ).addClass( 'disabled' );
            }

            // Disable next if the next model is undefined
            if ( _.isUndefined( this.model.collection.at( this.model.collection.indexOf( current ) + 1 ) ) ) {
                $refsiteInstaller.find( '.next-refsite' ).addClass( 'disabled' );
            }
        }
    });

    // Refsite Details view
    // Set ups a modal overlay with the expanded refsite data
    refsites.view.Details = wp.Backbone.View.extend({

        // Wrap refsite data on a div.refsite element
        className: 'refsite-overlay',

        events: {
            'click': 'collapse',
            'click .left': 'previousRefsite',
            'click .right': 'nextRefsite',
            'click .refsite-version': 'setRefsiteVersion',
            'click .refsite-actions .wpvdemo-download': 'installRefsite'
        },

        // The HTML template for the refsite overlay
        html: refsites.template( 'refsite-single' ),

        render: function() {
            var data = this.model.toJSON();
            this.$el.html( this.html( data ) );
            // Renders active refsite styles
            this.activeRefsite();
            // Set up navigation events
            this.navigation();
            // Checks screenshot size
            this.screenshotCheck( this.$el );
            // Contain "tabbing" inside the overlay
            this.containFocus( this.$el );

			if( $( 'body.mobile' ).length ) {
				window.scrollTo( 0, 0 );
			}
        },

        // Adds a class to the currently active refsite
        // and to the overlay in detailed view mode
        activeRefsite: function() {
            // Check the model has the active property
            this.$el.toggleClass( 'active', this.model.get( 'active' ) );
        },

        // Keeps :focus within the refsite details elements
        containFocus: function( $el ) {
            var $target;

            // Move focus to the primary action
            _.delay( function() {
                $( '.refsite-wrap a.button-primary:visible' ).focus();
            }, 500 );

            $el.on( 'keydown.wp-refsites', function( event ) {

                // Tab key
                if ( event.which === 9 ) {
                    $target = $( event.target );

                    // Keep focus within the overlay by making the last link on refsite actions
                    // switch focus to button.left on tabbing and vice versa
                    if ( $target.is( 'button.left' ) && event.shiftKey ) {
                        $el.find( '.refsite-actions a:last-child' ).focus();
                        event.preventDefault();
                    } else if ( $target.is( '.refsite-actions a:last-child' ) ) {
                        $el.find( 'button.left' ).focus();
                        event.preventDefault();
                    }
                }
            });    
        },

        // Single refsite overlay screen
        // It's shown when clicking a refsite
        collapse: function( event ) {
            var self = this,
                scroll;

            event = event || window.event;

            // Prevent collapsing detailed view when there is only one refsite available
            if ( refsites.data.refsites.length === 1 ) {
                return;
            }

            // Detect if the click is inside the overlay
            // and don't close it unless the target was
            // the div.back button
            if ( $( event.target ).is( '.refsite-backdrop' ) || $( event.target ).is( '.close' ) || event.keyCode === 27 ) {

                // Add a temporary closing class while overlay fades out
                $( 'body' ).addClass( 'closing-overlay' );

                // With a quick fade out animation
                this.$el.fadeOut( 130, function() {
                    // Clicking outside the modal box closes the overlay
                    $( 'body' ).removeClass( 'closing-overlay' );
                    // Handle event cleanup
                    self.closeOverlay();

                    // Get scroll position to avoid jumping to the top
                    scroll = document.body.scrollTop;

                    // Clean the url structure
                    refsites.router.navigate( refsites.router.baseUrl( '' ) );

                    // Restore scroll position
                    document.body.scrollTop = scroll;

                    // Return focus to the refsite div
                    if ( refsites.focusedRefsite ) {
                        refsites.focusedRefsite.focus();
                    }
                });
            }
        },

        // Handles .disabled classes for next/previous buttons
        navigation: function() {

            // Disable Left/Right when at the start or end of the collection
            if ( this.model.cid === this.model.collection.at(0).cid ) {
                this.$el.find( '.left' ).addClass( 'disabled' );
            }
            if ( this.model.cid === this.model.collection.at( this.model.collection.length - 1 ).cid ) {
                this.$el.find( '.right' ).addClass( 'disabled' );
            }
        },

        // Performs the actions to effectively close
        // the refsite details overlay
        closeOverlay: function() {
            $( 'body' ).removeClass( 'modal-open' );
            this.remove();
            this.unbind();
            this.trigger( 'refsite:collapse' );
        },

        nextRefsite: function() {
            var self = this;
            self.trigger( 'refsite:next', self.model.cid );
            return false;
        },

        previousRefsite: function() {
            var self = this;
            self.trigger( 'refsite:previous', self.model.cid );
            return false;
        },

        setRefsiteVersion: function( event ) {
            var self = this;          
            var $version = $(event.target);
            $('.refsite-plugins .refsite-version-plugins').hide();
            
            $('.inactive-refsite .wpmlversion').hide();
            $('.inactive-refsite .nowpmlversion').show();
            $('.refsite-actions .wpmlversion_div_msg').hide();
            
            if ( $version.is(':checked') ) {
                $('.refsite-plugins .refsite-version-plugins-' + $version.val()).fadeIn();
            }
            
            var loaded_version =$version.val();            
            
            if ('wpml' == loaded_version) {
            	$('.inactive-refsite .wpmlversion').show();
            	$('.inactive-refsite .nowpmlversion').hide();
            	$('.refsite-actions .wpmlversion_div_msg').show();
            }
            
            return true;
        },

        installRefsite: function( event ) {
            var self = this;
            var $this = $(event.target);
            if ($this.is(':disabled')) {
                return false;
            }
            var refsite_id = $this.attr('href') || 0;                       
            var version = 'nowpml';
            if ($('.refsite-versions input[name="version"]:checked', self.$el).length) {
                version = $('.refsite-versions input[name="version"]:checked', self.$el).val();
            } else if ($('input[name="version"]', self.$el).length) {
                version = $('input[name="version"]', self.$el).val();
            }    
            //alert('installing "' + this.model.attributes.title + '" [' + refsite_id + '] - version: ' + version);
            var answer = confirm(wpvdemo_confirm_download_txt);
            if (answer) {
            	
                $this.attr('disabled', 'disabled');                
               
                var the_nonce_import_text_data= _refsitesSettings.refsite_import_text_nonce; 
                
                /** Framework Installer 1.8.2: Customize download process steps based on the refsite being downloaded */
                /** START */
        		var import_text_custom = {
        				action: 'refsite_custom_import_process_steps',
        				the_refsite_id : refsite_id,
        				language_settings_passed: version,
        				the_nonce_import_text :  the_nonce_import_text_data
        		};
        		
        		
        	 	$.post(ajaxurl, import_text_custom, function(response) {
        	 		
        	 		var myObjx = $.parseJSON(response);
        	 		var success_query= myObjx.outputtext;
        	 		
        	 		if ('success' == success_query) {	        	 		
        	 			
        	 			wpvdemo_download_step_one_txt=myObjx.importing_text_updated;        	 			
        	 			
	                    //Show installation progress                          
	                    $('#refsite-install-progress-' + refsite_id).show();
	                   
	                    //Define site title                 
	                    var h2_title = wpvdemo_lets_retrieved_the_site_title(refsite_id);
	                    
	                    $('h2.imported_site_title').text(h2_title);
	                    
	                    //Let's disable some buttons so it can't distract
	                    $('.refsite-header .left').prop('disabled', true);
	                    $('.refsite-header .right').prop('disabled', true);                
	                    	
	                    //Let's make the Install button completely unclickable when installation is on the way.
	                    $('.refsite-actions a.wpvdemo-download').removeAttr('href');                
	                    $('.refsite-actions .wpvdemo-download').removeClass('wpvdemo-download');                
	                    
	                    //Let's install	                    
	                    refsiteInstallStep(refsite_id, version, 1);
        	 		}
        	 	});
        	 	
        	 	/** END */        		

            }
            return false;
        },
        // Checks if the refsite screenshot is the old 300px width version
        // and adds a corresponding class if it's true
        screenshotCheck: function( el ) {
            var screenshot, image;

            screenshot = el.find( '.screenshot img' );
            image = new Image();
            image.src = screenshot.attr( 'src' );

            // Width check
            if ( image.width && image.width <= 300 ) {
                el.addClass( 'small-screenshot' );
            }
        }
    });

    // refsite Preview view
    // Set ups a modal overlay with the expanded refsite data
    refsites.view.Preview = refsites.view.Details.extend({

        className: 'wp-full-overlay expanded',
        el: '.refsite-install-overlay',

        events: {
            'click .close-full-overlay': 'close',
            'click .collapse-sidebar': 'collapse',
            'click .previous-refsite': 'previousRefsite',
            'click .next-refsite': 'nextRefsite',
            'keyup': 'keyEvent'
        },

        // The HTML template for the refsite preview
        html: refsites.template( 'refsite-preview' ),

        render: function() {
            var data = this.model.toJSON();

            this.$el.html( this.html( data ) );

            refsites.router.navigate( refsites.router.baseUrl( refsites.router.refsitePath + this.model.get( 'id' ) ), { replace: true } );

            this.$el.fadeIn( 200, function() {
                $( 'body' ).addClass( 'refsite-installer-active full-overlay-active' );
                $( '.close-full-overlay' ).focus();
            });
        },

        close: function() {
            this.$el.fadeOut( 200, function() {
                $( 'body' ).removeClass( 'refsite-installer-active full-overlay-active' );

                // Return focus to the refsite div
                if ( refsites.focusedRefsite ) {
                    refsites.focusedRefsite.focus();
                }
            });

            refsites.router.navigate( refsites.router.baseUrl( '' ) );
            this.trigger( 'preview:close' );
            this.undelegateEvents();
            this.unbind();
            return false;
        },

        collapse: function() {

            this.$el.toggleClass( 'collapsed' ).toggleClass( 'expanded' );
            return false;
        },

        keyEvent: function( event ) {
            // The escape key closes the preview
            if ( event.keyCode === 27 ) {
                this.undelegateEvents();
                this.close();
            }
            // The right arrow key, next refsite
            if ( event.keyCode === 39 ) {
                _.once( this.nextRefsite() );
            }

            // The left arrow key, previous refsite
            if ( event.keyCode === 37 ) {
                this.previousRefsite();
            }
        }
    });

    // Controls the rendering of div.refsites,
    // a wrapper that will hold all the refsite elements
    refsites.view.Refsites = wp.Backbone.View.extend({

        className: 'refsites',
        $overlay: $( 'div.refsite-overlay' ),

        // Number to keep track of scroll position
        // while in refsite-overlay mode
        index: 0,

        // The refsite count element
        count: $( '.wp-filter .refsite-count' ),

        initialize: function( options ) {
            var self = this;

            // Set up parent
            this.parent = options.parent;

            // Set current view to [grid]
            this.setView( 'grid' );

            // Move the active refsite to the beginning of the collection
            self.currentRefsite();

            // When the collection is updated by user input...
            this.listenTo( self.collection, 'update', function() {
                self.parent.page = 0;
                self.currentRefsite();
                self.render( this );
            });

            // Update refsite count to full result set when available.
            this.listenTo( self.collection, 'query:success', function( count ) {
                if ( _.isNumber( count ) ) {
                    self.count.text( count );
                } else {
                    self.count.text( self.collection.length );
                }
            });

            this.listenTo( self.collection, 'query:empty', function() {
                $( 'body' ).addClass( 'no-results' );
            });

            this.listenTo( this.parent, 'refsite:scroll', function() {
                self.renderRefsites( self.parent.page );
            });

            this.listenTo( this.parent, 'refsite:close', function() {
                if ( self.overlay ) {
                    self.overlay.closeOverlay();
                }
            } );

            // Bind keyboard events.
            $( 'body' ).on( 'keyup', function( event ) {
                if ( ! self.overlay ) {
                    return;
                }

                // Pressing the right arrow key fires a refsite:next event
                if ( event.keyCode === 39 ) {
                    self.overlay.nextRefsite();
                }

                // Pressing the left arrow key fires a refsite:previous event
                if ( event.keyCode === 37 ) {
                    self.overlay.previousRefsite();
                }

                // Pressing the escape key fires a refsite:collapse event
                if ( event.keyCode === 27 ) {
                    self.overlay.collapse( event );
                }
            });
        },

        // Manages rendering of refsite pages
        // and keeping refsite count in sync
        render: function() {
            // Clear the DOM, please
            this.$el.html( '' );

            // If the user doesn't have switch capabilities
            // or there is only one refsite in the collection
            // render the detailed view of the active refsite
            if ( refsites.data.refsites.length === 1 ) {

                // Constructs the view
                this.singleRefsite = new refsites.view.Details({
                    model: this.collection.models[0]
                });

                // Render and apply a 'single-refsite' class to our container
                this.singleRefsite.render();
                this.$el.addClass( 'single-refsite' );
                this.$el.append( this.singleRefsite.el );
            }

            // Generate the refsites
            // Using page instance
            // While checking the collection has items
            if ( this.options.collection.size() > 0 ) {
                this.renderRefsites( this.parent.page );
            }

            // Display a live refsite count for the collection
            this.count.text( this.collection.count ? this.collection.count : this.collection.length );
        },

        // Iterates through each instance of the collection
        // and renders each refsite module
        renderRefsites: function( page ) {
            var self = this;

            self.instance = self.collection.paginate( page );

            // If we have no more refsites bail
            if ( self.instance.size() === 0 ) {
                // Fire a no-more-refsites event.
                this.parent.trigger( 'refsite:end' );
                return;
            }

            // Make sure the add-new stays at the end
            if ( page >= 1 ) {
                $( '.add-new-refsite' ).remove();
            }

            // Loop through the refsites and setup each refsite view
            self.instance.each( function( refsite ) {
                self.refsite = new refsites.view.Refsite({
                    model: refsite,
                    parent: self
                });

                // Render the views...
                self.refsite.render();
                // and append them to div.refsites
                self.$el.append( self.refsite.el );

                // Binds to refsite:expand to show the modal box
                // with the refsite details
                self.listenTo( self.refsite, 'refsite:expand', self.expand, self );
            });

            this.parent.page++;
        },

        // Grabs current refsite and puts it at the beginning of the collection
        currentRefsite: function() {
            var self = this,
                current;

            current = self.collection.findWhere({ active: true });

            // Move the active refsite to the beginning of the collection
            if ( current ) {
                self.collection.remove( current );
                self.collection.add( current, { at:0 } );
            }
        },

        // Sets current view
        setView: function( view ) {
            return view;
        },

        // Renders the overlay with the RefsiteDetails view
        // Uses the current model data
        expand: function( id ) {
            var self = this;

            // Set the current refsite model
            this.model = self.collection.get( id );

            // Trigger a route update for the current model
            refsites.router.navigate( refsites.router.baseUrl( refsites.router.refsitePath + this.model.id ) );

            // Sets this.view to 'detail'
            this.setView( 'detail' );
            $( 'body' ).addClass( 'modal-open' );

            // Set up the refsite details view
            this.overlay = new refsites.view.Details({
                model: self.model
            });

            this.overlay.render();
            this.$overlay.html( this.overlay.el );

            // Bind to refsite:next and refsite:previous
            // triggered by the arrow keys
            //
            // Keep track of the current model so we
            // can infer an index position
            this.listenTo( this.overlay, 'refsite:next', function() {
                // Renders the next refsite on the overlay
                self.next( [ self.model.cid ] );

            })
                .listenTo( this.overlay, 'refsite:previous', function() {
                    // Renders the previous refsite on the overlay
                    self.previous( [ self.model.cid ] );
                });

            // Set version configuration
            if ($('.refsite-versions input[name="version"]:checked').length) {
                $('.refsite-versions input[name="version"]:checked').click();
            } else {
                $('.refsite-plugins .refsite-version-plugins').first().fadeIn();
            }
        },

        // This method renders the next refsite on the overlay modal
        // based on the current position in the collection
        // @params [model cid]
        next: function( args ) {
            var self = this,
                model, nextModel;

            // Get the current refsite
            model = self.collection.get( args[0] );
            // Find the next model within the collection
            nextModel = self.collection.at( self.collection.indexOf( model ) + 1 );

            // Sanity check which also serves as a boundary test
            if ( nextModel !== undefined ) {

                // We have a new refsite...
                // Close the overlay
                this.overlay.closeOverlay();

                // Trigger a route update for the current model
                self.refsite.trigger( 'refsite:expand', nextModel.cid );

            }
        },

        // This method renders the previous refsite on the overlay modal
        // based on the current position in the collection
        // @params [model cid]
        previous: function( args ) {
            var self = this,
                model, previousModel;

            // Get the current refsite
            model = self.collection.get( args[0] );
            // Find the previous model within the collection
            previousModel = self.collection.at( self.collection.indexOf( model ) - 1 );

            if ( previousModel !== undefined ) {

                // We have a new refsite...
                // Close the overlay
                this.overlay.closeOverlay();

                // Trigger a route update for the current model
                self.refsite.trigger( 'refsite:expand', previousModel.cid );

            }
        }
    });

    // Search input view controller.
    refsites.view.Search = wp.Backbone.View.extend({

        tagName: 'input',
        className: 'wp-filter-search',
        id: 'wp-filter-search-input',
        searching: false,

        attributes: {
            placeholder: l10n.searchPlaceholder,
            type: 'search'
        },

        events: {
            'input':  'search',
            'keyup':  'search',
            'change': 'search',
            'search': 'search',
            'blur':   'pushState'
        },

        initialize: function( options ) {

            this.parent = options.parent;

            this.listenTo( this.parent, 'refsite:close', function() {
                this.searching = false;
            } );

        },

        // Runs a search on the refsite collection.
        search: function( event ) {
            var options = {};

            // Clear on escape.
            if ( event.type === 'keyup' && event.which === 27 ) {
                event.target.value = '';
            }

            // Lose input focus when pressing enter
            if ( event.which === 13 ) {
                this.$el.trigger( 'blur' );
            }

            this.collection.doSearch( event.target.value );

            // if search is initiated and key is not return
            if ( this.searching && event.which !== 13 ) {
                options.replace = true;
            } else {
                this.searching = true;
            }

            // Update the URL hash
            if ( event.target.value ) {
                refsites.router.navigate( refsites.router.baseUrl( refsites.router.searchPath + event.target.value ), options );
            } else {
                refsites.router.navigate( refsites.router.baseUrl( '' ) );
            }
        },

        pushState: function( event ) {
            var url = refsites.router.baseUrl( '' );

            if ( event.target.value ) {
                url = refsites.router.baseUrl( refsites.router.searchPath + event.target.value );
            }

            this.searching = false;
            refsites.router.navigate( url );

        }
    });

    // Sets up the routes events for relevant url queries
    // Listens to [refsite] and [search] params
    refsites.Router = Backbone.Router.extend({

        routes: {
            'admin.php?page=manage-refsites&refsite=:slug': 'refsite',
            'admin.php?page=manage-refsites&search=:query': 'search',
            'admin.php?page=manage-refsites&s=:query': 'search',
            'admin.php?page=manage-refsites': 'refsites',
            '': 'refsites'
        },

        baseUrl: function( url ) {
            if ( '' == url ) {
                url = this.basePath;
            }
            return 'admin.php' + url;
        },

        basePath: '?page=manage-refsites',
        refsitePath: '?page=manage-refsites&refsite=',
        searchPath: '?page=manage-refsites&search=',

        search: function( query ) {
            $( '.wp-filter-search' ).val( query );
        },

        refsites: function() {
            $( '.wp-filter-search' ).val( '' );
        },

        navigate: function() {
            if ( Backbone.history._hasPushState ) {
                Backbone.Router.prototype.navigate.apply( this, arguments );
            }
        }

    });

    // Execute and setup the application
    refsites.Run = {
        init: function() {
            // Initializes the blog's refsite library view
            // Create a new collection with data
            this.refsites = new refsites.Collection( refsites.data.refsites );

            // Set up the view
            this.view = new refsites.view.Appearance({
                collection: this.refsites
            });

            this.render();
        },

        render: function() {

            // Render results
            this.view.render();
            this.routes();

            Backbone.history.start({
                root: refsites.data.settings.adminUrl,
                pushState: true,
                hashChange: false
            });
        },

        routes: function() {
            var self = this;
            // Bind to our global thx object
            // so that the object is available to sub-views
            refsites.router = new refsites.Router();

            // Handles refsite details route event
            refsites.router.on( 'route:refsite', function( slug ) {

                self.view.view.expand( slug );
            });

            refsites.router.on( 'route:refsites', function() {
                self.refsites.doSearch( '' );
                self.view.trigger( 'refsite:close' );
            });

            // Handles search route event
            refsites.router.on( 'route:search', function() {
                $( '.wp-filter-search' ).trigger( 'keyup' );
            });

            this.extraRoutes();
        },

        extraRoutes: function() {
            return false;
        }
    };

    refsites.Run.init();

    function refsiteInstallStep(refsite_id, version, step) { 	
        var updateDiv = $('#wpvdemo-download-response-'+refsite_id);       
        refsite_install_current_site_id = refsite_id;        
        setInterval(function() {        	
            if (refsite_install_current_step == 2) {
                if (refsite_install_check_num_posts == false) {                	
                    return false;
                }
                var updateDiv = $('#wpvdemo-download-response-'+refsite_install_current_site_id);                
                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: 'action=wpvdemo_post_count&_wpnonce='+wpvdemo_nonce,
                    cache: false,
                    beforeSend: function () {
                        refsite_install_check_num_posts = false;
                    },
                    success: function (data) {

                        // check for string length. This is to avoid a bug where WP is creating cron errors
                        // https://icanlocalize.basecamphq.com/projects/7393061-wp-views/todo_items/147303129/comments

                        if (data.length < 12) {
                            updateDiv.find('#' + refsite_install_step_id_prefix + refsite_install_current_step).find('.wpvdemo-post-count').show();
                            updateDiv.find('#' + refsite_install_step_id_prefix + refsite_install_current_step).find('.wpvdemo-post-count').html(data);
                            refsite_install_check_num_posts = true;
                        }
                    }
                });
            }
        }, 2000);

        //Auto-refresh manage sites screen
        $(document).ajaxSuccess(function(event,xhr,options){
            var responseText_string= xhr.responseText;
            if ( responseText_string.indexOf('Operation complete')>= 0) {
                //Operation completed, refresh
                location.reload();
            }
            if ( responseText_string.indexOf('reference site was successfully imported')>= 0) {                
               $('.refsite-header button.close').addClass('importdone');
            }          
        });
        
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: 'action=refsite_install&_wpnonce='+wpvdemo_nonce+'&site_id='+refsite_id+'&version='+version+'&step='+step,
            cache: false,
            beforeSend: function() {
                if (step == 1) {
                    updateDiv.append(wpvdemo_download_step_one_txt);
                }                
                for (var i = 1; i <= 10; i++) {
                    if (i == step) {
                        updateDiv.find('#' + refsite_install_step_id_prefix + i).css('font-weight', 'bold');
                        updateDiv.find('#' + refsite_install_step_id_prefix + i).find('.wpcf-ajax-loading-small').show();
                    } else {
                        updateDiv.find('#' + refsite_install_step_id_prefix + i).css('font-weight', 'normal');
                        updateDiv.find('#' + refsite_install_step_id_prefix + i).find('.wpcf-ajax-loading-small').hide();
                    }
                }

                refsite_install_current_step = step;
            },
            success: function(data) {   
                updateDiv.find('#' + refsite_install_step_id_prefix + step).css('font-weight', 'normal');
                updateDiv.find('#' + refsite_install_step_id_prefix + step).find('.wpvdemo-green-check').show();
                updateDiv.find('#' + refsite_install_step_id_prefix + step).find('.wpcf-ajax-loading-small').hide();

                updateDiv.append(data);

                if (step == 2) {
                    updateDiv.find('#' + refsite_install_step_id_prefix + step).find('.wpvdemo-post-count').hide();
                }                
            }
        });
    }
    
    /** Retrieved the site title */
    function wpvdemo_lets_retrieved_the_site_title(refsite_id) {
    	
        var sites_object= _refsitesSettings.refsites_unfiltered;           
        var site_details= wpvdemo_findObjectByAttribute(sites_object,'ID',refsite_id);     
        var site_title_loaded= site_details.title;              
        var h2_title="We're setting up your new "+site_title_loaded+" test site";   	
    	
        return h2_title;   	
    }
    
    /** Search reference sites object values given an attribute */
    function wpvdemo_findObjectByAttribute(items, attribute, value) {
    	  for (var i = 0; i < items.length; i++) {
    	    if (items[i][attribute] === value) {    	      
    	      return items[i];
    	    }
    	  }
    	  return null;
    }
    
    /** Handler for Discover-WP multisite install */
    if (window.location.hash) {
        if (window.location.hash.indexOf('installsite') == 1) {     
        	//Retrieve full hash
        	var fullhash = window.location.hash;        	
        	var parts = fullhash.split('_');
        	var origin = parts[parts.length - 1]; 
        	var version = parts[parts.length - 2];       	
        	var site_id = parts[parts.length - 3];   
        	var target_verification = _refsitesSettings.target_verification;
        	var site_screen= _refsitesSettings.refsites_master_screen;  

        	if ((origin) && (target_verification)) {
        		
        		var the_origin= $.trim(origin);
        		var the_target_verification=  $.trim(target_verification);        		
        		if (the_target_verification == the_origin) {

                    /** Framework Installer 1.8.2: Customize download process steps based on the refsite being downloaded */
                    /** START */
        			var the_nonce_import_text_data_discover= _refsitesSettings.refsite_import_text_nonce;
            		var import_text_custom_discover = {
            				action: 'refsite_custom_import_process_steps',
            				the_refsite_id : site_id,
            				language_settings_passed: version,
            				the_nonce_import_text :  the_nonce_import_text_data_discover
            		};            		
            		
            	 	$.post(ajaxurl, import_text_custom_discover, function(response) {
            	 		
            	 		var myObjx = $.parseJSON(response);
            	 		var success_query= myObjx.outputtext;
            	 		
            	 		if ('success' == success_query) {	        	 		
            	 			
            	 			wpvdemo_download_step_one_txt=myObjx.importing_text_updated;        	 			
            	 			
                        	//Fire the install method        	
                            $('#refsite-install-progress-'+ site_id).show();
                            
                            //Let's show site title in import steps as heading
                            var h2_title= wpvdemo_lets_retrieved_the_site_title(site_id);
                            $('h2.imported_site_title').text(h2_title);
                            
                            //Let's disable some buttons so it can't distract
                            $('.refsite-header .left').prop('disabled', true);
                            $('.refsite-header .right').prop('disabled', true);                
                            	
                            //Let's make the Install button completely unclickable when installation is on the way.
                            $('.refsite-actions a.wpvdemo-create-site').attr('disabled', 'disabled');
                            $('.refsite-actions a.wpvdemo-create-site').removeAttr('href'); 
                            $('.refsite-actions a.wpvdemo-create-site').removeAttr('onclick'); 
                            $('.refsite-actions .wpvdemo-create-site').removeClass('thickbox');                 
                            refsiteInstallStep(site_id, version, 1); 
            	 		}
            	 	});
            	 	
            	 	/** END */        			

        		} else {
        			//Redirect to reference sites screen
        			window.location =	site_screen;  
        		}
        	} else {
    			//Redirect to reference sites screen
    			window.location =	site_screen;        		
        	}
            
        }        
    }
    
    /** Auto-refresh the reference sites admin screen after import to load updated contents */
	$(document).on('click','button.importdone',(function() {	
		var site_screen= _refsitesSettings.refsites_master_screen;  
		window.location =	site_screen;   
	}));
	
	/** Merged Driver */

	$(document).on('change','.refsite-versions input:radio[name="site_version"]',function(){

		if ($(this).is(':checked')) {
			
			//Install button href attribute
			var selected_site_version=$(this).val();

	        /** Refsite screenshot merged div driver */			
			var refsite_screenshot_selector= '.refsite-about #refsite-screenshot-'+selected_site_version;
			$('.refsite-about .refsite-screenshot').hide();
			$(refsite_screenshot_selector).show();	

	        /** Refsite info merged div driver */			
			var refsite_info_selector= '.refsite-about #refsite-info-'+selected_site_version;
			$('.refsite-about .refsite-info').hide();
			$(refsite_info_selector).show();

			/** Refsite merged plugins driver */
			
			var merged_plugin_selector= '.refsite-about #merge-plugin-'+selected_site_version;
			$('.refsite-about .refsite-plugins').hide();
			$(merged_plugin_selector).show(); 			
			
	        /** Refsite actions merged div driver */
			var refsite_selector= '.refsite-wrap #refsite-actions-'+selected_site_version;
			$('.refsite-wrap .refsite-actions').hide();
			$(refsite_selector).show();	        
		}
	});	
	
	$(document).on('change','.refsite-versions input:radio[name="version"]',function(){

		if ($(this).is(':checked')) {
			
			//Step1, Let's determine what language version is selected
			var selected_lang_version=$(this).val();
			
			//Step2, Let's determine the site version
			//Some sites does not have merged driver, we need to check if this selector exists
			var the_site_version_selector= '.refsite-versions input:radio[name="site_version"]';
			if ($(the_site_version_selector).length ) {
			    //Merged presentation
				var selected_site_version= $('.refsite-versions input:radio[name="site_version"]:checked').val();	
			} else {
				//Unmerged presentation
				var refsite_info_id_attribute= $('.refsite-info').attr('id');
				var selected_site_version = refsite_info_id_attribute.replace( /^\D+/g, ''); 				
			}

			//Step3, formulate ID selector for Discover create site button
			var correct_selector= '#wpvdemo-create-site-button-'+selected_site_version;
			var onclick_selector= '.refsite-actions '+correct_selector;
			
			//Step4, Let's retrieved the existing onclick handler
			var create_site_links_js= $(onclick_selector).attr('onclick');

			//Step5, let's formulate the correct replacement			
			var selected_lang_ver_js= "jQuery('#site_lang_version').val('" + selected_lang_version + "');";
			
			//Step6, let's removed the original lang version parameter			
			if (typeof create_site_links_js != 'undefined') {
				create_site_links_js= create_site_links_js.replace("jQuery('#site_lang_version').val('nowpml');","");
				create_site_links_js= create_site_links_js.replace("jQuery('#site_lang_version').val('wpml');","");			
				
				//Step7, let's add back
				var new_js_links= create_site_links_js+selected_lang_ver_js;
				
				$(onclick_selector).attr('onclick','');
				$(onclick_selector).attr('onclick',new_js_links);
			}
			
		}
	});	
});
