<?php

	Histone_RTTI::registerMember('string', 'isString', function() { return true; });
	Histone_RTTI::registerMember('string', 'toString', function($self) { return $self; });
	Histone_RTTI::registerMember('string', 'toBoolean', function($self) { return strlen($self) > 0; });
	Histone_RTTI::registerMember('string', 'toJSON', function($self) { return json_encode($self); });

	Histone_RTTI::registerMember('string', '__get', function($self, $index) {
		if (is_numeric($index)) {
			$length = strlen($self);
			if ($index < 0) $index = $length + $index;
			if ((int)$index != $index ||
				$index < 0 || $index >= $length) {
				return Histone_RTTI::getUndefined();
			} else return $self[$index];
		} else return Histone_RTTI::getUndefined();
	});

	Histone_RTTI::registerMember('string', 'size', function($self) {
		return strlen($self);
	});

	Histone_RTTI::registerMember('string', 'split', function($self, $args) {
		$separator = $args[0];
		if (!is_string($separator)) $separator = '';
		if (strlen($separator) === 0) return str_split($self);
		return explode($separator, $self);
	});

	Histone_RTTI::registerMember('string', 'charCodeAt', function($self, $args) {
		$index = $args[0];
		if (!is_numeric($index)) $index = 0;
		if ($index >= 0 && $index < strlen($self))
			return ord($self[$index]);
		return Histone_RTTI::getUndefined();
	});

	Histone_RTTI::registerMember('string', 'indexOf', function($self, $args) {

		$count = count($args);
		if ($count === 0) { $search = 0; $start = 0; }
		else if ($count === 1) { $search = $args[0]; $start = 0; }
		else { $search = $args[0]; $start = $args[1]; }

		if (!is_string($search)) return -1;
		if (strlen($search) === 0) return -1;
		if (!is_int($start)) $start = 0;
		$strLen = strlen($self);
		if ($start < 0) $start = $strLen + $start;
		if ($start < 0) $start = 0;
		if ($start >= $strLen) return -1;
		$result = strpos($self, $search, $start);
		if ($result === false) $result = -1;
		return $result;
	});

	Histone_RTTI::registerMember('string', 'lastIndexOf', function($self, $args) {


		$count = count($args);
		if ($count === 0) { $search = 0; $start = 0; }
		else if ($count === 1) { $search = $args[0]; $start = 0; }
		else { $search = $args[0]; $start = $args[1]; }

		if (strlen($self) === 0) return -1;
		if (!is_string($search)) return -1;
		if (strlen($search) === 0) return -1;

		if (is_int($start)) {
			if ($start <= 0) {
				$start = strlen($self) + $start;
				if ($start <= 0) return -1;
			}
			$self = substr($self, 0, $start);
		}

		$pos = strpos(strrev($self), strrev($search));
		if ($pos === false) return -1;
		return strlen($self) - $pos - strlen($search);
	});

	Histone_RTTI::registerMember('string', 'strip', function($self, $args) {
		$chars = '';
		while (count($args)) {
			$arg = array_shift($args);
			if (!is_string($arg)) continue;
			$chars .= $arg;
		}
		if ($chars) return trim($self, $chars);
		return trim($self);
	});

	Histone_RTTI::registerMember('string', 'slice', function($self, $args) {
		$strLen = strlen($self);
		$start = (isset($args[0])) ? (int) $args[0] : 0;
		$length = (isset($args[1])) ? (int) $args[1] : $strLen;
		if ($start < 0) $start = $strLen + $start;
		if ($start < 0) $start = 0;
		if ($start >= $strLen) return '';
		if ($length === 0) $length = $strLen - $start;
		if ($length < 0) $length = $strLen - $start + $length;
		if ($length <= 0) return '';
		return substr($self, $start, $length);
	});

	Histone_RTTI::registerMember('string', 'toNumber', function($self) {
		if (is_numeric(trim($self)))
			return floatval($self);
		return Histone_RTTI::getUndefined();
	});

	Histone_RTTI::registerMember('string', 'toLowerCase', function($self) {
		return mb_strtolower($self, 'utf-8');
	});

	Histone_RTTI::registerMember('string', 'toUpperCase', function($self) {
		return mb_strtoupper($self, 'utf-8');
	});

?>