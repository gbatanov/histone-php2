<?php

	Histone_RTTI::registerMember('bool', 'isBoolean', function() { return true; });
	Histone_RTTI::registerMember('bool', 'toString', function($self) { return ($self ? 'true' : 'false'); });
	Histone_RTTI::registerMember('bool', 'toBoolean', function($self) { return $self; });
	Histone_RTTI::registerMember('bool', 'toJSON', function($self) { return ($self ? 'true' : 'false'); });

?>