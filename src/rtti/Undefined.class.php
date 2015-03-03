<?php

	class Histone_Undefined {}
	Histone_RTTI::registerType('Histone_Undefined');
	Histone_RTTI::registerMember('Histone_Undefined', 'isUndefined', function() { return true; });
	Histone_RTTI::registerMember('Histone_Undefined', 'toString', function() { return '[Histone_Undefined]'; });
	Histone_RTTI::registerMember('Histone_Undefined', 'toBoolean', function() { return false; });
	Histone_RTTI::registerMember('Histone_Undefined', 'toJSON', function() { return 'null'; });

?>