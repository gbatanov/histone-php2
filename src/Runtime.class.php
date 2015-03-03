<?php

class Histone_Runtime {

	private $thisObj;
	private $baseURI;
	private $scopes = array();

	public function __construct($baseURI, $thisObj) {
		$this->pushScope(0);
		$this->baseURI = $baseURI;
		$this->thisObj = $thisObj;
	}

	public function __destruct() {
		$this->popScope(0);
	}

	public function getThis() {
		return $this->thisObj;
	}

	public function pushScope($scope) {
		$scopes = &$this->scopes;
		if (!array_key_exists($scope, $scopes)) {
			$scopes[$scope] = array();
		}
		array_unshift($scopes[$scope], array());
	}

	public function getVarIndex() {
		$scopes = $this->scopes;
		return count($scopes[count($scopes) - 1][0]);
	}

	public function setVar($value, $index) {
		$scopes = &$this->scopes;
		$scopes[count($scopes) - 1][0][$index] = $value;
	}

	public function &getVar($scope, $index) {
		return $this->scopes[$scope][0][$index];
	}

	public function popScope($scope) {
		array_pop($this->scopes[$scope]);
		if (!count($this->scopes[$scope])) {
			unset($this->scopes[$scope]);
		}
	}

}