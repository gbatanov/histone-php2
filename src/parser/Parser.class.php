<?php

require_once('Tokenizer.class.php');
require_once(__DIR__ . '/../Constants.class.php');

class Histone_Parser {

	const T_BREAK = -1;
	const T_ARRAY = -2;

	private static $tokenizer;
	private static $scopeChain;

	// used to validate regular expression flags
	private static $validRegexpFlags = '/^(?:([gim])(?!.*\1))*$/';

	// used to convert control characters into regular characters
	private static $stringEscapeRegExp = '/\\\\(x[0-9A-F]{2}|u[0-9A-F]{4}|\n|.)/';

	private static function pushFrame($pushScope = false) {
		if ($pushScope) array_push(self::$scopeChain, []);
		array_push(self::$scopeChain[count(self::$scopeChain) - 1], []);
	}

	private static function popFrame($popScope = false) {
		array_pop(self::$scopeChain[count(self::$scopeChain) - 1]);
		if ($popScope) array_pop(self::$scopeChain);
	}

	private static function setName($name) {
		$lastScope = &self::$scopeChain[count(self::$scopeChain) - 1];
		$lastFrame = &$lastScope[count($lastScope) - 1];
		if (!array_key_exists($name, $lastFrame)) {
			$offset = 0;
			for ($c = 0; $c < count($lastScope) - 1; $c++)
				$offset += count($lastScope[$c]);
			foreach ($lastFrame as $key => $value)
				if ($key === $name) break; else $offset++;
			$lastFrame[$name] = $offset;
		}
		return $lastFrame[$name];
	}

	private static function getName($name) {
		$scopeIndex = count(self::$scopeChain);
		while ($scopeIndex--) {
			$scope = &self::$scopeChain[$scopeIndex];
			$frameIndex = count($scope);
			while ($frameIndex--) {
				$frame = &$scope[$frameIndex];
				if (array_key_exists($name, $frame)) {
					$offset = 0;
					for ($c = 0; $c < $frameIndex; $c++)
						$offset += count($scope[$c]);
					foreach ($frame as $key => $value) {
						if ($key === $name) break;
						$offset++;
					}
					return [Histone_Constants::T_REF, $scopeIndex, $offset];
				}
			}
		}
		return [Histone_Constants::T_GET, [Histone_Constants::T_GLOBAL], $name];
	}

	private static function escapeStringLiteral($string) {
		return preg_replace_callback(self::$stringEscapeRegExp, function($match) {
			$match = $match[1];
			switch ($match[0]) {
				// null character
				case '0': return chr(0);
				// backspace
				case 'b': return chr(8);
				// form feed
				case 'f': return chr(12);
				// new line
				case 'n': return chr(10);
				// carriage return
				case 'r': return chr(13);
				// horizontal tab
				case 't': return chr(9);
				// vertical tab
				case 'v': return chr(11);
				// hexadecimal sequence (2 digits: dd)
				case 'x': return chr(intval(substr($match, 1), 16));
				// unicode sequence (4 hex digits: dddd)
				case 'u': return chr(intval(substr($match, 1), 16));
				// by default return escaped character "as is"
				default: return $match;
			}
		}, $string);
	}

