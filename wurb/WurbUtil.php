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

// match ${name} style property references
define('VAR_PATTERN_1', '/\${[^}]+}/');
// match $name style property references
define('VAR_PATTERN_2', '/\$\w+/');

class WurbUtil {

    /**
     * Set a global property
     */
    public static function defineGlobalProperty($key, $value) {
        $_SERVER[$key] = $value;
    }

    /**
     *  Scan a property line. The line must not contain newlines.
     *  @param 
     *  @return 
     */
    public static function scanPropertyLine($line, &$props) {

        $pos = strpos($line, '#');
        if($pos !== false) {
            $line = substr($line, 0, $pos);
        }
        $pos = strpos($line, '=');
        if($pos === false) {
            return(null);
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos+1));

        // echo "Setting property $key=$value\n";
        $props[$key] = $value;
        return($key);

    }

    public static function translateVariables($arg, $getenv, $lastLoop, $otherProps) {

        // repetitively loop, until all vars are rplaced
        while(($pat1 = preg_match(VAR_PATTERN_1, $arg, $matches, PREG_OFFSET_CAPTURE)) > 0 ||
                ($pat2 = preg_match(VAR_PATTERN_2, $arg, $matches, PREG_OFFSET_CAPTURE)) > 0 ) {
            if($pat1 > 0) {
                $name = substr($matches[0][0], 2, -1);
                $repl_len = strlen($name)+3;
                $where = $matches[0][1];
            } else {
                $name = substr($matches[0][0], 1);
                $repl_len = strlen($name)+1;
                $where = $matches[0][1];
            }

            $env = $GLOBALS['_SERVER'];
            $repl = isset($env[$name]) ? $env[$name] : null;
            if($repl == null && $otherProps) {
                $repl = isset($otherProps[$name]) ? $otherProps[$name] : '';
            }
            $arg = substr_replace($arg, $repl, $where, $repl_len);
        }
        return($arg);
    }

    public static function translateVars($arg, $otherProps) {
        $arg = self::translateVariables($arg, false, false, $otherProps);
        return(self::translateVariables($arg, true, true, $otherProps));
    }

    /**
     *  Indent the source text
     *  @param $source is the source text
     *  @param $indent is the number of spaces at the start of each line
     *  @return 
     */
    public static function indent($source, $indent) {
        if($source && $indent > 0) {

            $ibuf = '';
            while($indent-- > 0) {
                $ibuf .= ' ';
            }

            $buffer = '';
            $stok = split_string("\n", $source);
            foreach($stok as $line) {
                $buffer .= $ibuf.$line."\n";
            }
            return($buffer);
        }
        return($source);
    }

    /**
     *  Get the reader for a specific file
     *  @param $name is the name of the file
     *  @return the reader
     */
    public static function openReader($name) {
        if($name[0] == '.') {
            $hf = HeapFile::get(substr($name, 1));
            if($hf == null) {
                throw new SourceException("$name: heapfile not found");
            }
            return($hf->getReader());
        } else {
            $fp = fopen($name, 'r');
            if($fp == null) {
                $error = error_get_last();
                throw new SourceException($error['message']);
            }
            return($fp);
        }
    }
}

// split replacement for PHP > 5.3
function split_string($expr, $string) {
    $ret = preg_split("/$expr/", $string);
    return($ret);
}

function index_of(&$haystack, $needle, $offset=0) {
    $pos = strpos($haystack, $needle, $offset);
    if($pos === false) {
        return(-1);
    }
    return($pos);
}

function last_index_of(&$haystack, $needle, $offset=0) {
    $pos = strrpos($haystack, $needle, $offset);
    if($pos === false) {
        return(-1);
    }
    return($pos);
}

function is_whitespace($c) {

    switch($c) {

        case " ": case "\t": case "\r": case "\n":
            return(true);

        default:
        return(false);

    }
}
?>
