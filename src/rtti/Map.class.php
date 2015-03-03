<?php

	Histone_RTTI::registerMember('map', 'isMap', function() { return true; });

	Histone_RTTI::registerMember('map', 'toString', function($self) {
		$values = [];
		foreach ($self as $value) {
			$value = Histone_RTTI::toString($value);
			if (strlen($value) === 0) continue;
			array_push($values, $value);
		}
		return implode(' ', $values);
	});

	Histone_RTTI::registerMember('map', 'toBoolean', function() { return true; });

	Histone_RTTI::registerMember('map', 'toJSON', function($self) {

		$result = [];
		$count = count($self);
		$isObject = ($count && array_keys($self) !== range(0, $count - 1));

		foreach ($self as $key => $value) {

			$value = Histone_RTTI::toJSON($value);

			if ($isObject) {
				$key = strval($key);
				$key = Histone_RTTI::toJSON($key);
				array_push($result, $key . ':' . $value);
			} else array_push($result, $value);


		}

		$result = implode(',', $result);

		return (
			$isObject ?
			'{' . $result . '}' :
			'[' . $result . ']'
		);

	});




	Histone_RTTI::registerMember('map', '__get', function($self, $key) {
		if ((is_string($key) || is_int($key)) &&
			array_key_exists($key, $self)) {
			return $self[$key];
		} else return Histone_RTTI::getUndefined();
	});

	Histone_RTTI::registerMember('map', 'size', function($self) {
		return count($self);
	});

	Histone_RTTI::registerMember('map', 'hasIndex', function($self, $args) {
		$index = $args[0];
		if (!is_int($index)) return false;
		return ($index >= 0 && $index < count($self));
	});

	Histone_RTTI::registerMember('map', 'join', function($self, $args) {
		$separator = $args[0];
		if (!is_string($separator)) $separator = '';
		return implode($separator, $self);
	});

	Histone_RTTI::registerMember('map', 'slice', function($self, $args) {
		$start = (int) @$args[0];
		$length = (int) @$args[1];
		$arrLen = count($self);
		if ($start < 0) $start = $arrLen + $start;
		if ($start < 0) $start = 0;
		if ($start > $arrLen) return [];
		if ($length === 0) $length = $arrLen - $start;
		if ($length < 0) $length = $arrLen - $start + $length;
		if ($length <= 0) return [];
		return array_slice((array) $self, $start, $length);
	});

	Histone_RTTI::registerMember('map', 'keys', function($self) {
		return array_keys($self);
	});

	Histone_RTTI::registerMember('map', 'values', function($self) {
		return array_values($self);
	});

	Histone_RTTI::registerMember('map', 'hasKey', function($self, $args) {
		return array_key_exists($args[0], $self);
	});

?>