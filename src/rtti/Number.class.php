<?php

	Histone_RTTI::registerMember('number', 'isNumber', function() { return true; });

	Histone_RTTI::registerMember('number', 'toString', function($self) {
		$self = strtolower((string) (float) ($self));
		if (strpos($self, 'e') === false) return $self;
		$self = explode('e', $self);
		$numericPart = rtrim($self[0], '0');
		$exponentPart = $self[1];
		$numericSign = $numericPart[0];
		$exponentSign = $exponentPart[0];
		if ($numericSign === '+' || $numericSign === '-')
			$numericPart = substr($numericPart, 1);
		else $numericSign = '';
		if ($exponentSign === '+' || $exponentSign === '-')
			$exponentPart = substr($exponentPart, 1);
		else $exponentSign = '+';
		$decPos = strpos($numericPart, '.');
		if ($decPos === -1) {
			$rDecPlaces = 0;
			$lDecPlaces = strlen($numericPart);
		} else {
			$rDecPlaces = strlen(substr($numericPart, $decPos + 1));
			$lDecPlaces = strlen(substr($numericPart, 0, $decPos));
			$numericPart = str_replace('.', '', $numericPart);
		}
		if ($exponentSign === '+')
			$numZeros = $exponentPart - $rDecPlaces;
		else $numZeros = $exponentPart - $lDecPlaces;
		$zeros = str_pad('', $numZeros, '0');
		return (
			$exponentSign === '+' ?
			$numericSign . $numericPart . $zeros :
			$numericSign . '0.' . $zeros . $numericPart
		);
	});

	Histone_RTTI::registerMember('number', 'toBoolean', function($self) { return ($self !== 0); });
	Histone_RTTI::registerMember('number', 'toJSON', function($self) { return json_encode($self); });
	Histone_RTTI::registerMember('number', 'abs', function($self) { return abs($self); });
	Histone_RTTI::registerMember('number', 'floor', function($self) { return floor($self); });
	Histone_RTTI::registerMember('number', 'ceil', function($self) { return ceil($self); });
	Histone_RTTI::registerMember('number', 'round', function($self) { return round($self); });
	Histone_RTTI::registerMember('number', 'toChar', function($self) { return chr($self); });
	Histone_RTTI::registerMember('number', 'isInt', function($self) { return is_int($self); });
	Histone_RTTI::registerMember('number', 'isFloat', function($self) { return is_double($self); });

?>