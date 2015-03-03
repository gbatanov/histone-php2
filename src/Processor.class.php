<?php

require('RTTI.class.php');
require('Runtime.class.php');

class Histone_Return {

	public $value;

	function __construct($value) {
		$this->value = $value;
	}

}

class Histone_Get {

	public $left;
	public $right;

	function __construct($left, $right) {
		$this->left = $left;
		$this->right = $right;
	}

}

class Histone_Processor {

	private static function is_number($value) {
		return (is_int($value) || is_float($value));
	}

	private static function processMap($node, $runtime) {
		$result = [];
		for ($c = 1; $c < count($node); ++$c) {
			$item = $node[$c];
			$value = self::processNode2($item[1], $runtime);
			if (array_key_exists(2, $item))
				$result[$item[2]] = $value;
			else $result[] = $value;
		}
		return $result;
	}

	private static function processNot($node, $runtime) {
		$node = self::processNode2($node[1], $runtime);
		return (!Histone_RTTI::toBoolean($node));
	}

	private static function processOr($node, $runtime) {
		$left = self::processNode2($node[1], $runtime);
		if (Histone_RTTI::toBoolean($left)) return $left;
		return self::processNode2($node[2], $runtime);
	}

	private static function processAnd($node, $runtime) {
		$left = self::processNode2($node[1], $runtime);
		if (!Histone_RTTI::toBoolean($left)) return $left;
		return self::processNode2($node[2], $runtime);
	}

	private static function processTernary($node, $runtime) {
		$condition = self::processNode2($node[1], $runtime);
		if (Histone_RTTI::toBoolean($condition))
			return self::processNode2($node[2], $runtime);
		if (array_key_exists(3, $node))
			return self::processNode2($node[3], $runtime);
		return Histone_RTTI::getUndefined();
	}

	private static function processAddition($node, $runtime) {
		$left = self::processNode2($node[1], $runtime);
		$right = self::processNode2($node[2], $runtime);
		$left = Histone_RTTI::toPrimitive($left);
		$right = Histone_RTTI::toPrimitive($right);
		if (!(is_string($left) || is_string($right))) {
			if (is_numeric($left) || is_numeric($right)) {
				if (is_numeric($left)) $left = floatval($left);
				if (!self::is_number($left)) return Histone_RTTI::getUndefined();
				if (is_numeric($right)) $right = floatval($right);
				if (!self::is_number($right)) return Histone_RTTI::getUndefined();
				return ($left + $right);
			}
			if (is_array($left) && is_array($right)) {
				return array_merge($left, $right);
			}
		}
		return (Histone_RTTI::toString($left) . Histone_RTTI::toString($right));
	}

	private static function processArithmetical($type, $node, $runtime) {
		$left = self::processNode2($node[1], $runtime);
		$left = Histone_RTTI::toPrimitive($left);
		if (is_numeric($left)) $left = floatval($left);
		if (!self::is_number($left)) return Histone_RTTI::getUndefined();
		if ($type === Histone_Constants::T_USUB) return (-$left);
		$right = self::processNode2($node[2], $runtime);
		$right = Histone_RTTI::toPrimitive($right);
		if (is_numeric($right)) $right = floatval($right);
		if (!self::is_number($right)) return Histone_RTTI::getUndefined();

		switch ($type) {
			case Histone_Constants::T_SUB: return ($left - $right);
			case Histone_Constants::T_MUL: return ($left * $right);
			case Histone_Constants::T_DIV: return ($left / $right);
			case Histone_Constants::T_MOD: return ($left % $right);
		}
	}

