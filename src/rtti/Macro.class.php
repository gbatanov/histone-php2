<?php

	class Histone_Macro {

		private $params;
		private $body;
		private $scope;
		private $runtime;

		function __construct($params, $body, $scope, $runtime) {
			$this->params = $params;
			$this->body = $body;
			$this->scope = $scope;
			$this->runtime = $runtime;
		}

		function call($args) {

			$body = $this->body;
			$params = $this->params;
			$scope = $this->scope;
			$runtime = $this->runtime;

			$runtime->pushScope($scope);

			$runtime->setVar([callee => $this, arguments => $args], 0);

			foreach ($params as $index => $param) {
				if (array_key_exists($index, $args)) {
					$runtime->setVar($args[$index], $param[1]);
				} else if (array_key_exists(2, $param)) {
					$runtime->setVar($param[2], $param[1]);
				}
			}
			$result = Histone_Processor::processNode(
				$body,
				$runtime
			);

			$runtime->popScope($scope);
			return $result;
		}

	}

	Histone_RTTI::registerType('Histone_Macro');
	Histone_RTTI::registerMember('Histone_Macro', 'isCallable', function() { return true; });
	Histone_RTTI::registerMember('Histone_Macro', 'toString', function() { return '[Histone_Macro]'; });
	Histone_RTTI::registerMember('Histone_Macro', 'toBoolean', function() { return true; });
	Histone_RTTI::registerMember('Histone_Macro', 'toJSON', function() { return '[Histone_Macro]'; });

	Histone_RTTI::registerMember('Histone_Macro', 'apply', function($self, $args, $runtime) {
		$callArgs = [];
		foreach ($args as $arg) {
			if (is_array($arg)) foreach ($arg as $item)
				array_push($callArgs, $item);
			else array_push($callArgs, $arg);
		}
		return $self->call($callArgs, $runtime);
	});

?>