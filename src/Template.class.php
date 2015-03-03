<?php

require_once('Processor.class.php');

class Histone_Template {

	private $baseURI;
	private $template;

	function __construct($template, $baseURI) {
		$this->baseURI = $baseURI;
		$this->template = $template;
	}

	function getAST() {
		return $this->template;
	}

	function render($ret, $thisObj) {
		$ret(Histone_Processor::process($this->template, $thisObj));
	}

}