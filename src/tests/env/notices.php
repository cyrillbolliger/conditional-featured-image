<?php
/**
 * Must-use plugin of the test site (.wp-env.json maps it): prints the
 * deprecation and misuse notices the plugin causes.
 *
 * Core raises these from wp-includes/functions.php, so its own message names
 * no file of the plugin. This one names the plugin's file that made the call,
 * in the format tests/e2e/site.js looks for. Other plugins' notices stay out.
 */

$cybocfi_report = function ( $kind, $subject ) {
	foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) as $frame ) {
		if ( isset( $frame['file'] ) && false !== strpos( $frame['file'], '/plugins/conditional-featured-image/' ) ) {
			echo "<br><b>$kind</b>: " . esc_html( $subject ) . " called in {$frame['file']} on line {$frame['line']}<br>";

			return;
		}
	}
};

foreach (
	array(
		'deprecated_function_run'    => 'Deprecated',
		'deprecated_constructor_run' => 'Deprecated',
		'deprecated_class_run'       => 'Deprecated',
		'deprecated_file_included'   => 'Deprecated',
		'deprecated_argument_run'    => 'Deprecated',
		'deprecated_hook_run'        => 'Deprecated',
		'doing_it_wrong_run'         => 'Notice',
	) as $cybocfi_action => $cybocfi_kind
) {
	add_action(
		$cybocfi_action,
		function ( $subject ) use ( $cybocfi_report, $cybocfi_kind ) {
			$cybocfi_report( $cybocfi_kind, $subject );
		}
	);
}
