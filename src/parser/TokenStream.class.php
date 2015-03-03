<?php

class Histone_ParseError extends Exception {

	public $line = '';
	public $found = '';
	public $expected = '';

	public function __construct($line, $expected, $found, $file) {
		parent::__construct();
		$this->line = $line;
		$this->expected = $expected;
		$this->found = $found;
		$this->file = $file;
	}

	public function __toString() {
		return ($this->file . '(' .
			$this->line .
			') Syntax error, "' .
			$this->expected .
			'" expected but "' .
			$this->found .
			'" found');
	}

	public function getExpected() {
		return strval($this->expected);
	}

	public function getFound() {
		return strval($this->found);
	}

}


class Histone_TokenStream {

	private $ignored;
	private $tokenizer;
	private $baseURI;

	const T_EOF = Histone_Tokenizer::T_EOF;
	const T_ERROR = Histone_Tokenizer::T_ERROR;
	const T_TOKEN = Histone_Tokenizer::T_TOKEN;

	private function peek($offset) {
		$token = $this->tokenizer->peek($offset);
		if ($this->compareToken($token, $this->ignored))
			return array_merge($token, ['ignored' => true]);
		else return $token;
	}

	function __construct($tokenizer, $baseURI, $ignored = []) {
		$this->ignored = $ignored;
		$this->baseURI = $baseURI;
		$this->tokenizer = $tokenizer;
	}

	public function __get($name) {
		return $this->tokenizer->getTokenId($name);
	}

	private function compareToken($token, $selector) {

		if (!is_array($selector))
			$selector = [$selector];

		$type = $token['type'];
		$value = $token['value'];

		foreach ($selector as $fragment) {

			if (is_numeric($fragment)) {

				if (is_array($type)) {
					if (array_search($fragment, $type, true) !== false) {
						return true;
					}
				}

				else if ($fragment === $type) {
					return true;
				}

			}

			else if (is_string($fragment)) {
				if ($fragment === $value) {
					return true;
				}
			}

		}

	}


	private function getToken($consume) {

		if ($consume) do {
			$token = $this->peek(0);
			$this->tokenizer->shift();
		} while ($token['ignored']);

		else {
			$offset = 0;
			do $token = $this->peek($offset++);
			while ($token['ignored']);
		}

		return $token;

	}

	private function testToken($selector, $consume) {

		$result = [];
		$end = $index = 0;
		$length = count($selector);

		for (;;) {
			$token = $this->peek($end++);
			if ($this->compareToken($token, $selector[$index])) {
				array_push($result, $token);
				if (++$index >= $length) break;
			} else if (!$token['ignored']) return;
		}

		if (!$consume) return true;

		while ($end--) $this->tokenizer->shift();
		if (count($result) === 1) $result = $result[0];
		return $result;
	}

	public function ignore() {
		return new Histone_TokenStream(
			$this->tokenizer,
			$this->baseURI,
			func_get_args()
		);
	}

	public function next() {
		return (
			func_num_args() ?
			$this->testToken(func_get_args(), true) :
			$this->getToken(true)
		);
	}

	public function test() {
		return (
			func_num_args() ?
			$this->testToken(func_get_args(), false) :
			$this->getToken(false)
		);
	}

	public function error($expected, $found = null) {
		$token = $this->next();
		$line = $this->tokenizer->getLineNumber($token['pos']);
		if (!$found) {
			$found = $token['value'];
			if ($token['type'] === self::T_EOF) $found = 'T_EOF';
		}
		throw new Histone_ParseError($line, $expected, $found, $this->baseURI);
	}

}