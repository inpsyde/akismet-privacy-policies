<?php

/**
 * Plugin Name: Akismet Privacy Policies
 * Plugin URI:  http://wpde.org/
 * Description: Ergänzt das Kommentarformular um datenschutzrechtliche Hinweise bei Nutzung des Plugins Akismet.
 * Version:     2.0.0
 * Author:      Inpsyde GmbH
 * Author URI:  https://inpsyde.com
 * Text Domain: akismet-privacy-policies
 * License:     GPLv2+
 */
class Akismet_Privacy_Policies {

	static private $classobj;
	// Default for active checkbox on comment form.
	public $checkbox;
	// Available languages.
	public $languages;
	// Translation object, needed if current locale unequal translation locale.
	public $mo;
	// Translation languages.
	public $translation;
	// The options store.
	public $options;
	// Default for notice on comment form.
	public $notice;
	// Default for error message, if checkbox is not active on comment form.
	public $error_message;
	// Default style to float checkbox.
	public $style = 'input#akismet_privacy_check { float: left; margin: 7px 7px 7px 0; width: 13px; }';

	/**
	 * construct
	 *
	 * @uses   add_filter
	 * @access public
	 * @since  0.0.1
	 */
	public function __construct() {
		register_deactivation_hook( __FILE__, [ &$this, 'unregister_settings' ] );
		register_uninstall_hook( __FILE__, [ 'Akismet_Privacy_Policies', 'unregister_settings' ] );

		add_filter( 'comment_form_defaults', [ $this, 'add_comment_notice' ], 11, 1 );
		add_action( 'akismet_privacy_policies', [ $this, 'add_comment_notice' ] );

		$this->languages = get_available_languages();
		// default language is en_US but get_available_languages
		// contains translations in .mo files
		$this->languages[] = 'en_US';

		$this->translation = get_user_locale();
		if ( isset( $_GET['translation'] ) ) {
			$this->translation = $_GET['translation'];
		} elseif ( isset( $_POST['translation'] ) ) {
			$this->translation = $_POST['translation'];
		}

		if ( $this->translation !== 'en_US' ) {
			$this->mo = new Mo;
			$mofile   = __DIR__ . '/languages/akismet-privacy-policies-' . $this->translation . '.mo';
			$this->mo->import_from_file( $mofile );
		}

		$this->options = get_option( 'akismet_privacy_notice_settings_' . $this->translation );

		if ( empty( $this->options['checkbox'] ) ) {
			$this->options['checkbox'] = $this->checkbox;
		}
		if ( $this->options['checkbox'] ) {
			add_action( 'pre_comment_on_post', [ $this, 'print_error_message' ] );
		}
		if ( ! isset( $this->options['style'] ) ) {
			$this->options['style'] = $this->style;
		}
		if ( $this->options['style'] ) {
			add_action( 'wp_head', [ $this, 'add_style' ] );
		}

		// for settings
		add_action( 'init', [ $this, 'translate_strings' ] );
		add_action( 'init', [ $this, 'akismet_privacy_policies_textdomain' ] );
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_filter( 'plugin_action_links', [ $this, 'plugin_action_links' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Handler for the action 'init'. Instantiates this class.
	 *
	 * @return Akismet_Privacy_Policies $classobj
	 * @since  0.0.2
	 * @access public
	 */
	public static function get_object() {
		if ( self::$classobj === null ) {
			self::$classobj = new self;
		}

		return self::$classobj;
	}

	/**
	 * Initialize and translate $this->notice, $this->error_message
	 *
	 * @return void
	 * @since  2.0.0
	 * @access public
	 */
	public function translate_strings() {
		// dummy translation, only there to let poedit recognize the text...
		esc_html__( 'I accept that my given data and my IP address is sent to a server in the USA only for the purpose of spam prevention through the <a href="http://akismet.com/">Akismet</a> program.<br /><a href="https://akismet.com/gdpr/">More information on Akismet and GDPR</a>.',
		            'akismet-privacy-policies' );
		esc_html__( '<p><strong>Attention:</strong> You have not accepted our privacy disclaimer.</p>',
		            'akismet-privacy-policies' );
		if ( $this->translation !== 'en_US' ) {
			$this->notice = $this->mo->translate( 'I accept that my given data and my IP address is sent to a server in the USA only for the purpose of spam prevention through the <a href="http://akismet.com/">Akismet</a> program.<br /><a href="https://akismet.com/gdpr/">More information on Akismet and GDPR</a>.',
			                                      'akismet-privacy-policies' );
		} else {
			$this->notice = 'I accept that my given data and my IP address is sent to a server in the USA only for the purpose of spam prevention through the <a href="http://akismet.com/">Akismet</a> program.<br /><a href="https://akismet.com/gdpr/">More information on Akismet and GDPR</a>.';
		}
		if ( $this->translation !== 'en_US' ) {
			$this->error_message = $this->mo->translate( '<p><strong>Attention:</strong> You have not accepted our privacy disclaimer.</p>',
			                                             'akismet-privacy-policies' );
		} else {
			$this->error_message = '<p><strong>Attention:</strong> You have not accepted our privacy disclaimer.</p>';
		}
	}

	/**
	 * find translations
	 *
	 * @access public
	 * @return void
	 * @since  2.0.0
	 *
	 * @uses   load_plugin_textdomain
	 */
	public function akismet_privacy_policies_textdomain() {
		load_plugin_textdomain( 'akismet-privacy-policies', false, basename( __DIR__ ) . '/languages' );
	}

	/**
	 * Print content for policies include markup.
	 * Use filter hook akismet_privacy_notice_options for change markup or notice.
	 *
	 * @access  public
	 *
	 * @param array string $arr_comment_defaults
	 *
	 * @since   0.0.1
	 *
	 * @uses    apply_filters
	 */
	public function add_comment_notice( $arr_comment_defaults ) {
		if ( is_user_logged_in() ) {
			return $arr_comment_defaults;
		}

		if ( ! isset( $this->options['checkbox'] )
		     || ( empty( $this->options['checkbox'] )
		     && $this->options['checkbox'] !== 0 )
		) {
			$this->options['checkbox'] = $this->checkbox;
		}
		if ( empty( $this->options['notice'] ) ) {
			$this->options['notice'] = wp_kses( $this->notice, wp_kses_allowed_html($this->notice) );
		}

		$defaults = [
			'css_class'    => 'privacy-notice',
			'html_element' => 'p',
			'text'         => $this->options['notice'],
			'checkbox'     => $this->options['checkbox'],
			'position'     => 'comment_notes_after',
		];

		// Make it filterable
		$params = apply_filters( 'akismet_privacy_notice_options', $defaults );

		// Create the output
		$html = "\n" . '<' . $params['html_element'];
		if ( ! empty( $params['css_class'] ) ) {
			$html .= ' class="' . $params['css_class'] . '"';
		}
		$html .= '>' . "\n";
		if ( (bool) $params['checkbox'] ) {
			$html .= '<input type="checkbox" id="akismet_privacy_check" name="akismet_privacy_check" value="1" aria-required="true" />'
			         . "\n";
			$html .= '<label for="akismet_privacy_check">';
		}
		$html .= $params['text'];
		if ( (bool) $params['checkbox'] ) {
			$html .= '</label>';
		}
		$html .= '</' . $params['html_element'] . '>' . "\n";

		// Add the text to array
		if ( isset( $arr_comment_defaults['comment_notes_after'] ) ) {
			$arr_comment_defaults['comment_notes_after'] .= $html;

			return $arr_comment_defaults;
		}

		// for custom hook in theme
		$arr_comment_defaults = $html;
		echo $arr_comment_defaults;
	}

	/**
	 * Return Message on inactive checkbox
	 * Use filter akismet_privacy_error_message for change text or markup
	 *
	 * @since  0.0.2
	 * @uses   wp_die
	 * @access public
	 */
	public function print_error_message() {
		if ( is_user_logged_in() ) {
			return;
		}

		if ( empty( $this->options['error_message'] ) ) {
			$this->options['error_message'] = $this->error_message;
		}

		// check for checkbox active
		if ( isset( $_POST['comment'] ) && ! isset( $_POST['akismet_privacy_check'] ) ) {
			$message = apply_filters(
				'akismet_privacy_error_message',
				wp_kses( $this->options['error_message'], wp_kses_allowed_html( $this->options['error_message'] ) )
			);
			wp_die( $message );
		}
	}

	/**
	 * Print style in wp_head.
	 *
	 * @since  0.0.2
	 * @uses   get_option, plugin_action_links, plugin_basename
	 * @access public
	 */
	public function add_style() {
		if ( is_user_logged_in() ) {
			return;
		}

		if ( empty( $this->options['style'] ) ) {
			$this->options['style'] = $this->style;
		}

		echo '<style type="text/css" media="screen">' . esc_html( $this->options['style'] ) . '</style>';
	}

	/**
	 * Add settings link on plugins.php in backend
	 *
	 * @param array $links , string $file
	 *
	 * @param       $file
	 *
	 * @return array
	 * @uses   plugin_basename, esc_html__
	 * @access public
	 *
	 * @since  0.0.2
	 */
	public function plugin_action_links( $links, $file ) {
		if ( plugin_basename( __DIR__ . '/akismet-privacy-policies.php' ) === $file ) {
			$links[] = '<a href="options-general.php?page=akismet_privacy_notice_settings_group">'
			           . esc_html__( 'Settings', 'akismet-privacy-policies' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Add settings page in WP backend
	 *
	 * @return void
	 * @since  0.0.2
	 * @uses   add_options_page, add_action
	 * @access public
	 */
	public function add_settings_page() {
		$options_page = add_options_page(
			esc_html__( 'Akismet Privacy Policies Settings', 'akismet-privacy-policies' ),
			esc_html__( 'Akismet Privacy Policies', 'akismet-privacy-policies' ),
			'manage_options',
			'akismet_privacy_notice_settings_group',
			[ $this, 'get_settings_page' ]
		);

		add_action( 'load-' . $options_page, [ $this, 'contextual_help' ] );
	}

	/**
	 * Return form and markup on settings page
	 *
	 * @since  0.0.2
	 * @uses   settings_fields, normalize_whitespace
	 * @access public
	 */
	public function get_settings_page() {
		?>
		<div class="wrap">
			<h2>
				<?php echo $this->get_plugin_data( 'Name' ); ?>
			</h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'akismet_privacy_notice_settings_group' );
				if ( ! isset( $this->options['checkbox'] )
				     || ( empty( $this->options['checkbox'] )
				     && $this->options['checkbox'] !== 0 )
				) {
					$this->options['checkbox'] = $this->checkbox;
				}
				if ( empty( $this->options['notice'] ) ) {
					$this->options['notice'] = normalize_whitespace( $this->notice );
				}
				if ( empty( $this->options['error_message'] ) ) {
					$this->options['error_message'] = normalize_whitespace( $this->error_message );
				}
				if ( empty( $this->options['style'] ) ) {
					$this->options['style'] = normalize_whitespace( $this->style );
				}
				?>
				<input type="hidden" name="translation" value="<?php echo $this->translation ?>">
				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row">
							<label for="select_translation_language">
								<?php esc_html_e( 'Select translation language', 'akismet-privacy-policies' ) ?>
							</label>
						</th>
						<td>
							<select id="select_translation_language"
								name="translation"
								onchange="location = location.href+'&amp;translation='+this.options[this.selectedIndex].value">
								<?php foreach ( $this->languages as $lang ) { ?>
									<option value="<?php echo esc_attr( $lang ); ?>" <?php selected(
										$this->translation, $lang ) ?>><?php echo strtoupper( substr( $lang, 0, 2 ) ) ?>
									</option>
								<?php } ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="akismet_privacy_checkbox">
								<?php esc_html_e( 'Consent via checkbox', 'akismet-privacy-policies' ); ?>
							</label>
						</th>
						<td>
							<input type="checkbox"
								id="akismet_privacy_checkbox"
								name="akismet_privacy_notice_settings_<?php echo $this->translation ?>[checkbox]"
								value="1"
								<?php if ( isset( $this->options['checkbox'] ) ) {
									checked( 1, $this->options['checkbox'] );
								} ?> />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="akismet_privacy_notice">
								<?php _e( 'Privacy Notice', 'akismet-privacy-policies' ) ?>
							</label>
						</th>
						<td>
							<textarea id="akismet_privacy_notice"
								name="akismet_privacy_notice_settings_<?php echo $this->translation ?>[notice]"
								cols="80"
								rows="10"
								aria-required="true"><?php if ( isset( $this->options['notice'] ) ) {
									$msg = $this->options['notice'];
									if ( $this->translation !== 'en_US' ) {
										$msg = $this->mo->translate( $this->options['notice'] );
									}
									echo $msg;
								} ?>
							</textarea>
							<br /><?php _e( '<strong>Note:</strong> HTML is possible', 'akismet-privacy-policies' ) ?>
							<br /><?php _e( '<strong>Attention:</strong> You will have to add the link to your privacy statement manually. Since WordPress version 5 you can find a link to a guide for creating your own privacy statement under \'Settings\' &rarr; \'Privacy\'.',
							                'akismet-privacy-policies' ) ?>
							<br /><strong><?php esc_html_e( 'Example:', 'akismet-privacy-policies' ) ?></strong> <?php
							$example_notice = $this->notice;
							if ( $this->translation !== 'en_US' ) {
								$example_notice = $this->mo->translate( $this->notice );
							}
							echo '<code>' . esc_html( $example_notice ) . '</code>';
							?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="akismet_privacy_error_message">
								<?php esc_html_e( 'Error Notice', 'akismet-privacy-policies' ) ?>
							</label>
						</th>
						<td>
							<textarea id="akismet_privacy_error_message"
								name="akismet_privacy_notice_settings_<?php echo $this->translation ?>[error_message]"
								cols="80"
								rows="10"
								aria-required="true"><?php if ( isset( $this->options['error_message'] ) ) {
									$msg = $this->options['error_message'];
									if ( $this->translation !== 'en_US' ) {
										$msg = $this->mo->translate( $this->options['error_message'] );
									}
									echo $msg;
								} ?>
							</textarea>
							<br /><?php _e( '<strong>Note:</strong> HTML is possible', 'akismet-privacy-policies' ) ?>
							<br /><strong><?php esc_html_e( 'Example:', 'akismet-privacy-policies' ) ?></strong> <?php
							$example_error = $this->error_message;
							if ( $this->translation !== 'en_US' ) {
								$example_error = $this->mo->translate( $this->error_message );
							}
							echo '<code>' . esc_html( $example_error ) . '</code>';
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="akismet_privacy_style">Stylesheet</label></th>
						<td><textarea id="akismet_privacy_style"
								name="akismet_privacy_notice_settings_<?php echo $this->translation ?>[style]"
								cols="80"
								rows="10"
								aria-required="true"><?php if ( isset( $this->options['style'] ) ) {
									echo $this->options['style'];
								} ?></textarea>
							<br /><?php _e( '<strong>Note:</strong> CSS is possible', 'akismet-privacy-policies' ) ?>
							<br /><strong>
								<?php _e(
									'Example:',
									'akismet-privacy-policies' );
								?>
							</strong>
							<?php echo '<code>' . esc_html( $this->style ) . '</code>'; ?>
						</td>
					</tr>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes',
					                                                             'akismet-privacy-policies' ) ?>" />
				</p>

				<?php _e( '<p>You can find more information about the topic here: <a href="https://akismet.com/gdpr/">akismet.com/gdpr/</a>. This plugin has been developed by <a href="http://inpsyde.com/" title="visit inpsyde.com">Inpsyde GmbH</a>, Germany, with legal support by the law firm <a href="http://spreerecht.de/" title="visit spreerecht.de">SCHWENKE &amp; DRAMBURG.</a></p>',
				          'akismet-privacy-policies' ) ?>
			</form>
		</div>
		<?php
	}

	/**
	 * return plugin comment data
	 *
	 * @param $value string Name, PluginURI, Version, Description, Author, AuthorURI, TextDomain, DomainPath, Network, Title
	 *
	 * @return string
	 * @since  0.0.2
	 * @access public
	 *
	 */
	public function get_plugin_data( $value = null ) {
		$plugin_data = get_plugin_data( __FILE__ );

		return $plugin_data[ $value ];
	}

	/**
	 * Validate settings for options
	 *
	 * @param array $value
	 *
	 * @return array
	 * @since  0.0.2
	 * @uses   normalize_whitespace
	 * @access public
	 *
	 */
	public function validate_settings( $value ) {
		if ( isset( $value['checkbox'] ) && $value['checkbox'] === '1' ) {
			$value['checkbox'] = 1;
		} else {
			$value['checkbox'] = 0;
		}
		$value['notice']        = normalize_whitespace( $value['notice'] );
		$value['error_message'] = normalize_whitespace( $value['error_message'] );
		$value['style']         = normalize_whitespace( $value['style'] );

		return $value;
	}

	/**
	 * Register settings for options
	 *
	 * @return void
	 * @since  0.0.2
	 * @uses   register_setting
	 * @access public
	 */
	public function register_settings() {
		register_setting(
			'akismet_privacy_notice_settings_group',
			'akismet_privacy_notice_settings_' . $this->translation,
			[ 'sanitize_callback' => [ $this, 'validate_settings' ] ]
		);
	}

	/**
	 * Unregister and delete settings; clean database
	 *
	 * @return void
	 * @since  0.0.2
	 * @uses   unregister_setting, delete_option
	 * @access public
	 */
	public function unregister_settings() {
		$all_options   = wp_load_alloptions();
		$to_be_deleted = preg_grep(
			'/^akismet_privacy_notice_settings(_)*[a-z]*(_)*[A-Z]*$/',
			array_keys( $all_options )
		);
		foreach ( $to_be_deleted as $option ) {
			unregister_setting( 'akismet_privacy_notice_settings_group', $option );
			delete_option( $option );
		}
	}

	/**
	 * Add help text
	 *
	 * @internal param string $screen
	 *
	 * @since    0.0.2
	 * @uses     normalize_whitespace
	 *
	 */
	public function contextual_help() {
		$contextual_help =
			'<p>' . __(
				'The plugin amends the comment form by a privacy notice which is necessary in some countries due to EU legislation',
				'akismet-privacy-policies'
			) . '</p>'
			. '<ul>'
			. '<li>' . __(
				'Use the plugin\'s preferences page to create settings.',
				'akismet-privacy-policies'
			) . '</li>'
			. '<li>' . __( 'Logged-in users will not see the privacy notice within their comment form.',
			               'akismet-privacy-policies' ) . '</li>'
			. '<li><strong>' . __(
				'You will have to add a link to your privacy statement manually within the privacy notice. '
			) . '</strong></li>'
			. '<li>' . __(
				'For your privacy statement you may use the following template:<br />',
				'akismet-privacy-policies' ) .
			__( '<code>&lt;strong&gt;Akismet Anti-Spam&lt;/strong&gt;', 'akismet-privacy-policies' ) .
			__( 'This page uses the &nbsp;&lt;a href="http://akismet.com/"&gt;Akismet</a>-plugin by&nbsp;&lt;a href="http://automattic.com/"&gt;Automattic&lt;/a&gt; Inc., 60 29th Street #343, San Francisco, CA 94110-4929, USA. By means of this plugin it is possible to filter out spam comments (e.g. created by robots, containing unsolicited advertisements or links to malware). For this purpose comments are sent to a server in the USA where they get analyzed and stored for four days. If a comment gets classified as being spam, the data will be stored beyond the four-day limit. The information being stored contains  the given name, the email address, the IP address, the comment content, the referrer, information about the browser being used to send the comment as well as the operating system of the computer and date and time when the comment was sent. You may use pseudonymes or ommit name and email address. You will not be able to send a comment if you do not consent by clicking the checkbox. You may object to the usage of your data by writing to&nbsp;&lt;a href="mailto:support@wordpress.com" target="_blank"&gt;support@wordpress.com&lt;/a&gt;, subject "Deletion of Data stored by Akismet" giving/describing the stored data.</code>',
			    'akismet-privacy-policies'
			) . '</li>'
			. '<li>' . __(
				'You can find more information about the topic here: <a href="https://akismet.com/gdpr/">akismet.com/gdpr/</a>',
				'akismet-privacy-policies'
			) . '</li>'
			. '<li>' . __(
				'This plugin has been developed by <a href="http://inpsyde.com/" title="Visit Inpsyde GmbH">Inpsyde GmbH</a> with legal support by the law firm <a href="http://spreerecht.de/" title="Visit spreerecht.de">SCHWENKE &amp; DRAMBURG</a>.',
				'akismet-privacy-policies'
			) . '</li>'
			. '</ul>';

		$screen = get_current_screen();
		if ( $screen !== null ) {
			$screen->add_help_tab(
				[
					'id'      => 'settings_page_akismet_privacy_notice_settings_group',
					'title'   => __( 'Akismet Privacy Policies', 'akismet-privacy-policies' ),
					'content' => normalize_whitespace( $contextual_help ),
				]
			);
		}
	}
} // end class

if ( function_exists( 'add_action' ) && class_exists( 'Akismet_Privacy_Policies' ) ) {
	add_action( 'plugins_loaded', [ 'Akismet_Privacy_Policies', 'get_object' ] );
} else {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
