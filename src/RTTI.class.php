<?php


require_once('rtti/Null.class.php');
require_once('rtti/Bool.class.php');
require_once('rtti/Map.class.php');
require_once('rtti/Macro.class.php');
require_once('rtti/String.class.php');
require_once('rtti/Number.class.php');
require_once('rtti/RegExp.class.php');
require_once('rtti/Global.class.php');
require_once('rtti/Closure.class.php');
require_once('rtti/undefined.class.php');

class Histone_RTTI {

	private static $global;
	private static $undefined;

	private static $typeInfo = [
		'type' => ['members' => []],
		'null' => ['members' => []],
		'bool' => ['members' => []],
		'number' => ['members' => []],
		'string' => ['members' => []],
		'map' => ['members' => []]
	];

	private static function getType($value) {
		switch (gettype($value)) {
			case 'NULL': return 'null';
			case 'boolean': return 'bool';
			case 'integer': return 'number';
			case 'double': return 'number';
			case 'string': return 'string';
			case 'array': return 'map';
			case 'object': return get_class($value);
			default: return 'type';
		}
	}

	static function registerType($type) {
		$typeInfo = &self::$typeInfo;
		if (!array_key_exists($type, $typeInfo) && class_exists($type)) {
			$typeInfo[$type] = ['members' => []];
		}
	}

	static function registerMember($type, $name, $handler) {
		$typeInfo = &self::$typeInfo;
		if (array_key_exists($type, $typeInfo) &&
			is_string($name) && strlen($name) &&
			$handler instanceof Closure) {
			if ($name === '__get')
				$typeInfo[$type]['__get'] = $handler;
			else $typeInfo[$type]['members'][$name] = $handler;
		}
	}

	static function toPrimitive($value) {
		while ($value instanceof Histone_Get)
			$value = self::get($value->left, $value->right);
		return $value;
	}

	static function get($value, $name) {

		$typeInfo = &self::$typeInfo;


		$value = self::toPrimitive($value);
		$type = self::getType($value);

		if (!array_key_exists($type, $typeInfo)) {
			$value = self::getUndefined();
			$type = 'type';
		}

		$getter = null;

		$checkTypes = [$type, 'type'];

		foreach ($checkTypes as $checkType) {

			$checkType = $typeInfo[$checkType];

			if (@array_key_exists($name, $checkType['members'])) {
				return $checkType['members'][$name];
			}

			else if (is_null($getter) && array_key_exists('__get', $checkType)) {
				$getter = $checkType['__get'];
			}

		}

		if (!is_null($getter))
			return $getter($value, $name);

		return self::getUndefined();

	}

	static function toString($value) {
		$value = self::toPrimitive($value);
		$toString = self::get($value, 'toString');
		return $toString($value);
	}

	static function toBoolean($value) {
		$value = self::toPrimitive($value);
		$toBoolean = self::get($value, 'toBoolean');
		return $toBoolean($value);
	}

	static function toJSON($value) {
		$value = self::toPrimitive($value);
		$toJSON = self::get($value, 'toJSON');
		return $toJSON($value);
	}

	static function getUndefined() {
		if (is_null(self::$undefined))
			self::$undefined = new Histone_Undefined();
		return self::$undefined;
	}

	static function getGlobal() {
		if (is_null(self::$global))
			self::$global = new Histone_Global();
		return self::$global;
	}

}

Histone_RTTI::registerMember('type', 'isUndefined', function() { return false; });
Histone_RTTI::registerMember('type', 'isNull', function() { return false; });
Histone_RTTI::registerMember('type', 'isBoolean', function() { return false; });
Histone_RTTI::registerMember('type', 'isString', function() { return false; });
Histone_RTTI::registerMember('type', 'isNumber', function() { return false; });
Histone_RTTI::registerMember('type', 'isMap', function() { return false; });
Histone_RTTI::registerMember('type', 'isRegExp', function() { return false; });