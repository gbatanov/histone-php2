<?php

	require_once(__DIR__ . '/../Constants.class.php');

	class Histone_RegExp {

		public $global;
		public $expression;

		function __construct($expr, $flags) {
			$this->expression = ('/' . $expr . '/');
			$this->global = ($flags & Histone_Constants::RE_GLOBAL);
			if ($flags & Histone_Constants::RE_MULTILINE) $this->expression .= 'm';
			if ($flags & Histone_Constants::RE_IGNORECASE) $this->expression .= 'i';
		}

	}

	Histone_RTTI::registerType('Histone_RegExp');

	Histone_RTTI::registerMember('Histone_RegExp', 'isRegExp', function() {
		return true;
	});

	Histone_RTTI::registerMember('Histone_RegExp', 'toString', function($self) {
		$expression = $self->expression;
		if ($self->global) $expression .= 'g';
		return $expression;
	});

	Histone_RTTI::registerMember('Histone_RegExp', 'toBoolean', function() {
		return true;
	});

	Histone_RTTI::registerMember('Histone_RegExp', 'toJSON', function($self) {
		$expression = $self->expression;
		if ($self->global) $expression .= 'g';
		return $expression;
	});

	Histone_RTTI::registerMember('Histone_RegExp', 'test', function($self, $args) {
		if (!is_string($subject = $args[0])) return false;
		if ($self->global)
			return (preg_match_all($self->expression, $subject) === 1);
		else return (preg_match($self->expression, $subject) === 1);
	});

?>