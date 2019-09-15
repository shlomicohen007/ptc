<?

	
	ob_start();

	$res = array();
	
	function res($a) {
		exit(json_encode($a));
	}

	$error = array( 
		"status" => 200,
		"error_code" => 1,
		"error" => "no_token"
	);

	$reg_code = ( isset($_GET["reg_code"]) ) ? $_GET["reg_code"] : false ;
	$token = ( isset($_GET["token"]) ) ? $_GET["token"] : false ;
	
	if ( $token == false || $reg_code == false ) {
		res($error);
	}
	
	///include 'inc/db.php';
	include 'inc/simple_html_dom.php';


	//check token in db
	

	$html = file_get_html("https://nacionalidade.justica.gov.pt/");

	$form = $html->find('#form0');
	$results = array();

	$form = array(
		"url" => "https://nacionalidade.justica.gov.pt/Home/GetEstadoProcessoAjax",
		"method" => "POST",
		"inputs" => array(
			"SenhaAcesso" => $reg_code
		),
	);

	$nodes = $html->find("input[type=hidden]");

	foreach ($nodes as $node) {
		$val = $node->value;
		$name = $node->name;
		$form["inputs"][$name] = $val;
	}


	//curl
	$url = $form["url"];
	$postvars = http_build_query($form["inputs"]);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, count($form["inputs"]));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result_post = curl_exec($ch);

	//return print $result_post;

	//result to array
	$reslt = explode('<',$result_post);

	if ( isset($reslt[84]) ) {
		//code ok
		
		$reg_status_code = 0;
		
		$reg_id = str_replace('div id="bloc1">',"",$reslt[6]);
		$reg_name = str_replace('div style="color:#335779; font-size:1.3em;">',"",$reslt[13]);
		$reg_status_name = strip_tags(str_replace('div style="font-weight: bold;">',"",$reslt[11]));
		$reg_status_name = str_replace('&#243;'," ",$reg_status_name);
		
		//match result
		preg_match_all('#<div[^>]*>(.+?)</div>#is', $result_post, $matches);
		 
		foreach ( $matches[0] as $stage ) {
			
			if (stripos($stage, 'active3') !== false) {
				preg_match_all('#<div class="step[^>]*>(.+?)</div>#is', $stage, $matches1);
				$reg_status_code = intval(trim(str_replace('<div class="step-icon">',"",$matches1[1][0])));
			}
		}
		

		$reg_status_desc = str_replace('div class="container">',"",$reslt[84]);

		$res = array(
			"status" => 200,
			"reg_id" => $reg_id,
			"status_code" => 1,
			"reg_code" => $reg_code,
			"name" => $reg_name,
			"status_name" => $reg_status_name,
			"status_desc" => utf8_encode($reg_status_desc),
			"status_code" => $reg_status_code
		);

	} else {
		$res = array(
			"status" => 200,
			"status_code" => 2,
			"reg_code" => $reg_code
		);
	}

	
	exit(json_encode($res));

?>