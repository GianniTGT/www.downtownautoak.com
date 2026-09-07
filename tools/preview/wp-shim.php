<?php
/**
 * A very small WordPress, enough to render this theme's front-end templates
 * outside WordPress.
 *
 * Why it exists: the brief says to verify on the rendered page rather than from
 * the theme source, and a page can only be looked at once something has drawn
 * it. This harness draws every template against fixture data, which catches the
 * two failures that reading PHP never does — a template that fatals, and a
 * layout that falls apart at 390px.
 *
 * It is not a test of WordPress. It stubs the functions the front end calls and
 * nothing more; anything it cannot answer honestly it answers emptily, which is
 * exactly how the templates are meant to behave when a setting is unanswered.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );

$GLOBALS['daak_options'] = array();
$GLOBALS['daak_posts']   = array();
$GLOBALS['daak_loop']    = array( 'posts' => array(), 'i' => -1 );
$GLOBALS['daak_current'] = null;
$GLOBALS['daak_ctx']     = array( 'singular' => '', 'archive' => false );

/* ------------------------------------------------------------- fixtures */

function daak_fixture_option( $key, $value ) { $GLOBALS['daak_options'][ $key ] = $value; }

function daak_fixture_post( $args ) {
	$id = count( $GLOBALS['daak_posts'] ) + 1;
	$GLOBALS['daak_posts'][ $id ] = (object) array_merge( array(
		'ID'           => $id,
		'post_type'    => 'page',
		'post_title'   => '',
		'post_name'    => '',
		'post_content' => '',
		'post_status'  => 'publish',
		'menu_order'   => 0,
		'meta'         => array(),
		'thumb'        => '',
	), $args );
	$GLOBALS['daak_posts'][ $id ]->ID = $id;
	return $id;
}

function daak_set_loop( $posts, $ctx = array() ) {
	$GLOBALS['daak_loop'] = array( 'posts' => $posts, 'i' => -1 );
	$GLOBALS['daak_ctx']  = array_merge( array( 'singular' => '', 'archive' => false ), $ctx );
}

/* --------------------------------------------------------- hooks (inert) */

