<?php

class Histone_Method {

	private $thisObj;
	private $method;

	function __construct($thisObj, $method) {
		$this->thisObj = $thisObj;
		$this->method = $method;
	}

	function call($args, $runtime) {
		return call_user_func(
			$this->method,
			$this->thisObj,
			$args, $runtime
		);
	}

}


Histone_RTTI::registerType('Histone_Method');
Histone_RTTI::registerMember('Histone_Method', 'toString', function() { return '[Histone_Method]'; });
Histone_RTTI::registerMember('Histone_Method', 'toBoolean', function() { return true; });
Histone_RTTI::registerMember('Histone_Method', 'isCallable', function() { return true; });
Histone_RTTI::registerMember('Histone_Method', 'toJSON', function() { return '[Histone_Method]'; });