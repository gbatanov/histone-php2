<?php

require_once('TokenStream.class.php');

class Histone_Tokenizer {

	const T_EOF = -1;
	const T_ERROR = -2;
	const T_TOKEN = -3;

	private $inputStr = '';
	private $inputLen = 0;
	private $regexp = '//';
	private $lastIndex = 0;

	private $buffer = [];
	private $tokenIds = [];
	private $tokenNames = [];
	private $lastTokenId = 0;

	private function readTokenToBuffer() {
		if ($this->lastIndex < $this->inputLen) {

			if (preg_match($this->regexp, $this->inputStr, $matches, PREG_OFFSET_CAPTURE, $this->lastIndex)) {

				$matchText = $matches[0][0];
				$matchIndex = $matches[0][1];

				if ($this->lastIndex < $matchIndex) {
					array_push($this->buffer, [
						'type' => self::T_ERROR,
						'pos' => $this->lastIndex,
						'value' => substr($this->inputStr, $this->lastIndex, $matchIndex - $this->lastIndex)
					]);
				}

				$this->lastIndex = ($matchIndex + strlen($matchText));

				array_push($this->buffer, [
					'type' => $this->tokenIds[count($matches) - 2],
					'pos' => $matchIndex,
					'value' => $matchText
				]);
			}

			else {

				array_push($this->buffer, [
					'type' => self::T_ERROR,
					'pos' => $this->lastIndex,
					'value' => substr($this->inputStr, $this->lastIndex)
				]);

				$this->lastIndex = $this->inputLen;

			}
		}

		else {
			array_push($this->buffer, [
				'type' => self::T_EOF,
				'pos' => $this->inputLen
			]);

		}
	}

	private function addExpression($name, $expression) {

		$regexp = &$this->regexp;
		$regexp = substr($regexp, 1, -1);
		if (strlen($regexp) > 0) $regexp .= '|';
		$regexp .= '(' . $expression . ')';
		$regexp = '/' . $regexp . '/';

		$tokenIds = &$this->tokenIds;
		$tokenNames = &$this->tokenNames;
		$lastTokenId = &$this->lastTokenId;

		if (is_array($name)) {
			array_push($tokenIds, array_map(function($name) use (&$tokenNames, &$lastTokenId) {
				if (!array_key_exists($name, $tokenNames))
					$tokenNames[$name] = (++$lastTokenId);
				return $tokenNames[$name];
			}, $name));
		}

		else if (is_string($name)) {
			if (!array_key_exists($name, $tokenNames))
				$tokenNames[$name] = (++$lastTokenId);
			array_push($tokenIds, $tokenNames[$name]);
		}

		else array_push($tokenIds, self::T_TOKEN);
	}

	public function regexp() {
		$length = func_num_args();
		if ($length === 0) return;
		$arguments = func_get_args();
		if ($length > 1) {
			$name = $arguments[0];
			$expression = $arguments[1];
		} else $expression = $arguments[0];
		$this->addExpression($name, $expression);
	}

	public function literal() {
		$length = func_num_args();
		if ($length === 0) return;
		$arguments = func_get_args();
		if ($length > 1) {
			$name = $arguments[0];
			$expression = $arguments[1];
		} else $expression = $arguments[0];
		$this->addExpression($name, preg_quote($expression, '/'));
	}

	public function getLineNumber($position) {
		$pos = -1;
		$lineNumber = 1;
		while (++$pos < $position) {
			$code = ord(substr($this->inputStr, $pos, 1));
			if ($code === 10 or $code === 13) $lineNumber++;
		}
		return $lineNumber;
	}

	public function tokenize($input, $baseURI) {
		$this->buffer = [];
		$this->lastIndex = 0;
		$this->inputStr = $input;
		$this->inputLen = strlen($input);
		return new Histone_TokenStream($this, $baseURI);
	}

	public function getTokenId($name) {
		return (
			array_key_exists($name, $this->tokenNames) ?
			$this->tokenNames[$name] : 0
		);
	}

	public function peek($offset) {
		$buffer = &$this->buffer;
		$toRead = $offset - count($buffer) + 1;
		while ($toRead-- > 0) $this->readTokenToBuffer();
		return $buffer[$offset];
	}

	public function shift() {
		return array_shift($this->buffer);
	}

}