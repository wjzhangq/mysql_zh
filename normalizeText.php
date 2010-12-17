<?php
// " MATCH($field) AGAINST('$searchon' IN BOOLEAN MODE) ";
//"SHOW GLOBAL VARIABLES LIKE 'ft\\_min\\_word\\_len'";

class normalizeText{
	static $mMinSearchLength;
	var $strictMatching = true;
	var $searchTerms = array();
	
	function __construct(){
		self::$mMinSearchLength = 4;
	}
	
	function normalize($str){
		//word segmentation
		$out = $this->wordSegmentation($str);
		
		// MySQL fulltext index doesn't grok utf-8, so we
		// need to fold cases and convert to hex
		$out = preg_replace_callback(
			"/([\\xc0-\\xff][\\x80-\\xbf]*)/",
			array($this, 'stripForSearchCallback' ),
			$this->lc($out));
			
		// And to add insult to injury, the default indexing
		// ignores short words... Pad them so we can pass them
		// through without reconfiguring the server...
		$minLength = $this->minSearchLength();
		if( $minLength > 1 ) {
			$n = $minLength - 1;
			$out = preg_replace(
				"/\b(\w{1,$n})\b/",
				"$1u800",
				$out );
		}
		
		// Periods within things like hostnames and IP addresses
		// are also important -- we want a search for "example.com"
		// or "192.168.1.1" to work sanely.
		//
		// MySQL's search seems to ignore them, so you'd match on
		// "example.wikipedia.com" and "192.168.83.1" as well.
		$out = preg_replace(
			"/(\w)\.(\w|\*)/u",
			"$1u82e$2",
			$out );
		
		return $out;
	}
	
	function resume($str){
		
	}
	
	/** 
	 * Parse the user's query and transform it into an SQL fragment which will 
	 * become part of a WHERE clause
	 */
	function parseQuery( $filteredText) {
		$lc = $this->legalSearchChars(); // Minus format chars
		$searchon = '';
		$this->searchTerms = array();

		# FIXME: This doesn't handle parenthetical expressions.
		$m = array();
		if( preg_match_all( '/([-+<>~]?)(([' . $lc . ']+)(\*?)|"[^"]*")/',
			  $filteredText, $m, PREG_SET_ORDER ) ) {
			foreach( $m as $bits ) {
				@list( /* all */, $modifier, $term, $nonQuoted, $wildcard ) = $bits;
				
				if( $nonQuoted != '' ) {
					$term = $nonQuoted;
					$quote = '';
				} else {
					$term = str_replace( '"', '', $term );
					$quote = '"';
				}
			
				if( $searchon !== '' ) $searchon .= ' ';
				if( $this->strictMatching && ($modifier == '') ) {
					// If we leave this out, boolean op defaults to OR which is rarely helpful.
					$modifier = '+';
				}
				
				// Some languages such as Serbian store the input form in the search index,
				// so we may need to search for matches in multiple writing system variants.
				$convertedVariants = $term;
				if( is_array( $convertedVariants ) ) {
					$variants = array_unique( array_values( $convertedVariants ) );
				} else {
					$variants = array( $term );
				}
				
				// The low-level search index does some processing on input to work
				// around problems with minimum lengths and encoding in MySQL's
				// fulltext engine.
				// For Chinese this also inserts spaces between adjacent Han characters.
				$strippedVariants = $variants;
				
				// Some languages such as Chinese force all variants to a canonical
				// form when stripping to the low-level search index, so to be sure
				// let's check our variants list for unique items after stripping.
				$strippedVariants = array_unique( $strippedVariants );
				
				$searchon .= $modifier;
				if( count( $strippedVariants) > 1 )
					$searchon .= '(';
				foreach( $strippedVariants as $stripped ) {
					$stripped = $this->normalize( $stripped );
					if( $nonQuoted && strpos( $stripped, ' ' ) !== false ) {
						// Hack for Chinese: we need to toss in quotes for
						// multiple-character phrases since normalizeForSearch()
						// added spaces between them to make word breaks.
						$stripped = '"' . trim( $stripped ) . '"';
					}
					$searchon .= "$quote$stripped$quote$wildcard ";
				}
				if( count( $strippedVariants) > 1 )
					$searchon .= ')';
				
				// Match individual terms or quoted phrase in result highlighting...
				// Note that variants will be introduced in a later stage for highlighting!
				$regexp = $this->regexTerm( $term, $wildcard );
				$this->searchTerms[] = $regexp;
			}
			wfDebug( __METHOD__ . ": Would search with '$searchon'\n" );
			wfDebug( __METHOD__ . ': Match with /' . implode( '|', $this->searchTerms ) . "/\n" );
		} else {
			wfDebug( __METHOD__ . ": Can't understand search query '{$filteredText}'\n" );
		}

		$searchon = addslashes( $searchon );

		return $searchon;
	}	
	
	/**
	 * Eventually this should be a word segmentation;
	 * for now just treat each character as a word.
	 * @todo Fixme: only do this for Han characters...
	 */
	function wordSegmentation( $string ) {
		$reg = "/([\\xc0-\\xff][\\x80-\\xbf]*)/";
		$s = self::insertSpace( $string, $reg );
		return $s;
	}
	
	
	function lc($str){
		return strtolower($str);
	}
	
	/**
	 * Armor a case-folded UTF-8 string to get through MySQL's
	 * fulltext search without being mucked up by funny charset
	 * settings or anything else of the sort.
	 */
	protected function stripForSearchCallback( $matches ) {
		return 'u8' . bin2hex( $matches[1] );
	}
	
	/**
	 * Check MySQL server's ft_min_word_len setting so we know
	 * if we need to pad short words...
	 * 
	 * @return int
	 */
	protected function minSearchLength() {
		if( is_null( self::$mMinSearchLength ) ) {
			$sql = "SHOW GLOBAL VARIABLES LIKE 'ft\\_min\\_word\\_len'";
			
			self::$mMinSearchLength = 0;
		}
		return self::$mMinSearchLength;
	}
	
	protected static function insertSpace( $string, $pattern ) {
		$string = preg_replace( $pattern, " $1 ", $string );
		$string = preg_replace( '/ +/', ' ', $string );
		return $string;
	}
	
	public static function legalSearchChars() {
		return "A-Za-z_'.0-9\\x80-\\xFF\\-";
	}
	
	function regexTerm( $string, $wildcard ) {
		$regex = preg_quote( $string, '/' );
		return $regex;
	}	
}

function wfDebug($str){
	echo $str;
}


?>