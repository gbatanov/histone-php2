<?php

require_once('Template.class.php');

class Histone {

	private static $resourceLoader;

	function __invoke($template, $baseURI = null) {

		if (!is_string($baseURI)) $baseURI = getcwd();

		if (is_string($template)) {
			require_once('parser/Parser.class.php');
			$template = Histone_Parser::parse($template, $baseURI);
			return new Histone_Template($template, $baseURI);
		}

		else if ($template instanceof Histone_Template) {
			$template = $template->getAST();
			return new Histone_Template($template, $baseURI);
		}

		else if (is_array($template)) {
			return new Histone_Template($template, $baseURI);
		}

	}

	static function setResourceLoader($resourceLoader) {
		if (!is_callable($resourceLoader)) return;
		self::$resourceLoader = $resourceLoader;
	}

	static function loadResource($resourceURI) {
		if (is_callable(self::$resourceLoader))
			return call_user_func(self::$resourceLoader, $resourceURI);
		return Histone_RTTI::getUndefined();
	}

}

$Histone = new Histone();