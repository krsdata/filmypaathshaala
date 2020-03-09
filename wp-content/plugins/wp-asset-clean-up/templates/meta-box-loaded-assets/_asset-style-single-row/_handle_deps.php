<?php
/*
 * The file is included from /templates/meta-box-loaded-assets/_asset-style-single-row.php
*/
if (! isset($data)) {
	exit; // no direct access
}

if (isset($data['row']['obj']->deps) && ! empty($data['row']['obj']->deps)) {
	$depsOutput = '';

	if (is_array($data['row']['obj']->deps)) {
		$dependsOnText = (count($data['row']['obj']->deps) === 1)
			? __('"Child" of one "parent" CSS file:')
			: sprintf(__('"Child" of %s CSS "parent" files:', 'wp-asset-clean-up'),
				count($data['row']['obj']->deps));
	} else {
		$dependsOnText = __('"Child" of "parent" CSS file(s):', 'wp-asset-clean-up');
	}

	$depsOutput .= $dependsOnText.' ';

	foreach ($data['row']['obj']->deps as $depHandle) {
		$depsOutput .= '<span style="color: green; font-weight: 300;">'.$depHandle.'</span>, ';
	}

	$depsOutput = rtrim($depsOutput, ', ');

	$extraInfo[] = $depsOutput;
}