function add_action( $hook, $cb = null, $p = 10, $a = 1 ) {}
function add_filter( $hook, $cb = null, $p = 10, $a = 1 ) {}
function do_action( $hook ) {}
function apply_filters( $hook, $value ) { return $value; }
function add_theme_support() {}
function add_image_size() {}
function register_nav_menus() {}
function register_post_type() {}
function register_post_meta() {}
function register_rest_route() {}
function add_menu_page() {}
function add_submenu_page() {}
function wp_enqueue_style() {}
function wp_enqueue_script() {}
function wp_localize_script() {}
function remove_action() {}
function load_theme_textdomain() {}
function wp_create_nonce() { return 'preview'; }
function wp_nonce_field( $a = '', $n = '' ) { printf( '<input type="hidden" name="%s" value="preview">', htmlspecialchars( (string) $n ) ); }
function wp_verify_nonce() { return true; }
function current_user_can() { return false; }
function is_admin() { return false; }
function is_wp_error( $t ) { return false; }
function wp_cache_flush() {}
function flush_rewrite_rules() {}
function set_transient( $k, $v, $t = 0 ) {}
function get_transient( $k ) { return false; }
function delete_transient( $k ) {}
function wp_salt() { return 'preview'; }
function wp_mail() { return true; }
function wp_insert_post( $a, $e = false ) { return daak_fixture_post( $a ); }
function wp_update_nav_menu_item() {}
function wp_create_nav_menu() { return 1; }
function set_theme_mod() {}
function get_theme_mod( $k, $d = false ) { return $d; }
function has_custom_logo() { return false; }
function has_nav_menu() { return false; }
function wp_get_referer() { return '/'; }
function wp_safe_redirect() {}
function wp_get_upload_dir() { return array( 'basedir' => sys_get_temp_dir() ); }
function trailingslashit( $s ) { return rtrim( (string) $s, '/\\' ) . '/'; }
function wp_parse_args( $a, $d = array() ) { return array_merge( $d, is_array( $a ) ? $a : array() ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function absint( $v ) { return abs( (int) $v ); }
function wp_unslash( $v ) { return $v; }

/* ------------------------------------------------------------ escaping */

function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url_raw( $s ) { return (string) $s; }
function esc_textarea( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function wp_kses_post( $s ) { return (string) $s; }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_textarea_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_email( $s ) { return (string) $s; }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
function sanitize_title( $s ) { return preg_replace( '/[^a-z0-9\-]/', '-', strtolower( (string) $s ) ); }
function checked( $a, $b = true, $echo = true ) { $r = (string) $a === (string) $b ? ' checked' : ''; if ( $echo ) { echo $r; } return $r; }
function selected( $a, $b = true, $echo = true ) { $r = (string) $a === (string) $b ? ' selected' : ''; if ( $echo ) { echo $r; } return $r; }

/* --------------------------------------------------------------- urls */

function home_url( $path = '/' ) { return rtrim( '.', '/' ) . $path; }
function admin_url( $path = '' ) { return '#admin/' . $path; }
function rest_url( $path = '' ) { return '/wp-json/' . $path; }
function get_privacy_policy_url() { return 'privacy.html'; }
function add_query_arg( $args, $url = '' ) {
	if ( ! is_array( $args ) ) { return $url; }
	$args = array_filter( $args, function ( $v ) { return '' !== $v && null !== $v; } );
	if ( ! $args ) { return $url; }
	return $url . ( str_contains( (string) $url, '?' ) ? '&' : '?' ) . http_build_query( $args );
}
function remove_query_arg( $keys, $url = '' ) { return $url; }

/**
 * Permalinks map onto the files this harness writes, so the rendered pages link
 * to each other and a click in the screenshot goes where it says it does.
 */
function get_permalink( $post = null ) {
	$p = daak_resolve( $post );
	if ( ! $p ) { return '#'; }
	if ( 'daak_vehicle' === $p->post_type ) { return 'vehicle-' . $p->ID . '.html'; }
	$map = array( 'home' => 'home.html', 'where-you-can-drive' => 'drive.html', 'rates' => 'rates.html', 'winter-driving' => 'winter.html', 'rent-to-own' => 'rent-to-own.html', 'about' => 'contact.html', 'policies' => 'policies.html', 'privacy' => 'privacy.html' );
	return $map[ $p->post_name ] ?? ( $p->post_name . '.html' );
}
function get_post_type_archive_link( $type ) { return 'fleet.html'; }
function get_edit_post_link( $id ) { return '#'; }

/* -------------------------------------------------------------- posts */

function daak_resolve( $post ) {
	if ( is_object( $post ) ) { return $post; }
	if ( is_numeric( $post ) ) { return $GLOBALS['daak_posts'][ (int) $post ] ?? null; }
	return $GLOBALS['daak_current'];
}
function get_option( $key, $default = false ) { return $GLOBALS['daak_options'][ $key ] ?? $default; }
function update_option( $key, $value ) { $GLOBALS['daak_options'][ $key ] = $value; return true; }
function get_post_meta( $id, $key = '', $single = false ) {
	$p = daak_resolve( $id );
	if ( ! $p ) { return ''; }
	return $p->meta[ $key ] ?? '';
}
function update_post_meta( $id, $key, $value ) { $p = daak_resolve( $id ); if ( $p ) { $p->meta[ $key ] = $value; } return true; }
function get_post_type( $id = null ) { $p = daak_resolve( $id ); return $p ? $p->post_type : false; }
function get_the_title( $id = null ) { $p = daak_resolve( $id ); return $p ? $p->post_title : ''; }
function get_the_content( $a = null, $b = false, $post = null ) { $p = daak_resolve( $post ); return $p ? $p->post_content : ''; }
function get_the_excerpt( $post = null ) { $p = daak_resolve( $post ); return $p ? wp_trim_words( strip_tags( $p->post_content ), 30 ) : ''; }
function wp_trim_words( $text, $n = 30, $more = '' ) { $w = preg_split( '/\s+/', trim( (string) $text ) ); return implode( ' ', array_slice( $w, 0, $n ) ) . ( count( $w ) > $n ? $more : '' ); }
function get_page_by_path( $slug ) {
	foreach ( $GLOBALS['daak_posts'] as $p ) { if ( $p->post_name === $slug ) { return $p; } }
	return null;
}
function get_posts( $args = array() ) {
	$type = $args['post_type'] ?? 'post';
	$out  = array();
	foreach ( $GLOBALS['daak_posts'] as $p ) { if ( $p->post_type === $type ) { $out[] = $p; } }
	usort( $out, function ( $a, $b ) { return $a->menu_order <=> $b->menu_order ?: strcmp( $a->post_title, $b->post_title ); } );
	return $out;
}

function have_posts() { return $GLOBALS['daak_loop']['i'] + 1 < count( $GLOBALS['daak_loop']['posts'] ); }
function the_post() {
	$GLOBALS['daak_loop']['i']++;
	$GLOBALS['daak_current'] = $GLOBALS['daak_loop']['posts'][ $GLOBALS['daak_loop']['i'] ];
}
function rewind_posts() { $GLOBALS['daak_loop']['i'] = -1; }
function get_the_ID() { return $GLOBALS['daak_current'] ? $GLOBALS['daak_current']->ID : 0; }
function the_title() { echo esc_html( get_the_title() ); }
function the_content() { echo $GLOBALS['daak_current'] ? $GLOBALS['daak_current']->post_content : ''; }
function the_posts_pagination() {}
function has_post_thumbnail( $id = null ) { $p = daak_resolve( $id ); return $p && $p->thumb; }
function get_the_post_thumbnail_url( $id = null, $size = '' ) { $p = daak_resolve( $id ); return $p ? $p->thumb : ''; }
function get_the_post_thumbnail( $id = null, $size = '', $attr = array() ) {
	$p = daak_resolve( $id );
	if ( ! $p || ! $p->thumb ) { return ''; }
	return sprintf( '<img src="%s" alt="%s" loading="lazy">', esc_url( $p->thumb ), esc_attr( $attr['alt'] ?? '' ) );
}
function the_post_thumbnail( $size = '', $attr = array() ) { echo get_the_post_thumbnail( null, $size, $attr ); }
function wp_get_attachment_image( $id, $size = '', $icon = false, $attr = array() ) { return ''; }
function wp_get_attachment_image_url( $id, $size = '' ) { return ''; }
function get_search_query() { return ''; }

function is_singular( $type = '' ) { return $type ? $GLOBALS['daak_ctx']['singular'] === $type : (bool) $GLOBALS['daak_ctx']['singular']; }
function is_page() { return 'page' === $GLOBALS['daak_ctx']['singular']; }
function is_front_page() { return ! empty( $GLOBALS['daak_ctx']['front'] ); }
function body_class( $extra = '' ) { echo 'class="preview ' . esc_attr( is_string( $extra ) ? $extra : '' ) . ( $GLOBALS['daak_ctx']['archive'] ? ' post-type-archive' : '' ) . '"'; }
function language_attributes() { echo 'lang="en-US"'; }
function bloginfo( $what ) { echo 'charset' === $what ? 'UTF-8' : ''; }

/* --------------------------------------------------------------- dates */

function wp_date( $format, $ts = null ) { return gmdate( $format, $ts ?? time() ); }
function current_time( $f ) { return gmdate( $f ); }

/* -------------------------------------------------- head, footer, parts */

function wp_head() {
	printf( '<link rel="stylesheet" href="%s">' . "\n", $GLOBALS['daak_css'] );
	// Barlow is served from the fonts directory here so a screenshot shows the
	// real typeface even when the renderer has no network.
	$local = __DIR__ . '/fonts/local.css';
	if ( file_exists( $local ) ) {
		echo '<link rel="stylesheet" href="../fonts/local.css">' . "\n";
	} else {
		echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Barlow+Condensed:wght@600;700;800&display=swap">' . "\n";
	}
	echo '<title>' . esc_html( $GLOBALS['daak_title'] ?? 'Preview' ) . '</title>' . "\n";
}
function wp_footer() { printf( '<script src="%s"></script>', $GLOBALS['daak_js'] ); }
function wp_body_open() {}
function get_header() { include DAAK_DIR . '/header.php'; }
function get_footer() { include DAAK_DIR . '/footer.php'; }
function get_search_form() { include DAAK_DIR . '/searchform.php'; }