	private static function tokenize($input, $baseURI) {
		if (is_null($tokenizer = &self::$tokenizer)) {
			$tokenizer = new Histone_Tokenizer();
			$tokenizer->regexp('PROP', 'null\\b');
			$tokenizer->regexp('PROP', 'true\\b');
			$tokenizer->regexp('PROP', 'false\\b');
			$tokenizer->regexp(['PROP', 'STATEMENT'], 'if\\b');
			$tokenizer->regexp(['PROP', 'STATEMENT'], 'in\\b');
			$tokenizer->regexp(['PROP', 'STATEMENT'], 'for\\b');
			$tokenizer->regexp(['PROP', 'STATEMENT'], 'var\\b');
			$tokenizer->regexp(['PROP', 'STATEMENT'], 'else\\b');
			$tokenizer->regexp(['PROP', 'STATEMENT'], 'macro\\b');
			$tokenizer->regexp(['PROP', 'STATEMENT'], 'elseif\\b');
			$tokenizer->regexp(['PROP', 'STATEMENT'], 'return\\b');
			$tokenizer->regexp('HEX', '0[xX][0-9A-Fa-f]+');
			$tokenizer->regexp('FLOAT', '(?:[0-9]*\\.)?[0-9]+[eE][+-]?[0-9]+');
			$tokenizer->regexp('FLOAT', '[0-9]*\\.[0-9]+');
			$tokenizer->regexp('INT', '[0-9]+');
			$tokenizer->regexp(['PROP', 'REF'], 'this\\b');
			$tokenizer->regexp(['PROP', 'REF'], 'self\\b');
			$tokenizer->regexp(['PROP', 'REF'], 'global\\b');
			$tokenizer->regexp(['PROP', 'REF', 'VAR'], '[_$a-zA-Z][_$a-zA-Z0-9]*');
			$tokenizer->regexp(['SPACES', 'EOL'], '[\\x0A\\x0D]+');
			$tokenizer->regexp('SPACES', '[\\x09\\x20]+');
			$tokenizer->literal('{{%');
			$tokenizer->literal('%}}');
			$tokenizer->literal('{{*');
			$tokenizer->literal('*}}');
			$tokenizer->literal('}}');
			$tokenizer->literal('{{');
			$tokenizer->literal('!=');
			$tokenizer->literal('||');
			$tokenizer->literal('&&');
			$tokenizer->literal('!');
			$tokenizer->literal('"');
			$tokenizer->literal("'");
			$tokenizer->literal('=');
			$tokenizer->literal('%');
			$tokenizer->literal(':');
			$tokenizer->literal(',');
			$tokenizer->literal('?');
			$tokenizer->literal('<=');
			$tokenizer->literal('>=');
			$tokenizer->literal('<');
			$tokenizer->literal('>');
			$tokenizer->literal('.');
			$tokenizer->literal('-');
			$tokenizer->literal('+');
			$tokenizer->literal('*');
			$tokenizer->literal('/');
			$tokenizer->literal('\\');
			$tokenizer->literal('(');
			$tokenizer->literal(')');
			$tokenizer->literal('[');
			$tokenizer->literal(']');
		}
		return $tokenizer->tokenize($input, $baseURI);
	}

	private static function OrderedMapExpression($ctx) {

		$values = [];
		$result = [Histone_Constants::T_MAP];

		do {

			while ($ctx->next(','));
			if ($ctx->next(']')) return $result;

			if ($key = $ctx->next($ctx->PROP, ':')) {
				if (!array_key_exists($key = $key[0]['value'], $values)) {
					$values[$key] = count($result);

					array_push($result, [
						Histone_Constants::T_ITEM,
						self::Expression($ctx), $key
					]);

				} else $result[$values[$key]][1] = self::Expression($ctx);
			}

			else if ((is_string($key = self::Expression($ctx)) ||
				is_integer($key) || is_float($key)) && $ctx->next(':')) {
				if (!array_key_exists($key = strval($key), $values)) {

					$values[$key] = count($result);

					array_push($result, [
						Histone_Constants::T_ITEM,
						self::Expression($ctx), $key
					]);

				} else $result[$values[$key]][1] = self::Expression($ctx);
			}

			else array_push($result, [Histone_Constants::T_ITEM, $key]);

		} while ($ctx->next(','));

		if (!$ctx->next(']')) $ctx->error(']');
		return $result;
	}

	private static function RegexpLiteral($ctx) {

		$flags = 0;
		$result = '';
		$inCharSet = false;

		for (;;) {
			if ($ctx->test($ctx->EOL)) break;
			if ($ctx->test($ctx::T_EOF)) break;
			if (!$inCharSet && $ctx->test('/')) break;
			if ($ctx->next('\\')) $result .= '\\';
			else if ($ctx->test('[')) $inCharSet = true;
			else if ($ctx->test(']')) $inCharSet = false;
			$result .= $ctx->next()['value'];
		}

		if (!$ctx->next('/')) $ctx->error('/');
		if (@preg_match('/' . $result . '/', null) === false)
			$ctx->error(error_get_last()['message']);

		$result = [Histone_Constants::T_REGEXP, $result, &$flags];

		if ($flagsStr = $ctx->next($ctx->PROP)) {
			$flagsStr = $flagsStr['value'];
			if (!preg_match(self::$validRegexpFlags, $flagsStr))
				$ctx->error('g|i|m', $flagsStr);
			if (strpos($flagsStr, 'g') !== false)
				$flags |= Histone_Constants::RE_GLOBAL;
			if (strpos($flagsStr, 'm') !== false)
				$flags |= Histone_Constants::RE_MULTILINE;
			if (strpos($flagsStr, 'i') !== false)
				$flags |= Histone_Constants::RE_IGNORECASE;
		}

		return $result;
	}

