<?php
	namespace kbw;
   function encode_json($str){
		$code = json_encode($str);
		return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', create_function('$matches', 'return iconv("UCS-2BE","UTF-8",pack("H*", $matches[1]));'), $code);
		//return preg_replace_callback("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", $code);
	}
 