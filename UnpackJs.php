<?php
class UnpackJs {
	
	public $str;
	public $option;
	
	public function __construct($opts = array()) {
		//TODO: $this->options
	}
	
	public function unpack($pack) {
		
		//Check string length
		if(strlen($pack) < 1) {
			throw new Exception("Empty string...");
		}

		//Search the "packer/d" prototype
		$proto = "eval(function(p,a,c,k,e,";
		if(substr(trim($pack), 0, strlen($proto)) != $proto) {
			throw new Exception("It doesnt' seems to be a p,a,c,k,e,d/r function...");
		}

		//Grab the parameters
		$preg = "@}\('(.*)', *(\d+), *(\d+), *'(.*)'\.split@";	
		preg_match_all($preg, $pack, $match, PREG_SET_ORDER);
		$match = $match[0];

		//Functions
		$_f = $match[1];
		
		//Base (62, 95...)
		$_b = $match[2];

		//Max functions in this pack
		$_m = $match[3];

		//Keywords
		$_k = explode("|",$match[4]);

		//Check if we have all the keywords
		if(count($_k) != $_m) {
			throw new Exception("It seems that the functions (".count($_k).") are different (".$_m.").");
		}

		//If base > 62, we can't work (actually...)
		if($_b > 62) {
			throw new Exception("You try to parse a ".$_b." base...");
		}

		//Base 62
		$_p = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		
		//Max iterations
		$i = intval($_m/$_b) + 1;

		//Init array
		$_t = array();

		//Building function array
		for($j=0; $j<$i; $j++) {
			for($k=0; $k < $_b; $k++) {
				$l = substr($_p,$k,1);
				$n = $_b*$j+$k;
				if($j > 0) $l = $j.substr($_p,$k,1);
				if($n < $_m) $_t[$l] = $_k[$n];
			}
		}
		
		//Now we have an array (_t) with all the function with key

		//Search all masked functions
		$preg = "/[a-zA-Z0-9]+\b/";
		preg_match_all($preg, $_f, $match);
		$match = $match[0];
		
		
		//print_r($_t);
		//die();

		// Replacement in the function
		$str = $_f;
		foreach($match as $d) {
			if($_t[$d] != '') {
				// we replace only masked functions
				$str = preg_replace("/\b".$d."\b/", $_t[$d], $str);
			}
		}

		//$this->str is the new complete function;
		return $this->str = $this->indent($str);
		
	}
	
		
	//Make tab simulation
	private function makespaces($j) {
		$m = $j*4; // 1 tab = 2 spaces
		$spaces = '';
		for($i=0; $i<$m; $i++) {
			$spaces .= ' ';
		}
		return $spaces;
	}
	
	// We try to indent the code
	private function indent($str) {
		
		$result = '';

		$str = str_replace("\\'", "'", $str);
		$str = str_replace('\\', '', $str);

		//Don't eval the code...
		$str = htmlentities($str);
		
		$result .= PHP_EOL;

		$_of = 0; // function
		$_ov = 0; // ;
		$_op = 0; // (
		$_oc = 0; // {
		$_oe = 0; // &
		$_og = 0; // "
		$_ob = 0; // block if, for, while...
		$_oi = 0; // indentation


		//Char by char...
		for($i = 0; $i < strlen($str); $i++) {

			$c = substr($str, $i, 1);

			if(substr($str, $i, 3) == 'for') {
				$_ob = 1;
			}
			if($_ob && $c == ')') {
				$_ob = 0;
			}
			if($c == '(') {
				$_op++;
			}
			if($c == ')') {
				$_op--;
				if($_op < 0) $_op = 0;
			}
			if($c == "&") {
				$_oe = 1;
				if(substr($str, $i, 6) == '&quot;') {
					$_og = !$_og;
				}
			}
			if($c == '}') {
				$result .= PHP_EOL;
				$_oi--;
				$result .= $this->makespaces($_oi);
			}

			//Show char
			$result .= $c;

			if($c == ',' && !$_op) {
				$result .= PHP_EOL;
				$result .= $this->makespaces($_oi);
			}
			if($c == ';' && !$_ob && !$_oe && !$_og) {
				$result .= PHP_EOL;
				$result .= $this->makespaces($_oi);
			}
			if($c == '{') {
				$result .= PHP_EOL;
				$_oi++;
				$result .= $this->makespaces($_oi);
			}
			if($c == '}' && substr($str, $i+1, 1) != ';' && substr($str, $i+1, 1) != ')' && substr($str, $i+1, 4) != 'else' && substr($str, $i+1, 5) != 'catch') {
				$result .= PHP_EOL;
				$result .= $this->makespaces($_oi);
			}

			if($c == ';' && $_oe) {
				$_oe = 0;
			}
		}
		
		return $result;

	}
	
}