	private static function processRelational($type, $node, $runtime) {

		$left = self::processNode2($node[1], $runtime);
		$right = self::processNode2($node[2], $runtime);
		$left = Histone_RTTI::toPrimitive($left);
		$right = Histone_RTTI::toPrimitive($right);

		if (is_string($left) && self::is_number($right)) {
			if (is_numeric($left)) $left = floatval($left);
			else $right = Histone_RTTI::toString($right);
		}

		elseif (self::is_number($left) && is_string($right)) {
			if (is_numeric($right)) $right = floatval($right);
			else $left = Histone_RTTI::toString($left);
		}

		if (!(self::is_number($left) && self::is_number($right))) {
			if (is_string($left) && is_string($right)) {
				$left = strlen($left);
				$right = strlen($right);
			} else {
				$left = Histone_RTTI::toBoolean($left);
				$right = Histone_RTTI::toBoolean($right);
			}
		}

		switch ($type) {
			case Histone_Constants::T_LT: return ($left < $right);
			case Histone_Constants::T_GT: return ($left > $right);
			case Histone_Constants::T_LE: return ($left <= $right);
			case Histone_Constants::T_GE: return ($left >= $right);
		}
	}

	private static function processEquality($type, $node, $runtime) {

		$left = self::processNode2($node[1], $runtime);
		$right = self::processNode2($node[2], $runtime);
		$left = Histone_RTTI::toPrimitive($left);
		$right = Histone_RTTI::toPrimitive($right);

		if (is_string($left) && self::is_number($right)) {
			if (is_numeric($left)) $left = floatval($left);
			else $right = Histone_RTTI::toString($right);
		}

		elseif (self::is_number($left) && is_string($right)) {
			if (is_numeric($right)) $right = floatval($right);
			else $left = Histone_RTTI::toString($left);
		}

		if (!(is_string($left) && is_string($right))) {
			if (self::is_number($left) && self::is_number($right)) {
				$left = floatval($left);
				$right = floatval($right);
			} else {
				$left = Histone_RTTI::toBoolean($left);
				$right = Histone_RTTI::toBoolean($right);
			}
		}

		return (
			$type === Histone_Constants::T_EQ ?
			$left === $right : $left !== $right
		);
	}

	private static function processIf($node, $runtime) {
		$result = '';
		for ($c = 1; $c < count($node); $c += 2) {
			if (!array_key_exists($c + 1, $node) || Histone_RTTI::toBoolean(
				self::processNode($node[$c + 1], $runtime))) {
				$result = self::processNode($node[$c], $runtime);
				break;
			}
		}
		return $result;
	}

	private static function processGet($node, $runtime) {
		return new Histone_Get(
			self::processNode2($node[1], $runtime),
			self::processNode2($node[2], $runtime)
		);
	}

	private static function processFor($node, $runtime) {

		$result = '';
		$collection = self::processNode2($node[4], $runtime);

		if (is_array($collection) && $last = count($collection)) {

			$index = 0;
			$last = $last - 1;
			$selfIndex = $runtime->getVarIndex();
			$keyIndex = $node[1]; $valIndex = $node[2];

			foreach ($collection as $key => $value) {

				$runtime->setVar([
					key => $key,
					value => $value,
					index => $index++,
					last => $last
				], $selfIndex);

				if (!is_null($keyIndex)) $runtime->setVar($key, $keyIndex);
				if (!is_null($valIndex)) $runtime->setVar($value, $valIndex);

				$iteration = self::processNode($node[3], $runtime);
				if ($iteration instanceof Histone_Return) { $result = $iteration; break; }

				$result .= $iteration;

			}
		}

		else for ($c = 5; $c < count($node); $c += 2) {
			if (!array_key_exists($c + 1, $node) || Histone_RTTI::toBoolean(
				self::processNode2($node[$c + 1], $runtime)
			)) return self::processNode($node[$c], $runtime);
		}

		return $result;
	}

	private static function processCall($node, $runtime) {

		$args = [];
		for ($c = 2, $cnt = count($node); $c < $cnt; ++$c)
			array_push($args, self::processNode2($node[$c], $runtime));

		$thisObj = null;

		$callee = self::processNode2($node[1], $runtime);

		while ($callee instanceof Histone_Get) {
			$thisObj = Histone_RTTI::toPrimitive($callee->left);
			$callee = Histone_RTTI::get($thisObj, $callee->right);
		}

		if ($callee instanceof Closure)
			return $callee($thisObj, $args, $runtime);

		if ($callee instanceof Histone_Macro) {
			return $callee->call($args, $runtime);
		}

		return Histone_RTTI::getUndefined();

	}

