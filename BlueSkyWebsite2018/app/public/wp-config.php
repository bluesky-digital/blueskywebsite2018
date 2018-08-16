<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'root' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'UEtNBh2tpD2MBLATD0xaJMM6L8Y2geFZL0vvoVJLkKw3leKox6KiKGPPFognRhNYktLyQhCRJnsKO0Lw43zcTQ==');
define('SECURE_AUTH_KEY',  '7X9EPwII8CMubPEj7XGJqpQWfSdP9gdZO8JZxwtFfmQgv/lOise2Kg3Iyvj4L7jq2jD3BFUhBMb8rrQfD7XSIg==');
define('LOGGED_IN_KEY',    'KSre64da0ZpcM/Hd7pJTnpWhRDEFnKqYVFlkC/YnXmNtY0msXebb3QvVtOA6utNzwgvsMitjwULsck0z13AiFg==');
define('NONCE_KEY',        '57dfWwiYgdwbPfTHBQGb0TpKrO0K4glVvj/mlRBOxJL2Hh8bpa8RsxyZ3hx3vvLp+BR1hYynTCwJU53gFs6aFA==');
define('AUTH_SALT',        'fKubJJoYfFH1vBi1lV1ZvtNGM6LH/45e7b28cVUk8eA8Aje+tR7FZ/roUXtOWe503n89KGJoN90hqRrs2HpVcw==');
define('SECURE_AUTH_SALT', 'AfLZYuPv89q7LQ4zIYJZwepHuuQIl7CqVCoxn+E7b/a8XlGzf46lJfK8IrAvembAlx/ljSTyhHmERUVfXmbj3w==');
define('LOGGED_IN_SALT',   'N2MDeSHQLG5hezvyZn/CQeZqEAAkU1Lz+1weRdL/yylVpi8UbEB2LGT6JKlcmHZlc7JBstM9XMg1IRbBJR97+Q==');
define('NONCE_SALT',       'gMgRcjKdB6Zexf/tytQTL/Ze4/4ud3BPpRrvI7nx1YKC8uqmrY+D4ZNTmMz4qijjNihFDWVqnN9VwmjdPe+wBA==');

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';





/* Inserted by Local by Flywheel. See: http://codex.wordpress.org/Administration_Over_SSL#Using_a_Reverse_Proxy */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}

/* Inserted by Local by Flywheel. Fixes $is_nginx global for rewrites. */
if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) && strpos( $_SERVER['SERVER_SOFTWARE'], 'Flywheel/' ) !== false ) {
	$_SERVER['SERVER_SOFTWARE'] = 'nginx/1.10.1';
}

/* Inserted by Local by Flywheel. See: http://codex.wordpress.org/Administration_Over_SSL#Using_a_Reverse_Proxy */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}

/* Inserted by Local by Flywheel. Fixes $is_nginx global for rewrites. */
if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) && strpos( $_SERVER['SERVER_SOFTWARE'], 'Flywheel/' ) !== false ) {
	$_SERVER['SERVER_SOFTWARE'] = 'nginx/1.10.1';
}

/* Inserted by Local by Flywheel. See: http://codex.wordpress.org/Administration_Over_SSL#Using_a_Reverse_Proxy */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}

/* Inserted by Local by Flywheel. Fixes $is_nginx global for rewrites. */
if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) && strpos( $_SERVER['SERVER_SOFTWARE'], 'Flywheel/' ) !== false ) {
	$_SERVER['SERVER_SOFTWARE'] = 'nginx/1.10.1';
}
/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) )
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
