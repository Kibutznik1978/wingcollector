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

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wingcollector');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '~:`vLD<RUC(R ~,JgR(_sw4P3V~Z4 O!]:z-#`o.Ci|Vp]HEpC_/uv>W+*RN%o[Q');
define('SECURE_AUTH_KEY',  '^k$ZIl16JiQ|`mN.qi`3yCe.)-2A^-i&m=ilpoEmDhXT,<RULQZ&,M[q$`T fy2#');
define('LOGGED_IN_KEY',    'VC0xMtAeIaz%n>4p}$R`*oO@`b$c]f97vQy5ELSkz;-;bS=q,{TNl}O&{T>OxV!J');
define('NONCE_KEY',        'Yr~_V]oD]SQc]m!&%&<mac-YSGm>i`F.0sp`}rbFG.o5M)q([ytu-eL1bo58bQ<5');
define('AUTH_SALT',        'S?m3W5&+YtO3}o~E)bcoj~A48FFcy#S!q:JB9Vd_Ub3CCg$@Y$IHb[^t_i?wC Cv');
define('SECURE_AUTH_SALT', 'TrhweMF@9J$XF%xm5$Ga`e1z/JG|_5-T]D5ef!}O0C&U]oe`G]DO;GpJ`;H UEDH');
define('LOGGED_IN_SALT',   'NQ6Fh<ro|9fCWy/V*cH%k<XGnBGIiyVOdl8osdQ1l$vg6wm5uqVEQ(^L<4C `&,0');
define('NONCE_SALT',       '6/XH_G,_m2fCKwDMoMHVLf08ChqHtzDlP5h@bJQ--KDE$c/57-s.%oht?l$znR1w');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
