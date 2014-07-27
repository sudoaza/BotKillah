<?php

function get_me_pieces($string) {

	$string = preg_replace('/(\d)([,.-]+)(\d)/m','$1$3',$string);
	
	$pieces = preg_split('/([.,!?-]+)(\s)/m',$string);
	$matches = array();
	preg_match_all('/[#$@]{1}\S+/m',$string,$matches);
	$pieces = array_merge($matches[0],$pieces);


	return $pieces;

}

var_dump( get_me_pieces('habia una ves -0---000000,00.29. testssssa. @idfspoke #google $mon'));