	private static function processMacro($node, $runtime) {
		$params = [];
		for ($c = 4; $c < count($node); ++$c) {
			$param = $node[$c];
			if (array_key_exists(2, $param))
				$param[2] = self::processNode2($param[2], $runtime);
			array_push($params, $param);
		}
		$scope = $node[1]; $index = $node[2]; $body = $node[3];
		$runtime->setVar(new Histone_Macro($params, $body, $scope, $runtime), $index);
		return '';
	}

	private static function processVar($node, $runtime) {
		$value = self::processNode2($node[1], $runtime);
		$runtime->setVar($value, $node[2]);
		return '';
	}

	private static function processNodes($node, $runtime) {
		$result = '';
		for ($c = 1, $cnt = count($node); $c < $cnt; ++$c) {
			$value = self::processNode($node[$c], $runtime);
			if ($value instanceof Histone_Return) { $result = $value; break; }
			$result .= Histone_RTTI::toString($value);
		}
		return $result;
	}



	public static function processNode($node, $runtime) {

		if (!is_array($node)) return $node;

		switch ($type = $node[0]) {

			// primitives
			case Histone_Constants::T_MAP:
				return self::processMap($node, $runtime);
			case Histone_Constants::T_REGEXP:
				return new Histone_RegExp($node[1], $node[2]);

			// logical
			case Histone_Constants::T_NOT:
				return self::processNot($node, $runtime);
			case Histone_Constants::T_OR:
				return self::processOr($node, $runtime);
			case Histone_Constants::T_AND:
				return self::processAnd($node, $runtime);
			case Histone_Constants::T_TERNARY:
				return self::processTernary($node, $runtime);

			// addition
			case Histone_Constants::T_ADD:
				return self::processAddition($node, $runtime);

			// arithmetical
			case Histone_Constants::T_SUB:
			case Histone_Constants::T_MUL:
			case Histone_Constants::T_DIV:
			case Histone_Constants::T_MOD:
			case Histone_Constants::T_USUB:
				return self::processArithmetical($type, $node, $runtime);

			// relational
			case Histone_Constants::T_LT:
			case Histone_Constants::T_GT:
			case Histone_Constants::T_LE:
			case Histone_Constants::T_GE:
				return self::processRelational($type, $node, $runtime);

			// equality
			case Histone_Constants::T_EQ:
			case Histone_Constants::T_NEQ:
				return self::processEquality($type, $node, $runtime);


			case Histone_Constants::T_RETURN:
				return new Histone_Return(self::processNode2($node[1], $runtime));

			case Histone_Constants::T_IF: return self::processIf($node, $runtime);
			case Histone_Constants::T_VAR: return self::processVar($node, $runtime);
			case Histone_Constants::T_REF: return $runtime->getVar($node[1], $node[2]);
			case Histone_Constants::T_THIS: return $runtime->thisObj;
			case Histone_Constants::T_GLOBAL: return Histone_RTTI::getGlobal();
			case Histone_Constants::T_FOR: return self::processFor($node, $runtime);
			case Histone_Constants::T_GET: return self::processGet($node, $runtime);
			case Histone_Constants::T_CALL: return self::processCall($node, $runtime);
			case Histone_Constants::T_MACRO: return self::processMacro($node, $runtime);
			case Histone_Constants::T_NODES: return self::processNodes($node, $runtime);

			default: throw new Exception($type);

		}
	}


	private static function processNode2($node, $runtime) {
		$result = self::processNode($node, $runtime);
		if ($result instanceof Histone_Return)
			$result = $result->value;
		return $result;
	}


	static function process($template, $runtime) {
		$result = self::processNode($template, $runtime);
		if ($result instanceof Histone_Return) $result = $result->value;
		return $result;
	}

}