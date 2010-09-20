<?php

	//Interfaces
	require_once "interface/Parser.interface.php";

	//Parsers
	require_once "class/parser/Generic.parser.php";
	require_once "class/parser/TagMatcher.parser.php";

	$openTag  = "i215";
	$closeTag = "a203";

	$content = "<html><head><title>Test</title><body>${openTag}MATCH${closeTag}${openTag}MATCH${closeTag}</body></html>";

	$tagMatch = new aidSQL\parser\TagMatcher($content);
	$tagMatch->setOpenTag($openTag);
	$tagMatch->setCloseTag($closeTag);
	var_dump($tagMatch->getResult());


?>
