<?php
if ( ! class_exists( 'Toolset_Classifieds_MessageSystem' ) ) {
	require_once TOOLSET_EXT_CLASSIFIEDS_PLUGIN_PATH . '/inc/toolset-classifieds.class.php';

	/**
	 * Class Toolset_Classifieds_MessageSystem
	 * messaging system specific functions
	 */
	class Toolset_Classifieds_MessageSystem extends Toolset_Classifieds {
		function __construct() {
			
			add_action( 'init', array( $this, 'classifieds_process_guest_login' ) );

			add_shortcode( 'classifieds-message-userdata', array( $this, 'classifieds_get_message_userdata' ) );
			add_shortcode( 'classifieds-message-data', array( $this, 'classifieds_get_message_data' ) );

			add_action( 'cred_save_data_form_new-message', array(
				$this,
				'classifieds_cred_save_data_new_message_function'
			), 10, 2 );
			add_action( 'cred_submit_complete_form_new-message', array(
				$this,
				'classifieds_cred_submit_complete_new_message'
			), 10, 2 );
			add_action( 'cred_save_data_form_reply-message', array(
				$this,
				'classifieds_cred_save_data_reply_message_function'
			), 10, 2 );
			add_action( 'cred_submit_complete_form_reply-message', array(
				$this,
				'classifieds_cred_submit_complete_reply_message'
			), 10, 2 );

			add_filter( 'wpv_filter_wpv_view_shortcode_output', array( $this, 'prefix_clean_view_output' ), 5, 2 );
		}

		/**
		 * @param $post_id
		 * @param $form_data
		 */
		public function classifieds_cred_save_data_new_message_function( $post_id, $form_data ) {
			/* check if we have a listing-id so we can associate the message to it */
			if ( ! empty( $_GET['listing-id'] ) ) {
				$listing_id   = intval( $_GET['listing-id'] );
				$listing_post = get_post( $listing_id );
				//get the author of the post
				$advertiser_id   = $listing_post->post_author;
				$advertiser_data = get_userdata( $advertiser_id );
				//associate information about the advertiser and the listing in the message
				add_post_meta( $post_id, 'wpcf-message-to', $advertiser_id, true );
				add_post_meta( $post_id, 'wpcf-to-firstname', $advertiser_data->user_firstname, true );
				add_post_meta( $post_id, 'wpcf-to-lastname', $advertiser_data->user_lastname, true );
				add_post_meta( $post_id, 'wpcf-to-email', $advertiser_data->user_email, true );
				add_post_meta( $post_id, 'wpcf-listing-id', $listing_id, true );


			}
		}

		/**
		 * @param $post_id
		 * @param $form_data
		 */
		public function classifieds_cred_submit_complete_new_message( $post_id, $form_data ) {
			$_SESSION['classifieds_msg_post_id'] = $post_id;
			$user_email                          = get_post_meta( $post_id, 'wpcf-from-email', true );
			if ( email_exists( $user_email ) != false ) {
				$user_id = email_exists( $user_email );
			}
			if ( ! isset( $user_id ) && email_exists( $user_email ) == false ) {
				$guest_password = 'PASSWORD';

				/** Instead of using email as the username, let's create a sensible non-email based username for this user */
				/** Let's generate username */

				//Retrieve first name from db
				$db_first_name = get_post_meta( $post_id, 'wpcf-from-firstname', true );

				//Retrieve last name from db
				$db_last_name = get_post_meta( $post_id, 'wpcf-from-lastname', true );

				if ( ( ! ( empty( $db_first_name ) ) ) && ( ! ( empty( $db_last_name ) ) ) ) {
					//User has first name and last name in the dB, proceed...
					$username = $this->classifieds_generateusername_from_name( $db_first_name, $db_last_name );

					//Before we use this username, let's ensure its unique
					$user_id = username_exists( $username );

					while ( ( $user_id ) ) {

						//ID exist on this username, regenerate
						$username = $this->classifieds_generateusername_from_name( $db_first_name, $db_last_name );
						$user_id  = username_exists( $username );
					}
				} else {
					//For some reason, first name or last name is not set, let's use native email
					$username = $user_email;
				}

				//At this point, we should have a unique username generated, let's use this to create this user
				$user_id = wp_create_user( $username, $guest_password, $user_email );
				wp_update_user( array( 'ID' => $user_id, 'role' => 'subscriber' ) );

				//Add First name and Lastname to WooCommerce Account
				$user_id_int = intval( $user_id );
				if ( $user_id_int > 0 ) {

					//Update
					update_user_meta( $user_id, 'billing_first_name', $db_first_name );
					update_user_meta( $user_id, 'first_name', $db_first_name );
					update_user_meta( $user_id, 'billing_last_name', $db_last_name );
					update_user_meta( $user_id, 'last_name', $db_last_name );
				}

				wp_set_auth_cookie( $user_id, false, is_ssl() );
			}
			update_post_meta( $post_id, 'wpcf-message-from', $user_id );
			wp_update_post( array( 'ID' => $post_id, 'post_author' => $user_id ) );
			//duplicate post to WPML translation
			parent::_classifieds_duplicate_on_publish( $post_id );
		}

		/**
		 * @param $firstname
		 * @param $lastname
		 *
		 * @return bool|string
		 */
		public function classifieds_generateusername_from_name( $firstname, $lastname ) {

			$username = false;

			if ( ( ! ( empty( $firstname ) ) ) && ( ! ( empty( $lastname ) ) ) ) {

				//Get first character of lastname
				$lastname_initial  = substr( $lastname, 0, 1 );
				$lastname_initial  = strtoupper( $lastname_initial );
				$random_number     = mt_rand( 1, 9999 );
				$firstname         = str_replace( ' ', '_', $firstname );
				$form_pre_username = $firstname . $lastname_initial . $random_number;
				$username          = sanitize_user( $form_pre_username, true );
			}

			return $username;

		}

		/**
		 * @param $post_id
		 * @param $form_data
		 */
		public function classifieds_cred_save_data_reply_message_function( $post_id, $form_data ) {
			/* check if we have a listing-id so we can associate the reply message to it */
			if ( ! empty( $_GET['listing-id'] ) ) {
				$listing_id = intval( $_GET['listing-id'] );
				add_post_meta( $post_id, 'wpcf-listing-id', $listing_id, true );
			}
			if ( ! empty( $_GET['original-message-id'] ) ) {
				//retrieve infromation from the initial message to the reply one
				$original_message_id    = intval( $_GET['original-message-id'] );
				$message_from_user_id   = get_post_meta( $original_message_id, 'wpcf-message-to', true );
				$user_from_data         = get_userdata( $message_from_user_id );
				$message_from_firstname = $user_from_data->user_firstname;
				$message_from_lastname  = $user_from_data->user_lastname;
				$message_from_email     = $user_from_data->user_email;
				$message_to_user_id     = get_post_meta( $original_message_id, 'wpcf-message-from', true );
				add_post_meta( $post_id, 'wpcf-message-from', $message_from_user_id, true );
				add_post_meta( $post_id, 'wpcf-from-firstname', $message_from_firstname, true );
				add_post_meta( $post_id, 'wpcf-from-lastname', $message_from_lastname, true );
				add_post_meta( $post_id, 'wpcf-from-email', $message_from_email, true );
				add_post_meta( $post_id, 'wpcf-message-to', $message_to_user_id, true );
			}
		}

		/**
		 * @param $post_id
		 * @param $form_data
		 */
		public function classifieds_cred_submit_complete_reply_message( $post_id, $form_data ) {
			//duplicate post to WPML translation
			parent::_classifieds_duplicate_on_publish( $post_id );
		}

		/**
		 * get message userdata
		 *
		 * @param $atts
		 *
		 * @return mixed|string
		 */
		public function classifieds_get_message_userdata( $atts ) {
			if ( ! empty( $_GET['original-message-id'] ) ) {
				$original_message_id = intval( $_GET['original-message-id'] );
			}
			extract( shortcode_atts( array(
				'field_user' => '',
				'user_id'    => '',
			), $atts ) );
			switch ( $field_user ) {
				case 'user_name':
					$user_data      = get_userdata( $user_id );
					$user_firstname = $user_data->user_firstname;
					$user_lastname  = $user_data->user_lastname;
					$user_name      = trim( $user_firstname . ' ' . $user_lastname );
					if ( ! empty( $user_name ) ) {
						$query = trim( $user_firstname . ' ' . $user_lastname );
					} else {
						$query = $user_data->user_login;
					}
					break;
				case 'firstname':
					if ( isset( $original_message_id ) ) {
						$query = get_post_meta( $original_message_id, 'wpcf-from-firstname', true );
					}
					break;
				case 'lastname':
					if ( isset( $original_message_id ) ) {
						$query = get_post_meta( $original_message_id, 'wpcf-from-lastname', true );
					}
					break;
				case 'email':
					if ( isset( $original_message_id ) ) {
						$query = get_post_meta( $original_message_id, 'wpcf-from-email', true );
					}
					break;
				case 'validation':
					$user_data = get_userdata( $user_id );
					//generate encrypted key with secret pass-phrase and user password (hashed) to challenge URL request
					//Let's use password, harder to guess than username or nickname
					$query = sha1( 'Toolset Classifieds by OnTheGoSystems' . $user_data->user_pass );
					break;
			}
			if ( isset( $query ) ) {
				return $query;
			}
		}

		/**
		 * auto login for client/subscriber account
		 */
		public function classifieds_process_guest_login() {
			if ( isset( $_GET['user'] ) && ! is_user_logged_in() ) {				
				//Validate user
				$user_id_validated = false;
				$the_user = $_GET['user'];
				$the_user = intval( $the_user );
				if ( $the_user > 0 ) {
					$user_id_validated = true;
				} 
				
				//Get user info based on validated ID
				$userinfo = get_user_by( 'id', $the_user );
				
				//Check if valid
				if ( ( ! isset( $_GET['validation'] ) ) || ( false === $user_id_validated ) ) {
					//Invalid or not complete
					return;
				}
				
				//Validate SHA1 code being passed
				$validation = false;
				$validation_code = $_GET['validation'];
				$validation_code = trim( $validation_code );
				$is_sha_string = $this->is_sha1( $validation_code );	
				
				if ( false === $is_sha_string ) {
					//Invalid string
					return;
				}
				
				$user_data = get_userdata( $userinfo->ID );	
				$user_pass = $user_data->user_pass;
				
				//Validate user
				if ( ! ($user_data) ) {
					//False
					return;
				}
				
				//We have validated user data at this point.
				//Generate reference sha1
				$reference_sha_authenticate = sha1( 'Toolset Classifieds by OnTheGoSystems' . $user_pass );
				
				//Authenticate encrypted key with secret pass-phrase in URL request
				if ( $reference_sha_authenticate === $validation_code ) {
					$validation = true;
				}
				
				//Validate login
				if ( $validation && $userinfo->has_cap( 'read' ) ) {					
					$location               = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
					
					//Validated and user can read. set cookie for login						
					wp_clear_auth_cookie();
					wp_set_current_user ( $userinfo->ID );
					wp_set_auth_cookie  ( $userinfo->ID );					 
				} else {
					//Validation fails, send to WC Login page
					$the_page           = get_page_by_title( 'My Account' );
					$translated_page_id = $this->_classifieds_lang_id( $the_page->ID, 'page' );
					$location           = esc_url( get_permalink( $translated_page_id ) );
				}
				// redirect after header definitions - cannot use wp_redirect($location);
				?>
				<script type="text/javascript">
					<!--
					window.location = <?php echo "'" . $location . "'"; ?>;
					//-->
				</script>
				<?php
			}
		}
		public function is_sha1($str) {
			$validated = false;
			if ( ! ( empty( $str ) ) ) {
				return (bool) preg_match('/^[0-9a-f]{40}$/i', $str);
			}
			return $validated;			
		}
		/**
		 * get message data for the email body
		 *
		 * @param $atts
		 *
		 * @return mixed|string
		 */
		public function classifieds_get_message_data( $atts ) {
			extract( shortcode_atts( array(
				'post_id' => '',
				'get'     => ''
			), $atts ) );
			switch ( $get ) {
				case 'from_name':
					$from_firstname = get_post_meta( $post_id, 'wpcf-from-firstname', true );
					$from_lastname  = get_post_meta( $post_id, 'wpcf-from-lastname', true );
					$query          = trim( $from_firstname . ' ' . $from_lastname );
					break;
				case 'to_name':
					$to_firstname = get_post_meta( $post_id, 'wpcf-to-firstname', true );
					$to_lastname  = get_post_meta( $post_id, 'wpcf-to-lastname', true );
					$query        = trim( $to_firstname . ' ' . $to_lastname );
					break;
				case 'message_content':
					$query = get_post_meta( $post_id, 'wpcf-message-description', true );
					break;
			}

			return $query;
		}

		/**
		 *
		 * clean as much as possible the output of a Views Loop
		 * in order to be able to use it on a CRED generic field default values
		 *
		 * @param $out
		 * @param $id
		 *
		 * @return string
		 */
		function prefix_clean_view_output( $out, $id ) {

			$advertiser_view_title = 'Advertiser details view';
			$post_type             = 'view';

			$advertiser_view = get_page_by_title( $advertiser_view_title, 'OBJECT', $post_type );

			if ( $advertiser_view->ID && $advertiser_view->ID == $id ) {
				$start = strpos( $out, '<!-- wpv-loop-start -->' );
				if (
					$start !== false
					&& strrpos( $out, '<!-- wpv-loop-end -->', $start ) !== false
				) {
					$start = $start + strlen( '<!-- wpv-loop-start -->' );
					$out   = substr( $out, $start );
					$end   = strrpos( $out, '<!-- wpv-loop-end -->' );
					$out   = substr( $out, 0, $end );
				}
			}

			return $out;
		}

	}
}
