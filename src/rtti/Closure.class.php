<?php

Histone_RTTI::registerType('Closure');
Histone_RTTI::registerMember('Closure', 'isCallable', function() { return true; });
Histone_RTTI::registerMember('Closure', 'toString', function() { return '[Histone_Closure]'; });
Histone_RTTI::registerMember('Closure', 'toBoolean', function() { return true; });
Histone_RTTI::registerMember('Closure', 'toJSON', function() { return '[Histone_Closure]'; });

Histone_RTTI::registerMember('Closure', 'apply', function($this, $args, $runtime) {
	return $this(null, $args[0], $runtime);
});

Histone_RTTI::registerMember('Closure', 'call', function($this, $args, $runtime) {
	return $this($args[0], [], $runtime);
});