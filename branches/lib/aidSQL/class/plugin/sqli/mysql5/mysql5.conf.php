<?php

	$config = array(
		"mysql5-numeric-only"=>array(
			"overlaps-with"=>array("mysql5-strings-only")
		),
		"mysql5-strings-only"=>array(
			"overlaps-with"=>("mysql5-numeric-only")
		),
		"mysql5-field-payloads"=>array(
		),
		"mysql5-ending-payloads"=>array(
		),
		"mysql5-comment-payloads"=>array(
		),
		"mysql5-start-offset"=>array(
		)
	);

?>