	private static function StringLiteral($ctx) {
		$result = '';
		$start = $ctx->next()['value'];
		for ($ctx = $ctx->ignore(); $fragment = $ctx->next();) {
			if ($fragment['type'] === $ctx::T_EOF) $ctx->error($start);
			else if (($fragment = $fragment['value']) === $start)
				return self::escapeStringLiteral($result);
			else if ($fragment === '\\')
				$result .= '\\' . $ctx->next()['value'];
			else $result .= $fragment;
		}
	}

	private static function PrimaryExpression($ctx) {

		if ($ctx->next('null')) return null;
		if ($ctx->next('true')) return true;
		if ($ctx->next('false')) return false;
		if ($ctx->next('/')) return self::RegexpLiteral($ctx);
		if ($ctx->next('{{%')) return self::LiteralStatement($ctx);
		if ($ctx->test(["'", '"'])) return self::StringLiteral($ctx);
		if ($ctx->next('[')) return self::OrderedMapExpression($ctx);
		if ($ctx->test($ctx->INT)) return intval($ctx->next()['value'], 10);
		if ($ctx->test($ctx->HEX)) return intval($ctx->next()['value'], 16);
		if ($ctx->test($ctx->FLOAT)) return floatval($ctx->next()['value']);
		if ($ctx->next('this')) return [Histone_Constants::T_THIS];
		if ($ctx->next('global')) return [Histone_Constants::T_GLOBAL];
		if ($ctx->test($ctx->REF)) return [Histone_Constants::T_REF, $ctx->next()['value']];
		if ($ctx->next('{{')) return self::NodesStatement($ctx, true);
		if ($ctx->next('(')) {
			$result = self::Expression($ctx);
			if (!$ctx->next(')')) $ctx->error(')');
			return $result;
		}

		$ctx->error('EXPRESSION');
	}

	private static function MemberExpression($ctx) {

		$result = self::PrimaryExpression($ctx);

		for (;;) if ($ctx->next('.')) {
			$result = [Histone_Constants::T_GET, $result];
			if (!$ctx->test($ctx->PROP)) $ctx->error('PROP');
			array_push($result, $ctx->next()['value']);
		}

		else if ($ctx->next('[')) {
			$result = [Histone_Constants::T_GET, $result];
			array_push($result, self::Expression($ctx));
			if (!$ctx->next(']')) $ctx->error(']');
		}

		else if ($ctx->next('(')) {
			$result = [Histone_Constants::T_CALL, $result];
			if ($ctx->next(')')) continue;
			do array_push($result, self::Expression($ctx));
			while ($ctx->next(','));
			if (!$ctx->next(')')) $ctx->error(')');
		}

		else return $result;
	}

	private static function UnaryExpression($ctx) {
		if ($ctx->next('!')) return [Histone_Constants::T_NOT, self::UnaryExpression($ctx)];
		if ($ctx->next('-')) return [Histone_Constants::T_USUB, self::UnaryExpression($ctx)];
		return self::MemberExpression($ctx);
	}

	private static function MultiplicativeExpression($ctx) {
		for ($result = self::UnaryExpression($ctx);
			$ctx->next('*') && ($result = [Histone_Constants::T_MUL, $result]) ||
			$ctx->next('/') && ($result = [Histone_Constants::T_DIV, $result]) ||
			$ctx->next('%') && ($result = [Histone_Constants::T_MOD, $result]);
			array_push($result, self::UnaryExpression($ctx)));
		return $result;
	}

	private static function AdditiveExpression($ctx) {
		for ($result = self::MultiplicativeExpression($ctx);
			$ctx->next('+') && ($result = [Histone_Constants::T_ADD, $result]) ||
			$ctx->next('-') && ($result = [Histone_Constants::T_SUB, $result]);
			array_push($result, self::MultiplicativeExpression($ctx)));
		return $result;
	}

