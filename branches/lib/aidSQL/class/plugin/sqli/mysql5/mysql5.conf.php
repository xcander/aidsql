<?php

	$config = array(
		"numeric-only"=>array(
			"overlaps-with"=>array("strings-only")
		),
		"strings-only"=>array(
			"overlaps-with"=>("numeric-only")
		),
		"field-payloads"=>array(
		),
		"ending-payloads"=>array(
		),
		"comment-payloads"=>array(
		),
		"start-offset"=>array(
		),
		"injection-attempts"=>array(
		),
		"var-count"=>array(
		)
	);

?>
