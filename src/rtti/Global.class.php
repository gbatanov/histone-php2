<?php

	class Histone_Global {

		static $WEEK_DAYS_SHORT = array(1 => 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс');
		static $WEEK_DAYS_LONG = array(1 => 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье');
		static $MONTH_NAMES_SHORT = array(1 => 'Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек');
		static $MONTH_NAMES_LONG = array(1 => 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');

	}

	Histone_RTTI::registerType('Histone_Global');

	Histone_RTTI::registerMember('Histone_Global', 'toString', function() {
		return '[Histone_Global]';
	});

	Histone_RTTI::registerMember('Histone_Global', 'getBaseURI', function($self, $args, $runtime) {
		return $runtime->baseURI;
	});

	Histone_RTTI::registerMember('Histone_Global', 'uniqueId', function() {
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				// 32 bits for "time_low"
				mt_rand(0, 0xffff), mt_rand(0, 0xffff),
				// 16 bits for "time_mid"
				mt_rand(0, 0xffff),
				// 16 bits for "time_hi_and_version",
				// four most significant bits holds version number 4
				mt_rand(0, 0x0fff) | 0x4000,
				// 16 bits, 8 bits for "clk_seq_hi_res",
				// 8 bits for "clk_seq_low",
				// two most significant bits holds zero and one for variant DCE1.1
				mt_rand(0, 0x3fff) | 0x8000,
				// 48 bits for "node"
				mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	});

	Histone_RTTI::registerMember('Histone_Global', 'weekDayNameShort', function($self, $args) {
		if (isset($args[0]) && isset(Histone_Global::$WEEK_DAYS_SHORT[(int) $args[0]]))
			return Histone_Global::$WEEK_DAYS_SHORT[(int) $args[0]];
		return Histone_RTTI::getUndefined();
	});

	Histone_RTTI::registerMember('Histone_Global', 'weekDayNameLong', function($self, $args) {
		if (isset($args[0]) && isset(Histone_Global::$WEEK_DAYS_LONG[(int) $args[0]]))
			return Histone_Global::$WEEK_DAYS_LONG[(int) $args[0]];
		return Histone_RTTI::getUndefined();
	});

	Histone_RTTI::registerMember('Histone_Global', 'monthNameShort', function($self, $args) {
		if (isset($args[0]) && isset(Histone_Global::$MONTH_NAMES_SHORT[(int) $args[0]]))
			return Histone_Global::$MONTH_NAMES_SHORT[(int) $args[0]];
		return Histone_RTTI::getUndefined();
	});

	Histone_RTTI::registerMember('Histone_Global', 'monthNameLong', function($self, $args) {
		if (isset($args[0]) && isset(Histone_Global::$MONTH_NAMES_LONG[(int) $args[0]]))
			return Histone_Global::$MONTH_NAMES_LONG[(int) $args[0]];
		return Histone_RTTI::getUndefined();
	});

	Histone_RTTI::registerMember('Histone_Global', 'max', function($self, $args) {
		$findMaximal = function($values) use (&$findMaximal) {
			$maxValue = Histone_RTTI::getUndefined();
			foreach ($values as $currValue) {
				if (is_array($currValue))
					$currValue = $findMaximal($currValue);
				if (is_int($currValue) || is_float($currValue)) {
					if ($maxValue instanceof Histone_Undefined ||
						$currValue > $maxValue) {
						$maxValue = $currValue;
					}
				}
			}
			return $maxValue;
		};
		return $findMaximal($args);
	});

	Histone_RTTI::registerMember('Histone_Global', 'min', function($self, $args) {
		$findMinimal = function($values) use (&$findMinimal) {
			$minValue = Histone_RTTI::getUndefined();
			foreach ($values as $currValue) {
				if (is_array($currValue))
					$currValue = $findMinimal($currValue);
				if (is_int($currValue) || is_float($currValue)) {
					if ($minValue instanceof Histone_Undefined ||
						$currValue < $minValue) {
						$minValue = $currValue;
					}
				}
			}
			return $minValue;
		};
		return $findMinimal($args);
	});

	Histone_RTTI::registerMember('Histone_Global', 'dayOfWeek', function($self, $args) {
		$year = @$args[0];
		$month = @$args[1];
		$day = @$args[2];
		if (is_numeric($year) && is_numeric($month) && is_numeric($day) &&
			(int)$year == $year && (int)$month == $month && (int)$day == $day) {
			$year = intval($year);
			$month = intval($month);
			$day = intval($day);
			$date = array($year, $month, $day);
			$date = @strtotime(implode('-', $date));
			$date = @getdate($date);
			if ($date['year'] === $year &&
				$date['mon'] === $month &&
				$date['mday'] === $day) {
				$day = $date['wday'];
				return ($day ? $day : 7);
			}
		}
		return Histone_RTTI::getUndefined();
	});

	Histone_RTTI::registerMember('Histone_Global', 'daysInMonth', function($self, $args) {
		$year = @$args[0];
		$month = @$args[1];
		if (is_numeric($year) && is_numeric($month) &&
			(int)$year == $year && (int)$month == $month &&
			$month > 0 && $month < 13) {
			$year = intval($year);
			$month = intval($month);
			return cal_days_in_month(
				CAL_GREGORIAN,
				$month, $year
			);
		}
		return Histone_RTTI::getUndefined();
	});

	Histone_RTTI::registerMember('Histone_Global', 'rand', function($self, $args) {
		$min = @$args[0]; $max = @$args[1];
		if (!is_numeric($min) || (int)$min != $min) $min = 0;
		if (!is_numeric($max) || (int)$max != $max) $max = pow(2, 32) - 1;
		return rand($min, $max);
	});

	Histone_RTTI::registerMember('Histone_Global', 'loadText', function($self, $args, $runtime) {
		return Histone::loadResource($args[0]);
	});

	Histone_RTTI::registerMember('Histone_Global', 'loadJSON', function($self, $args, $runtime) {
		$data = Histone::loadResource($args[0]);
		return json_decode($data, true);
	});

	Histone_RTTI::registerMember('Histone_Global', 'require', function($self, $args, $runtime) {
		$template = Histone::loadResource($url = $args[0]);
		$template = Histone_Parser::parse($template, '');

		$moduleRuntime = new Histone_Runtime($url, Histone_RTTI::getUndefined());
		$result = Histone_Processor::processNode($template, $moduleRuntime);
		if ($result instanceof Histone_Return) $result = $result->value;

		return $result;

		// return json_decode($data, true);
	});

?>