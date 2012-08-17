<?php
/**
 * PHP/Wurbelizer - a light-weight code generator for PHP.
 * The code is very much modeled after the original Java version with
 * a few PHP tweaks.
 * Copyright (c) 2007-2008 Uwe B. Meding, meding@yahoo.com
 *
 * Original copyright:
 *
 * Wurbelizer - a generic lightweight code generator.
 * Copyright (C) 2001-2006 Harald Krake, harald@krake.de, +49 7722 9508-0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once("WurbUtil.php");

/**
 * scans for arguments and does variable translation.
 *
 * Arguments usually are separated by whitespaces.
 * However, sometimes arguments must include whitespaces, i.e.
 * must be quoted. There are two kinds quotes:
 *
 * double-quotes like "test: $remote == --remote". The $remote will be translated.
 * A single-quote in a double-quoted string is allowed.
 *
 * single-quotes like --orderby='" ORDER BY " + FIELD_ID + " DESC"'.
 * No translation will take place and such args may contain double-quotes.
 *
 * Double-quotes in a double-quoted string must be quoted with a backslash.
 * Same is true for single-quotes in a single quoted string.
 * A backslash is written as \\.
 *
 * Notice that variable translation takes place _after_ arguments have been separated.
 * Thus, a variable may contain quotes and these quotes need _not_ be escaped.
 *
 */
class ArgScanner {

    private $str;
    private $otherProps;
    private $ndx;
    private $len;

    public function __construct($line, &$props) {

	$this->str = $line;
	$this->otherProps = $props;
	$this->len = $line ? strlen($line) : 0;
	$this->ndx = 0;
    }

    public function next() {
	$part = null;
	$arg = null;
	$inDoubleQuote = false;
	$inSingleQuote = false;

	// find start of (next) argument (skip whitespace)
	while($this->ndx < $this->len) {
	    $c = $this->str[$this->ndx];
	    if($arg === null) {
		if(is_whitespace($c) == false && $c != '\\') {
		    $arg = '';
		    continue;
		}
	    } else {
		if($part === null) {
		    $part = '';
		}
		// check for escape
		if($c == '\\') {
		    // take the next char as is
		    $this->ndx++;
		    if($this->ndx < $this->len) {
			$part .= $this->str[$this->ndx];
		    }
		} else {

		    if($inDoubleQuote) {
			// next double quote ends string
			if($c == '"') {
			    $inDoubleQuote = true;
			    $arg .= WurbUtil::translateVars($part, $this->otherProps);
			    $part = null;
			} else {
			    // anything else is part of the arg
			    $part .= $c;
			}

		    } else if($inSingleQuote) {
			// next single quote end string
			if($c == '\'') {
			    $inSingleQuote = false;
			    $arg .= $part;
			    $part = null;
			} else {
			    $part .= $c;
			}
		    } else {
			if(is_whitespace($c)) {
			    break;
			}
			if($c == '"') {
			    $inDoubleQuote = true;
			} else if($c == '\'') {
			    $inSingleQuote = true;
			} else {
			    $part .= $c;
			}
		    }
		}
	    }
	    $this->ndx++;
	}

	if($part !== null) {
	    // append last pendeing part
	    if($inSingleQuote) {
		// append untranslated
		$arg .= $part;
	    } else {
		// append translated
		$arg .= WurbUtil::translateVars($part, $this->otherProps);
	    }
	}

	return($arg);
    }
}
?>