	private static function RelationalExpression($ctx) {
		for ($result = self::AdditiveExpression($ctx);
			$ctx->next('<=') && ($result = [Histone_Constants::T_LE, $result]) ||
			$ctx->next('>=') && ($result = [Histone_Constants::T_GE, $result]) ||
			$ctx->next('<') && ($result = [Histone_Constants::T_LT, $result]) ||
			$ctx->next('>') && ($result = [Histone_Constants::T_GT, $result]);
			array_push($result, self::AdditiveExpression($ctx)));
		return $result;
	}

	private static function EqualityExpression($ctx) {
		for ($result = self::RelationalExpression($ctx);
			$ctx->next('=') && ($result = [Histone_Constants::T_EQ, $result]) ||
			$ctx->next('!=') && ($result = [Histone_Constants::T_NEQ, $result]);
			array_push($result, self::RelationalExpression($ctx)));
		return $result;
	}

	private static function LogicalANDExpression($ctx) {
		for ($result = self::EqualityExpression($ctx);
			$ctx->next('&&') && ($result = [Histone_Constants::T_AND, $result]);
			array_push($result, self::EqualityExpression($ctx)));
		return $result;
	}

	private static function LogicalORExpression($ctx) {
		for ($result = self::LogicalANDExpression($ctx);
			$ctx->next('||') && ($result = [Histone_Constants::T_OR, $result]);
			array_push($result, self::LogicalANDExpression($ctx)));
		return $result;
	}

	private static function Expression($ctx) {
		$result = self::LogicalORExpression($ctx);
		while ($ctx->next('?')) {
			$result = [Histone_Constants::T_TERNARY, $result, self::Expression($ctx)];
			if ($ctx->next(':')) array_push($result, self::Expression($ctx));
		}
		return $result;
	}

	private static function ExpressionStatement($ctx) {
		if ($ctx->next('}}')) return [Histone_Constants::T_NOP];
		$expression = self::Expression($ctx);
		if (!$ctx->next('}}')) $ctx->error('}}');
		return $expression;
	}

	private static function IfStatement($ctx) {
		$result = [Histone_Constants::T_IF];
		do {
			$condition = self::Expression($ctx);
			if (!$ctx->next('}}')) $ctx->error('}}');
			array_push($result, self::NodesStatement($ctx), $condition);
		} while ($ctx->next('elseif'));
		if ($ctx->next('else')) {
			if (!$ctx->next('}}')) $ctx->error('}}');
			array_push($result, self::NodesStatement($ctx));
		}
		if (!$ctx->next('/', 'if', '}}'))
			$ctx->error('{{/if}}');
		return $result;
	}

	private static function VarStatement($ctx) {

		if (!$ctx->test($ctx->VAR, '=')) {
			$name = $ctx->next($ctx->VAR);
			if (!$name) $ctx->error('identifier');
			$name = $name['value'];
			if (!$ctx->next('}}')) $ctx->error('}}');
			$expression = self::NodesStatement($ctx);
			if (!$ctx->next('/', 'var')) $ctx->error('{{/var}}');
			if (!$ctx->next('}}')) $ctx->error('}}');
			return [Histone_Constants::T_VAR, $expression, $name];
		}

		$result = [self::T_ARRAY];

		do {
			$name = $ctx->next($ctx->VAR);
			if (!$name) $ctx->error('identifier');
			$name = $name['value'];
			if (!$ctx->next('=')) $ctx->error('=');
			$expression = self::Expression($ctx);
			array_push($result, [Histone_Constants::T_VAR, $expression, $name]);
			if (!$ctx->next(',')) break;
		} while (!$ctx->test($ctx::T_EOF));
		if (!$ctx->next('}}')) $ctx->error('}}');

		return $result;
	}

