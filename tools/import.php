<?php
/**
 * This is an adaptation of pento's work to generat a translatable
 * list of twemojis.
 *
 * Credits: @pento on https://github.com/pento/react
 */
$contents = file_get_contents( 'https://raw.githubusercontent.com/iamcal/emoji-data/master/emoji.json' );

$data = json_decode( $contents );
$php  = '<?php
/**
 * Get Emojis in your language!
 *
 * @since 1.0.0
 */
function bp_reactions_get_emojis() {
	return array(
';

foreach ( $data as $emoji ) {
	// Exclude any not supported by Twemoji
	if ( empty( $emoji->has_img_twitter ) ) {
		continue;
	}

	$code = "0x" . $emoji->unified;
	$code = str_replace( '-', "-0x", $code );
	$code = explode( '-', $code );

	$php .= '		array( \'id\' => \'' . reset( $code ) . '\', \'name\' => _x( \'' . $emoji->short_name . '\', \'Emoji shortname\', \'bp-reactions\' ) ),' . "\n";
}

$php .= '	);
}
';

file_put_contents( dirname( __DIR__ ) . '/includes/emojis.php', $php );
