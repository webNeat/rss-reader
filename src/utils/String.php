<?php
namespace rss\utils;

class String {
	public static function camlCaseToUnderscores($input){
		preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
		$ret = $matches[0];
		foreach ($ret as &$match) {
			$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
		}
		return implode('_', $ret);
	}

	public static function underscoresToCamlCase($input, $lower = false){
		$camlCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $input)));
		if($lower)
			$camlCase = strtolower($camlCase[0]) . substr($camlCase, 1);
		return $camlCase;
	}
}