	private static function ForStatement($ctx) {

		$result = [Histone_Constants::T_FOR];

		if ($expression = $ctx->next($ctx->VAR)) {
			$expression = $expression['value'];
			if ($ctx->next(':')) {
				$key = $expression;
				if ($expression = $ctx->next($ctx->VAR))
					$value = $expression['value'];
				else $ctx->error('identifier');
			} else $value = $expression;
		}

		if (!$ctx->next('in')) $ctx->error('in');
		$expression = self::Expression($ctx);
		if (!$ctx->next('}}')) $ctx->error('}}');

		array_push($result, is_null($key) ? null : $key);
		array_push($result, is_null($value) ? null : $value);
		array_push($result, self::NodesStatement($ctx), $expression);

		while ($ctx->next('elseif')) {
			$expression = self::Expression($ctx);
			if (!$ctx->next('}}')) $ctx->error('}}');
			array_push($result, self::NodesStatement($ctx), $expression);
		}

		if ($ctx->next('else')) {
			if (!$ctx->next('}}')) $ctx->error('}}');
			array_push($result, self::NodesStatement($ctx));
		}

		if (!$ctx->next('/', 'for', '}}'))
			$ctx->error('{{/for}}');
		return $result;
	}

	private static function MacroStatement($ctx) {

		$params = [];
		$name = $ctx->next($ctx->VAR);
		if (!$name) $ctx->error('identifier');
		$result = [Histone_Constants::T_MACRO, $name = $name['value']];

		if ($ctx->next('(') && !$ctx->next(')')) {
			do {
				$param = $ctx->next($ctx->VAR);
				if (!$param) $ctx->error('identifier');
				$param = $param['value'];

				if ($ctx->next('=')) array_push($params, [
					Histone_Constants::T_PARAM,
					$param, self::Expression($ctx)
				]); else array_push($params, [
					Histone_Constants::T_PARAM, $param
				]);

			} while ($ctx->next(','));
			if (!$ctx->next(')')) $ctx->error(')');
		}
		if (!$ctx->next('}}')) $ctx->error('}}');

		array_push($result, self::NodesStatement($ctx));
		if (!$ctx->next('/', 'macro', '}}')) $ctx->error('{{/macro}}');
		return array_merge($result, $params);
	}

	private static function ReturnStatement($ctx) {
		$result = [Histone_Constants::T_RETURN];
		if ($ctx->next('}}')) {
			array_push($result, self::NodesStatement($ctx));
			if (!$ctx->next('/', 'return')) $ctx->error('{{/return}}');
		} else array_push($result, self::Expression($ctx));
		if (!$ctx->next('}}')) $ctx->error('}}');
		return $result;
	}

	private static function TemplateStatement($ctx) {
		$ctx = $ctx->ignore($ctx->SPACES);
		if ($ctx->next('if')) return self::IfStatement($ctx);
		if ($ctx->next('for')) return self::ForStatement($ctx);
		if ($ctx->next('var')) return self::VarStatement($ctx);
		if ($ctx->next('macro')) return self::MacroStatement($ctx);
		if ($ctx->next('return')) return self::ReturnStatement($ctx);
		if ($ctx->test('/', $ctx->STATEMENT, '}}')) return [self::T_BREAK];
		if ($ctx->test($ctx->STATEMENT)) return [self::T_BREAK];
		return self::ExpressionStatement($ctx);
	}

	private static function LiteralStatement($ctx) {
		$result = ''; $ctx = $ctx->ignore();
		while (!$ctx->test([$ctx::T_EOF, '%}}']))
			$result .= $ctx->next()['value'];
		if (!$ctx->next('%}}')) $ctx->error('%}}');
		return $result;
	}

	private static function CommentStatement($ctx) {
		while (!$ctx->test([$ctx::T_EOF, '*}}'])) $ctx->next();
		if (!$ctx->next('*}}')) $ctx->error('*}}');
		return [Histone_Constants::T_NOP];
	}

	private static function Statement($ctx) {
		if ($ctx->next('{{')) return self::TemplateStatement($ctx);
		if ($ctx->next('{{%')) return self::LiteralStatement($ctx);
		if ($ctx->next('{{*')) return self::CommentStatement($ctx);
		if (!$ctx->test($ctx::T_EOF)) return $ctx->next()['value'];
		return [self::T_BREAK];
	}

