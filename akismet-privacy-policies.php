<?php

/**
 * Plugin Name: Akismet Privacy Policies
 * Plugin URI:  http://wpde.org/
 * Description: Erg&auml;nzt das Kommentarformular um datenschutzrechtliche Hinweise bei Nutzung des Plugins Akismet.
 * Version:     1.1.2
 * Author:      Inpsyde GmbH
 * Author URI:  http://inpsyde.com/
 * License:     GPLv2+
 */
class Akismet_Privacy_Policies {

	static private $classobj;

	// default translation language
	// public $default_locale = 'de_DE';

	// available locales
	// public $locales = array(
	// 	'de_DE' => 'Deutsch',
	// 	'en_US' => 'English',
	// 	'fr_FR' => 'Francais',
	// 	'it_IT' => 'Italiano',
	// 	'es_ES' => 'Espanol'
	// );

	// default for active checkbox on comment form
	public $checkbox = 1;

  // translation languages
	public $translation;

	// the $this->options
	public $options;

	// default for notice on comment form
	public $notice;

	// default for error message, if checkbox is not active on comment form
	public $error_message;

	// default style to float checkbox
	public $style = 'input#akismet_privacy_check { float: left; margin: 7px 7px 7px 0; width: 13px; }';

	/**
	 * construct
	 *
	 * @uses   add_filter
	 * @access public
	 * @since  0.0.1
	 * @return \Akismet_Privacy_Policies
	 */
	public function __construct() {

		register_deactivation_hook( __FILE__, array( &$this, 'unregister_settings' ) );
		register_uninstall_hook( __FILE__, array( 'Akismet_Privacy_Policies', 'unregister_settings' ) );

		add_filter( 'comment_form_defaults', array( $this, 'add_comment_notice' ), 11, 1 );
		add_action( 'akismet_privacy_policies', array( $this, 'add_comment_notice' ) );

		$this->translation = isset( $_GET[ 'translation' ]) ? $_GET[ 'translation' ] : get_user_locale();
		$this->options = get_option( 'akismet_privacy_notice_settings_' . $this->translation );

		// echo '$this: ';
		// var_dump($this);
		if ( empty( $this->options[ 'checkbox' ] ) ) {
			$this->options[ 'checkbox' ] = $this->checkbox;
		}
		if ( $this->options[ 'checkbox' ] ) {
			add_action( 'pre_comment_on_post', array( $this, 'error_message' ) );
		}
		if ( ! isset( $this->options[ 'style' ] ) ) {
			$this->options[ 'style' ] = $this->style;
		}
		if ( $this->options[ 'style' ] ) {
			add_action( 'wp_head', array( $this, 'add_style' ) );
		}

		// for settings
		add_action( 'init', array( $this, 'akismet_privacy_policies_textdomain' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		// add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		add_action( 'init', array( $this, 'translate_strings' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		// add_action( 'admin_init', array( $this, 'pre_get_posts_test' ) );
	}

	/**
	 * Handler for the action 'init'. Instantiates this class.
	 *
	 * @since  0.0.2
	 * @access public
	 * @return \Akismet_Privacy_Policies $classobj
	 */
	public static function get_object() {

		if ( NULL === self::$classobj ) {
			self::$classobj = new self;
		}

		return self::$classobj;
	}

	/**
	 *
	 */
	public function translate_strings() {
		$this->notice = __( '<strong>Achtung:</strong> Ich erkl&auml;re mich damit einverstanden, dass alle eingegebenen Daten und meine IP-Adresse nur zum Zweck der Spamvermeidung durch das Programm <a href="http://akismet.com/">Akismet</a> in den USA &uuml;berpr&uuml;ft und gespeichert werden.<br /><a href="http://faq.wpde.org/hinweise-zum-datenschutz-beim-einsatz-von-akismet-in-deutschland/">Weitere Informationen zu Akismet und Widerrufsm&ouml;glichkeiten</a>.', 'akismet-privacy-policies' );
		$this->error_message = __( '<p><strong>Achtung:</strong> Du hast die datenschutzrechtlichen Hinweise nicht akzeptiert.</p>', 'akismet-privacy-policies' );

	}

	/**
	 * Add query string translation to query
	 */
	public function add_custom_query_var( $qvars ) {
		$qvars[] = 'translation';
		return $qvars;
	}

	public function pre_get_posts_test() {
		global $wp_query;
		echo "<pre>"; print_r($wp_query); echo "</pre>";
	}

	/**
	 * return plugin comment data
	 *
	 * @since  0.0.2
	 * @access public
	 *
	 * @param $value string, default = 'Version'
	 *               Name, PluginURI, Version, Description, Author, AuthorURI, TextDomain, DomainPath, Network, Title
	 *
	 * @return string
	 */
	public function get_plugin_data( $value = 'Version' ) {

		$plugin_data  = get_plugin_data( __FILE__ );
		$plugin_value = $plugin_data[ $value ];

		return $plugin_value;
	}

	/**
	 * find translations
	 *
	 * @access public
	 * @uses load_plugin_textdomain
	 */
	public function akismet_privacy_policies_textdomain() {
		load_plugin_textdomain( 'akismet-privacy-policies', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * return content for policies include markup
	 * use filter hook akismet_privacy_notice_options for change markup or notice
	 *
	 * @access public
	 * @uses   apply_filters
	 * @since  0.0.1
	 *
	 * @param array string $arr_comment_defaults
	 *
	 * @return array | string $arr_comment_defaults or 4html
	 */
	public function add_comment_notice( $arr_comment_defaults ) {

		if ( is_user_logged_in() ) {
			return $arr_comment_defaults;
		}

		// $locale = isset( $_GET[ 'translation' ]) ? $_GET[ 'lang'] : get_locale();
		// $this->options = get_option( 'akismet_privacy_notice_settings_' . $this->translation );

		if ( ! isset( $this->options[ 'checkbox' ] ) || empty( $this->options[ 'checkbox' ] ) && 0 != $this->options[ 'checkbox' ] ) {
			$this->options[ 'checkbox' ] = $this->checkbox;
		}
		if ( empty( $this->options[ 'notice' ] ) ) {
			$this->options[ 'notice' ] = __( $this->notice );
		}

		$defaults = array(
			'css_class'    => 'privacy-notice',
			'html_element' => 'p',
			'text'         => $this->options[ 'notice' ],
			'checkbox'     => $this->options[ 'checkbox' ],
			'position'     => 'comment_notes_after'
		);

		// Make it filterable
		$params = apply_filters( 'akismet_privacy_notice_options', $defaults );

		// Create the output
		$html = "\n" . '<' . $params[ 'html_element' ];
		if ( ! empty( $params[ 'css_class' ] ) ) {
			$html .= ' class="' . $params[ 'css_class' ] . '"';
		}
		$html .= '>' . "\n";
		if ( (bool) $params[ 'checkbox' ] ) {
			$html .= '<input type="checkbox" id="akismet_privacy_check" name="akismet_privacy_check" value="1" aria-required="true" />' . "\n";
			$html .= '<label for="akismet_privacy_check">';
		}
		$html .= $params[ 'text' ];
		if ( (bool) $params[ 'checkbox' ] ) {
			$html .= '</label>';
		}
		$html .= '</' . $params[ 'html_element' ] . '>' . "\n";

		// Add the text to array
		if ( isset( $arr_comment_defaults[ 'comment_notes_after' ] ) ) {
			$arr_comment_defaults[ 'comment_notes_after' ] .= $html;

			return $arr_comment_defaults;
		} else { // for custom hook in theme
			$arr_comment_defaults = $html;

			echo $arr_comment_defaults;
		}

		return NULL;
	}

	/**
	 * Return Message on inactive checkbox
	 * Use filter akismet_privacy_error_message for change text or markup
	 *
	 * @uses   wp_die
	 * @access public
	 * @since  0.0.2
	 * @return void
	 */
	public function error_message() {

		if ( is_user_logged_in() ) {
			return NULL;
		}

		// $locale = isset( $_GET[ 'translation' ]) ? $_GET[ 'lang'] : get_locale();
		// $this->options = get_option( 'akismet_privacy_notice_settings_' . $this->translation );
		if ( empty( $this->options[ 'error_message' ] ) ) {
			$this->options[ 'error_message' ] = __( $this->error_message );
		}

		// check for checkbox active
		if ( isset( $_POST[ 'comment' ] ) && ( ! isset( $_POST[ 'akismet_privacy_check' ] ) ) ) {
			$message = apply_filters( 'akismet_privacy_error_message', $this->options[ 'error_message' ] );
			wp_die( $message );
		}
	}

	/**
	 * Echo style in wp_head
	 *
	 * @uses   get_option, plugin_action_links, plugin_basename
	 * @access public
	 * @since  0.0.2
	 * @return string $links
	 */
	public function add_style() {

		if ( is_user_logged_in() ) {
			return NULL;
		}

		// $locale = isset( $_GET[ 'translation' ]) ? $_GET[ 'lang'] : get_locale();
		// $this->options = get_option( 'akismet_privacy_notice_settings_' . $this->translation );
		if ( empty( $this->options[ 'style' ] ) ) {
			$this->options[ 'style' ] = $this->style;
		}

		echo '<style type="text/css" media="screen">' . $this->options[ 'style' ] . '</style>';
	}

	/**
	 * Add settings link on plugins.php in backend
	 *
	 * @uses   plugin_basename
	 * @access public
	 *
	 * @param array $links , string $file
	 *
	 * @param       $file
	 *
	 * @since  0.0.2
	 * @return string $links
	 */
	public function plugin_action_links( $links, $file ) {

		if ( plugin_basename( dirname( __FILE__ ) . '/akismet-privacy-policies.php' ) == $file ) {
			$links[ ] = '<a href="options-general.php?page=akismet_privacy_notice_settings_group">' . __(
					'Settings'
				) . '</a>';
		}

		return $links;
	}

	/**
	 * Add settings page in WP backend
	 *
	 * @uses   add_options_page
	 * @access public
	 * @since  0.0.2
	 * @return void
	 */
	public function add_settings_page() {

		add_options_page(
			'Akismet Privacy Policies Settings',
			'Akismet Privacy Policies',
			'manage_options',
			'akismet_privacy_notice_settings_group',
			array( $this, 'get_settings_page' )
		);

		add_action( 'contextual_help', array( $this, 'contextual_help' ), 10, 2 );
	}

	/**
	 * Return form and markup on settings page
	 *
	 * @uses   settings_fields, normalize_whitespace
	 * @access public
	 * @since  0.0.2
	 * @return void
	 */
	public function get_settings_page() {

		?>
		<div class="wrap">
			<h2><?php echo $this->get_plugin_data( 'Name' ); ?></h2>
			<?php /* echo $this->notice */ ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'akismet_privacy_notice_settings_group' );
				// $locale = isset( $_GET[ 'translation' ] ) ? $_GET[ 'translation' ] : get_user_locale();
				// echo "sprache im formular: $this->translation\n";
				// $this->options = get_option( 'akismet_privacy_notice_settings_' . $this->translation );
				// echo "<pre>"; print_r($this->options); echo "</pre>";
				// echo "<pre>akismet_privacy_notice_settings_$this->translation - before: "; print_r($this->options); echo "</pre>";
				if ( ! isset( $this->options[ 'checkbox' ] ) || empty( $this->options[ 'checkbox' ] ) && 0 != $this->options[ 'checkbox' ] ) {
					$this->options[ 'checkbox' ] = $this->checkbox;
				}
				if ( empty( $this->options[ 'notice' ] ) ) {
					$this->options[ 'notice' ] = normalize_whitespace( __( $this->notice ) );
				}
				if ( empty( $this->options[ 'error_message' ] ) ) {
					$this->options[ 'error_message' ] = normalize_whitespace( __( $this->error_message ) );
				}
				if ( empty( $this->options[ 'style' ] ) ) {
					$this->options[ 'style' ] = normalize_whitespace( $this->style );
				}
				// echo "<pre>akismet_privacy_notice_settings_$this->translation - after: "; print_r($this->options); echo "</pre>";
				?>

				<table class="form-table">
					<tbody>
						<!-- language of the setting will always be the current WP locale, don't know why... -->
						<!-- <tr valign="top">
							<th scope="row"><label for="select_translation_language"><?php _e( '&Uuml;bersetzungssprache ausw&auml;hlen' ) ?></label></th>
							<td>
								<select id="select_translation_language" name="akismet_privacy_notice_settings_<?php echo $this->translation ?>[language]">
									<option value="de_DE" <?php selected( $this->translation, 'de_DE' ) ?>>Deutsch</option>
									<option value="en_US" <?php selected( $this->translation, 'en_US' ) ?>>English (US)</option>
								</select>
							</td>
						</tr> -->
					<tr valign="top">
						<th scope="row"><label for="akismet_privacy_checkbox"><?php _e( "Aktives Pr&uuml;fen via Checkbox", "akismet-privacy-policies" ) ?></label></th>
						<td>
							<input type="checkbox" id="akismet_privacy_checkbox" name="akismet_privacy_notice_settings_<?php echo $this->translation ?>[checkbox]" value="1"
								<?php if ( isset( $this->options[ 'checkbox' ] ) ) {
									checked( '1', $this->options[ 'checkbox' ] );
								} ?> />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="akismet_privacy_notice"><?php _e( "Datenschutzrechtlicher Hinweis", "akismet-privacy-policies" ) ?></label></th>
						<td>
							<textarea id="akismet_privacy_notice" name="akismet_privacy_notice_settings_<?php echo $this->translation ?>[notice]" cols="80" rows="10"
								aria-required="true"><?php if ( isset( $this->options[ 'notice' ] ) ) {
									echo $this->options[ 'notice' ];
								} ?></textarea>
							<br /><?php _e( '<strong>Hinweis:</strong> HTML m&ouml;glich', 'akismet-privacy-policies' ) ?>
							<br /><?php _e( '<strong>Achtung:</strong> Im Hinweistext musst du manuell den Link zu deiner Datenschutzerkl&auml;rung einf&uuml;gen. Einen Mustertext f&uuml;r die Datenschutzerkl&auml;rung findest du im Reiter &quot;Hilfe&quot;, rechts oben auf dieser Seite.', "akismet-privacy-policies" ) ?>
							<br /><strong><?php _e( 'Beispiel:', 'akismet-privacy-policies' ) ?></strong> <?php echo esc_html( __( $this->notice ) ); ?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="akismet_privacy_error_message"><?php _e( "Fehler-Hinweis", "akismet-privacy-policies" ) ?></label></th>
						<td>
							<textarea id="akismet_privacy_error_message" name="akismet_privacy_notice_settings_<?php echo $this->translation ?>[error_message]" cols="80"
								rows="10" aria-required="true"><?php if ( isset( $this->options[ 'error_message' ] ) ) {
									echo $this->options[ 'error_message' ];
								} ?></textarea>
							<br /><?php _e( "<strong>Hinweis:</strong> HTML m&ouml;glich", "akismet-privacy-policies" ) ?>
							<br /><strong><?php _e( "Beispiel:", "akismet-privacy-policies" ) ?></strong> <?php echo esc_html( __( $this->error_message ) ); ?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="akismet_privacy_style">Stylesheet</label></th>
						<td><textarea id="akismet_privacy_style" name="akismet_privacy_notice_settings_<?php echo $this->translation ?>[style]" cols="80"
								rows="10" aria-required="true"><?php if ( isset( $this->options[ 'style' ] ) ) {
									echo $this->options[ 'style' ];
								} ?></textarea>
							<br /><?php _e("<strong>Hinweis:</strong> CSS notwendig", "akismet-privacy-policies") ?>
							<br /><strong><?php _e("Beispiel:", "akismet-privacy-policies") ?></strong> <?php echo esc_html( $this->style ); ?>
						</td>
					</tr>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( '&Auml;nderungen speichern', "akismet-privacy-policies" ) ?>" />
				</p>

				<?php _e( '
				<p>Weitere Informationen zum Thema findest du in
					<a href="http://faq.wpde.org/hinweise-zum-datenschutz-beim-einsatz-von-akismet-in-deutschland/">der WordPress Deutschland FAQ</a>. Dieses Plugin wurde entwickelt von der
					<a href="http://inpsyde.com/" title="Besuch die Homepage der Inpsyde GmbH">Inpsyde GmbH</a> mit rechtlicher Unterst&uuml;tzung durch die Rechtsanwaltskanzlei
					<a href="http://spreerecht.de/" title="Besuch die Homepage der Kanzlei Schwenke und Dramburg">SCHWENKE &amp; DRAMBURG.</a>
				</p>
				', "akismet-privacy-policies" ) ?>
			</form>
		</div>
	<?php
	}

	/**
	 * Validate settings for options
	 *
	 * @uses   normalize_whitespace
	 * @access public
	 *
	 * @param array $value
	 *
	 * @since  0.0.2
	 * @return string $value
	 */
	public function validate_settings( $value ) {
		echo "<pre>validate_settings: "; var_dump($value); echo "</pre>";
		if ( isset( $value[ 'checkbox' ] ) && 1 == $value[ 'checkbox' ] ) {
			$value[ 'checkbox' ] = 1;
		} else {
			$value[ 'checkbox' ] = 0;
		}
		$value[ 'notice' ]        = normalize_whitespace( $value[ 'notice' ] );
		$value[ 'error_message' ] = normalize_whitespace( $value[ 'error_message' ] );
		$value[ 'style' ]         = normalize_whitespace( $value[ 'style' ] );

		return $value;
	}

	/**
	 * Register settings for options
	 *
	 * @uses   register_setting
	 * @access public
	 * @since  0.0.2
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'akismet_privacy_notice_settings_group', 'akismet_privacy_notice_settings_' . $this->translation,
			array( 'sanitize_callback' => array( $this, 'validate_settings' ) )
		);
	}

	/**
	 * Unregister and delete settings; clean database
	 *
	 * @uses   unregister_setting, delete_option
	 * @access public
	 * @since  0.0.2
	 * @return void
	 */
	public function unregister_settings() {
		// unregister_setting( 'akismet_privacy_notice_settings_group', 'akismet_privacy_notice_settings' );
		$all_options = wp_load_alloptions();
		$to_be_deleted = preg_grep('/^akismet_privacy_notice_settings(_)*[a-z]*(_)*[A-Z]*$/', array_keys($all_options));
		foreach( $to_be_deleted as $option ) {
			unregister_setting( 'akismet_privacy_notice_settings_group', $option );
			delete_option( $option );
		}
	}

	/**
	 * Add help text
	 *
	 * @uses     normalize_whitespace
	 *
	 * @param string $contextual_help
	 * @param string $screen_id
	 *
	 * @internal param string $screen
	 *
	 * @since    0.0.2
	 * @return string $contextual_help
	 */
	public function contextual_help( $contextual_help, $screen_id ) {

		if ( 'settings_page_akismet_privacy_notice_settings_group' !== $screen_id ) {
			return $contextual_help;
		}

		$contextual_help =
			'<p>' . __(
				'Das Plugin erg&auml;nzt das Kommentarformular um datenschutzrechtliche Hinweise,
				die erforderlich sind, wenn du das Plugin Akismet einsetzt.', "akismet-privacy-policies"
			) . '</p>'
			. '<ul>'
			. '<li>' . __(
				'Du kannst diverse Einstellungen vornehmen, nutze dazu die M&ouml;glichkeiten innerhalb der Einstellungen.', "akismet-privacy-policies"
			) . '</li>'
			. '<li>' . __( 'Eingeloggte Anwender sehen den Hinweis am Kommentarformular nicht.', "akismet-privacy-policies" ) . '</li>'
			. '<li><strong>' . __(
				'Im Hinweistext musst du den Link zu deiner Datenschutzerkl&auml;rung manuell einf&uuml;gen.'
			) . '</strong></li>'
			. '<li>' . __(
				'F&uuml;r die Datenschutzerkl&auml;rung kannst du folgende Vorlage verwenden: <br/>', "akismet-privacy-policies") .
__( '<code>&lt;strong&gt;Akismet Anti-Spam&lt;/strong&gt;', "akismet-privacy-policies" ) .
__( 'Diese Seite nutzt das&nbsp;&lt;a href="http://akismet.com/"&gt;Akismet</a>-Plugin der&nbsp;&lt;a href="http://automattic.com/"&gt;Automattic&lt;/a&gt; Inc., 60 29th Street #343, San Francisco, CA 94110-4929, USA. Mit Hilfe dieses Plugins werden Kommentare von echten Menschen von Spam-Kommentaren unterschieden. Dazu werden alle Kommentarangaben an einen Server in den USA verschickt, wo sie analysiert und f&uuml;r Vergleichszwecke vier Tage lang gespeichert werden. Ist ein Kommentar als Spam eingestuft worden, werden die Daten &uuml;ber diese Zeit hinaus gespeichert. Zu diesen Angaben geh&ouml;ren der eingegebene Name, die Emailadresse, die IP-Adresse, der Kommentarinhalt, der Referrer, Angaben zum verwendeten Browser sowie dem Computersystem und die Zeit des Eintrags. Sie k&ouml;nnen gerne Pseudonyme nutzen, oder auf die Eingabe des Namens oder der Emailadresse verzichten. Sie k&ouml;nnen die &uuml;bertragung der Daten komplett verhindern, in dem Sie unser Kommentarsystem nicht nutzen. Das w&auml;re schade, aber leider sehen wir sonst keine Alternativen, die ebenso effektiv arbeiten. Sie k&ouml;nnen der Nutzung Ihrer Daten f&uuml;r die Zukunft unter&nbsp;&lt;a href="mailto:support@wordpress.com" target="_blank"&gt;support@wordpress.com&lt;/a&gt;, Betreff "Deletion of Data stored by Akismet" unter Angabe/Beschreibung der gespeicherten Daten&nbsp;widersprechen.</code>', "akismet-privacy-policies"
			) . '</li>'
			. '<li>' . __(
				'Weitere Informationen zum Thema findest du in <a href="http://faq.wpde.org/hinweise-zum-datenschutz-beim-einsatz-von-akismet-in-deutschland/">diesem Artikel der WordPress Deutschland FAQ</a>', "akismet-privacy-policies"
			) . '</li>'
			. '<li>' . __(
				'Dieses Plugin wurde entwickelt von der <a href="http://inpsyde.com/" title="Besuch die Homepage der Inpsyde GmbH">Inpsyde GmbH</a> mit rechtlicher Unterst&uuml;tzung durch die Rechtsanwaltskanzlei <a href="http://spreerecht.de/" title="Besuch die Homepage der Kanzlei Schwenke und Dramburg">SCHWENKE &amp; DRAMBURG</a>.', "akismet-privacy-policies"
			) . '</li>'
			. '</ul>';

		return normalize_whitespace( $contextual_help );
	}

} // end class

if ( function_exists( 'add_action' ) && class_exists( 'Akismet_Privacy_Policies' ) ) {
	add_action( 'plugins_loaded', array( 'Akismet_Privacy_Policies', 'get_object' ) );
} else {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
