<?php
 
header("Content-type:text/html;charset=utf-8");  

// https://www.programmerclick.com/article/8937943648/

/** 
* Convertir cadena a binario 
* @param type $str 
* @return type 
*/  
function StrToBin($str){  
         // 1. Lista cada personaje  
    $arr = preg_split('/(?<!^)(?!$)/u', $str);  
         //2.paquete de caracteres  
    foreach($arr as &$v){  
        $temp = unpack('H*', $v);   
        $v = base_convert($temp[1], 16, 2);  
        unset($temp);  
    }  
    return join(' ',$arr);  
    //return $arr;  
}  



/** 
 * Hable acerca de convertir binario a cadena 
* @param type $str 
* @return type 
*/  
function BinToStr($str){  
    $arr = explode(' ', $str);  
    foreach($arr as &$v){  
        $v = pack("H".strlen(base_convert($v, 2, 16)), base_convert($v, 2, 16));  
    }  
    return join('', $arr);  
}  




function str2bin($str){
$bin=null;
	//dividir la cadena y pasarla a un array
	$str_arr = str_split($str, 4);
	
	for($i = 0; $i<count($str_arr); $i++)
		//convertir, corregir ceros y concatenar cada subcadena
		$bin = $bin.str_pad(decbin(hexdec(bin2hex($str_arr[$i]))), strlen($str_arr[$i])*8, "0", STR_PAD_LEFT);
	
	//retornar el resultado
	return $bin;
}


echo str2bin(ip2long("8.8.8.8"));  //134744072
echo "\n\n";  

//echo StrToBin ("desarrollo secundario php: www.php2.cc")."\n";


//echo "-------".BinToStr("1110000 1101000 1110000 111001001011101010001100 111001101010110010100001 111001011011110010000000 111001011000111110010001 111011111011110010011010 1110111 1110111 1110111 101110 1110000 1101000 1110000 110010 101110 1100011 1100011")."\n";



?>