	private static function removeOutputNodes(&$nodes) {

		$length = count($nodes) - 1;
		array_push($nodes, array_shift($nodes));

		while ($length--) {
			$node = array_shift($nodes);
			switch (is_array($node) ? $node[0] : null) {

				case Histone_Constants::T_IF: {
					for ($c = 1, $cnt = count($node); $c < $cnt; $c += 2)
						self::removeOutputNodes($node[$c]);
					array_push($nodes, $node);
					break;
				}

				case Histone_Constants::T_FOR: {
					for ($c = 3, $cnt = count($node); $c < $cnt; $c += 2)
						self::removeOutputNodes($node[$c]);
					array_push($nodes, $node);
					break;
				}

				case Histone_Constants::T_VAR:
				case Histone_Constants::T_MACRO:
				case Histone_Constants::T_RETURN: {
					array_push($nodes, $node);
					break;
				}

			}
		}
	}

	private static function NodesStatement($ctx, $nested = false) {
		$result = [Histone_Constants::T_NODES];
		$hasReturn = false; $ctx = $ctx->ignore();
		for (;;) {
			if ($nested && $ctx->test('}}')) break;
			$node = self::Statement($ctx);
			$type = (is_array($node) ? $node[0] : null);
			if ($type === self::T_BREAK) break;
			if (!$hasReturn && $type !== Histone_Constants::T_NOP) {
				if ($type === Histone_Constants::T_RETURN) $hasReturn = true;
				if ($type === self::T_ARRAY) {
					for ($c = 1, $cnt = count($node); $c < $cnt; $c++)
						array_push($result, $node[$c]);
				} else array_push($result, $node);
			}
		}
		if ($nested && !$ctx->next('}}')) $ctx->error('}}');
		if ($hasReturn) self::removeOutputNodes($result);
		return $result;
	}

	private static function getUsedVars($node, &$result = null) {

		if (is_null($result)) $result = [
			frames => [], variables => []
		];

		$frames = &$result['frames'];
		$variables = &$result['variables'];

		switch ($type = (is_array($node) ? $node[0] : null)) {

			case Histone_Constants::T_VAR: {
				$name = $node[2];
				self::getUsedVars($node[1], $result);
				$frame = &$frames[count($frames) - 1];
				if (!array_key_exists($name, $frame))
					$frame[$name] = [];
				array_push($frame[$name], $node);
				break;
			}

			case Histone_Constants::T_MACRO: {
				$name = $node[1];
				for ($c = 3, $cnt = count($node); $c < $cnt; $c++) {
					$param = $node[$c];
					if (array_key_exists(2, $param)) {
						self::getUsedVars($param[2], $result);
					}
				}
				self::getUsedVars($node[2], $result);
				$frame = &$frames[count($frames) - 1];
				if (!array_key_exists($name, $frame))
					$frame[$name] = [];
				array_push($frame[$name], $node);
				break;
			}

			case Histone_Constants::T_REF: {
				$name = $node[1];
				$index = count($frames);
				while ($index--) {
					$frame = $frames[$index];
					if (array_key_exists($name, $frame)) {
						array_push($variables, end($frame[$name]));
						break;
					}
				}
				break;
			}

			case Histone_Constants::T_NODES: {
				array_push($frames, []);
				for ($c = 1, $cnt = count($node); $c < $cnt; $c++)
					self::getUsedVars($node[$c], $result);
				array_pop($frames);
				break;
			}

			default: if (is_array($node)) foreach ($node as $item) {
				self::getUsedVars($item, $result);
			}


		}


		return $variables;
	}

	private static function removeUnusedVars(&$nodes, $usedVars, &$repeat = false) {

		if (!is_array($nodes)) return $repeat;

		$length = count($nodes) - 1;
		array_push($nodes, array_shift($nodes));

		while ($length--) {
			$node = array_shift($nodes);

			switch (is_array($node) ? $node[0] : null) {

				case Histone_Constants::T_VAR: {
					if (array_search($node, $usedVars) !== false) {
						self::removeUnusedVars($node[1], $usedVars, $repeat);
						array_push($nodes, $node);
					} else $repeat = true;
					break;
				}

				case Histone_Constants::T_MACRO: {
					if (array_search($node, $usedVars) !== false) {
						for ($c = 3; $c < count($node); $c++) {
							$param = &$node[$c];
							if (array_key_exists(2, $param)) {
								self::removeUnusedVars($param[2], $usedVars, $repeat);
							}
						}
						self::removeUnusedVars($node[2], $usedVars, $repeat);
						array_push($nodes, $node);
					} else $repeat = true;
					break;
				}

				default: {
					self::removeUnusedVars($node, $usedVars, $repeat);
					array_push($nodes, $node);
				}

			}

		}

		return $repeat;
	}

