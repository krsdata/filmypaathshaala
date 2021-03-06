<?php
if (
    isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) &&
    $_SERVER["HTTP_X_FORWARDED_PROTO"] == "https"
) {
    $_SERVER["HTTPS"] = "on";
}

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
define( 'DB_NAME', 'filmy' );

/** MySQL database username */
define( 'DB_USER', 'filmy' );

/** MySQL database password */
define( 'DB_PASSWORD', 'filmy@123!' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'b|t.LHEu68Voc?J-_,IK[rNJ}7I#c]~ueD9.>+%]#`MoZi9esRAf!v-!hzLGd~%<' );
define( 'SECURE_AUTH_KEY',  'C&UB^U/~d6b2J#g<;vY$W1g+GEpjj*tW gl/OjO6$]S*I3LQvL>YR$~BGSrRMdjY' );
define( 'LOGGED_IN_KEY',    '*d*W`^-PC)w36MVq.?#p)ZduC;P+J*JE!9m0|NHSCfy#L(YVrm8e<)%wL>eRo7m<' );
define( 'NONCE_KEY',        'C1u|fQPrw7d8m8u+nEMq});b:bb>+Hol<PA]t4Yk /CQD`l;Q.|3~L5]&P[VXq~L' );
define( 'AUTH_SALT',        ':KG bN/dcBb+s`B8c,t395uZ()hRCe ])bgAR1y826vom&S|KKsGjpw1# 7`[7ae' );
define( 'SECURE_AUTH_SALT', '](W);N<*?$a9{CtwI%Z,>_B;1e^+tPpmcXAY<-j?x~cP$r=5rkeAJi$HbeDfwW49' );
define( 'LOGGED_IN_SALT',   '5T moG?w Hcf+:GR6IdfG=Z{lpH9r~hKrz6:4V6ELLdXMMS0J ,(feN|bZs5@!Fh' );
define( 'NONCE_SALT',       'dYsL~%YY)(bRa^QO^{vjb:X/Z*]3[>m6]xt.WIXb8* EXkBaDzpQvz9jZqg%66cW' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );

