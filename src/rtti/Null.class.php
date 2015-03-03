<?php

	Histone_RTTI::registerMember('null', 'isNull', function() { return true; });
	Histone_RTTI::registerMember('null', 'toString', function() { return 'null'; });
	Histone_RTTI::registerMember('null', 'toBoolean', function() { return false; });
	Histone_RTTI::registerMember('null', 'toJSON', function() { return 'null'; });

?>