	private static function markReferences(&$node, $pushFrame = true) {

		switch ($type = (is_array($node) ? $node[0] : null)) {

			case Histone_Constants::T_REF: {
				$node = self::getName($node[1]);
				break;
			}

			case Histone_Constants::T_VAR: {
				self::markReferences($node[1]);
				$node[2] = self::setName($node[2]);
				break;
			}

			case Histone_Constants::T_FOR: {

				self::markReferences($node[4]);

				self::pushFrame();
				self::setName('self');

				if (!is_null($key = &$node[1]))
					$key = self::setName($key);

				if (!is_null($value = &$node[2]))
					$value = self::setName($value);

				self::markReferences($node[3], false);
				self::popFrame();

				for ($c = 5; $c < count($node); $c += 2) {
					self::markReferences($node[$c]);
					if (array_key_exists($c + 1, $node)) {
						self::markReferences($node[$c + 1]);
					}
				}
				break;
			}

			case Histone_Constants::T_MACRO: {

				for ($c = 4; $c < count($node); $c++) {
					$param = &$node[$c];
					if (array_key_exists(2, $param)) {
						self::markReferences($param[2]);
					}
				}

				array_splice($node, 1, 1, [
					count(self::$scopeChain),
					self::setName($node[1])
				]);

				self::pushFrame(true);
				self::setName('self');
				for ($c = 4; $c < count($node); $c++) {
					$name = &$node[$c][1];
					$name = self::setName($name);
				}
				self::markReferences($node[3], false);
				self::popFrame(true);


				break;
			}

			case Histone_Constants::T_NODES: {
				if ($pushFrame) self::pushFrame();
				for ($c = 1; $c < count($node); $c++)
					self::markReferences($node[$c]);
				if ($pushFrame) self::popFrame();
				break;
			}

			default: if (is_array($node)) foreach ($node as &$item) {
				self::markReferences($item);
			}

		}

	}

	private static function mergeStrings(&$nodes) {
		$type = (is_array($nodes) ? $nodes[0] : null);
		if ($type === Histone_Constants::T_NODES) {

			$stringIndex = 0;
			$length = count($nodes) - 1;
			array_push($nodes, array_shift($nodes));

			while ($length--) {

				$node = array_shift($nodes);
				$type = (is_array($node) ? $node[0] : null);

				if (is_string($node)) {
					if (!$stringIndex) {
						$stringIndex++;
						array_push($nodes, $node);
					} else $nodes[count($nodes) - $stringIndex] .= $node;
				}

				else if ($type === Histone_Constants::T_VAR) {
					if ($stringIndex) $stringIndex++;
					self::mergeStrings($node[1]);
					array_push($nodes, $node);
				}

				else if ($type === Histone_Constants::T_MACRO) {
					if ($stringIndex) $stringIndex++;
					for ($c = 4; $c < count($node); $c++) {
						$param = &$node[$c];
						if (array_key_exists(2, $param)) {
							self::mergeStrings($param[2]);
						}
					}
					self::mergeStrings($node[3]);
					array_push($nodes, $node);
				}

				else {
					$stringIndex = 0;
					self::mergeStrings($node);
					array_push($nodes, $node);
				}

			}
		}

		else if (is_array($nodes)) {
			foreach ($nodes as &$node) {
				self::mergeStrings($node);
			}
		}
	}

	public static function parse($input, $baseURI) {

		$ctx = self::tokenize($input, $baseURI);
		$result = self::NodesStatement($ctx);
		if (!$ctx->next($ctx::T_EOF)) $ctx->error('EOF');


		do $usedVars = self::getUsedVars($result);
		while (self::removeUnusedVars($result, $usedVars));


		self::$scopeChain = [];
		self::pushFrame(true);
		self::markReferences($result, false);
		self::popFrame(true);


		self::mergeStrings($result);

		return $result;
	}

}