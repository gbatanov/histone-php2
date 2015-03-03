<?php
	require_once('src/Histone.class.php');
	header('Content-type: text/html; charset=utf-8');
?>

<link rel="stylesheet" href="style.css" />

<?php


	$template = file_get_contents('template.tpl');
	$template = $Histone($template);
	echo '<PRE>' . json_encode($template->getAST(), 0 | JSON_UNESCAPED_UNICODE) . '</PRE>';


	$Histone::setResourceLoader(function($resourceURI) {
		if (substr($resourceURI, 0, 5) === 'http:') {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $resourceURI);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$html = curl_exec($ch);
			curl_close($ch);
			return $html;
		} else {
			return file_get_contents($resourceURI);
		}
	});


	$template->render(function($result) {
		echo $result;
	}, [
		hello => 'world',
		foo => 'bar'
	]);

?>