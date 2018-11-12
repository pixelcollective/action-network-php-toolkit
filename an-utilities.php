<?php

class actionnetwork_toolkit {
  
  // this stuff needs to be edited
	var $api_key = "***************************"; 
	var $originating_system = "********";
	var $identifier = "*********";
	
	var $ch;
	var $url = 'https://actionnetwork.org/api/v1';
	function __construct() {
		
		$this->ch = curl_init();
		curl_setopt($this->ch,CURLOPT_HTTPHEADER,array("api-key:$this->api_key","Content-Type: application/json"));
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 100);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_error($this->ch);
		curl_setopt($this->ch, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, '/tmp/cookies_file');
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, '/tmp/cookies_file');
	}
	
	function get_petition_id($petition_summary) {
		
		$endpoint = "petitions/?filter=summary eq '$petition_summary'";
		curl_setopt($this->ch, CURLOPT_URL, "$this->url/$endpoint");
		
		$json_response = curl_exec($this->ch);
		$response = json_decode($json_response);
		$http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		return $response;		
	}	
  
	function get_petition($petition_summary,$petition_id) {
		
		if($petition_summary) $petition_id = $this->get_petition_id($petition_summary);	
		
		$endpoint = "petitions/$petition_id";
		curl_setopt($this->ch, CURLOPT_URL, "$this->url/$endpoint");
		$json_response = curl_exec($this->ch);
		$response = json_decode($json_response);
		$http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		return $response;
	}
  
	function get_signer_count($petition_id) {
		$endpoint = "petitions/$petition_id";
		curl_setopt($this->ch, CURLOPT_URL, "$this->url/$endpoint");
		$json_response = curl_exec($this->ch);
		$response = json_decode($json_response);
		$count = $response->total_signatures;
		return $count;
	}
  
	function sign_petition($petition_id,$person) {
		$data = array(
		  'originating_system' => $this->originating_system,
			'person' => array(
        "family_name" => $person['family_name'],
			  "given_name" => $person['given_name'],
				"postal_addresses" => array(array("postal_code" => $person['postal_code'])),
				"email_addresses" => array(array("address" => $person['email_address']))
			)
		);
    
		$endpoint = "petitions/$petition_id/signatures/";
		curl_setopt($this->ch, CURLOPT_URL, "$this->url/$endpoint");
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($data));
		$json_response = curl_exec($this->ch);
		$response = json_decode($json_response);
		$http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		print_r($response);
		return $response;
	}
  
	function petition_migration($petition_id,$filename) {
		$upload[] = $this->parse_csv($filename);
		
		foreach($upload as $person) :
			foreach($person as $signer) :
				$response[] = $this->sign_petition($petition_id,$signer);
			endforeach;
		endforeach;
    
		return $response;	
	}
  
	function parse_csv($filename) {
	    $mappings = array();
	    $csv = fopen($filename, "r"); 
	    $data = fgetcsv($csv, filesize($filename)); 
	    $mappings = $data;
	 
	    while($data = fgetcsv($csv, filesize($filename))) :
	        if($data[0]) :
	            foreach($data as $key => $value) :
	            	$converted_data[$mappings[$key]] = $value; 
	            endforeach;
	            $table[] = $converted_data;
	        endif; 
	    endwhile;
      
	    fclose($csv); 
	    return $table;
	}
  
	function close_connection() {
		curl_close($this->ch);
	}
}
