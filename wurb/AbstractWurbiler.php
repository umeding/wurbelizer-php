<?php
/**
 * PHP/Wurbelizer - a light-weight code generator for PHP.
 * The code is very much modeled after the original Java version with
 * a few PHP tweaks.
 * Copyright (c) 2007-2008 Uwe B. Meding, uwe@uwemeding.com
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

require_once("Wurbiler.php");
require_once("WurbletData.php");

define('OUTNAME', 'out');

define('META_NONE', 0);
define('META_BLOCK', '[');
define('META_OBJECT', '(');
define('META_COMMAND', '{');

define('CURLY_OPEN', '{');
define('CURLY_CLOSE', '}');
define('ELLIPSE_OPEN', '(');
define('ELLIPSE_CLOSE', ')');
define('BRACKET_OPEN', '[');
define('BRACKET_CLOSE', ']');

class AbstractWurbiler implements Wurbiler {

    private $packageName;
    private $parentClass;
    private $interfaces;
    private $imports;
    private $args;

    private $readers;
    private $paths;
    private $in;
    private $out;
    private $outName;
    private $startAutoIndent;
    private $inAutoIndent;
    private $lastWasOpenBlock;
    private $autoIndent;
    private $indent;
    private $indentStr;
    private $sourceList;
    private $sourceBuf;
    private $sourceLoc;

    private $errors;
    private $metaMode;



    public function __construct($in, $inName, $out) {
	$this->out = $out;
	$this->in = NestedReader::fromHandle($in, $inName);

	$this->readers = array($this->in);
	$this->paths = array();

	$this->setOutName();
	$this->setAutoIndent(true);
	$this->setIndent(0);
	$this->setParentClass(null);
	$this->setPackageName(null);
	$this->setArgs(null);
    }

    public function getWriter() {
	return($this->out);
    }

    public function getReader() {
	return($this->in->reader);
    }

    /**
     *  Close any pending include streams
     */
    private function cleanup() {
    }

    /**
     *  Compiles the wurbelizable.
     */
    public function compile() {
	try {
	    return($this->do_compile());
	} catch(Exception $e) {
	    // we had some error, cleanup and rethrow
	    while(count($this->readers) > 1) {
		fclose($this->in->reader);
		unset($this->readers[0]);
		unset($this->paths[$this->in->path]);
		$this->in = $this->readers[0];
	    }
	    throw $e;
	}
    }

    private function do_compile() {
	$ic = -1;
	$c = 0;
	$inMeta = false;
	$subMeta = 0;
	$metaBuf = '';
	$dontRead = false;
	$blockStart = false;
	$this->errors = 0;
	$this->metaMode = META_NONE;
	$sourceBuf = '';
	$sourceLoc = $this->srcLocation();

	// initialize the source list
	if($this->sourceList) {
	    $this->sourceList = array();
	}

	for(;;) {
	    if($dontRead) {
		$dontRead = false;
	    } else {
		$ic = $this->read();
		if($ic == -1) {
		    break; // EOF
		}
		$c = $ic;
	    }

	    // Handle escapes
	    if($c == '\\') {
		$ic = $this->read();
		if($ic == -1) {
		    break; // eof
		}
		$nc = $ic;
		if($nc == '@') {
		    if($inMeta) {
			if($this->metaMode == META_BLOCK) {
			    fwrite($this->out, $nc);
			} else {
			    $metaBuf .= $c;
			}
		    } else {
			$this->sourceBuf .= $nc;
		    }
		} else if($nc != ' ') {
		    if($inMeta) {
                        if($this->metaMode == META_BLOCK) {
			    fwrite($this->out, $c);
			    fwrite($this->out, $nc);
			} else {
			    $metaBuf .= $c.$nc;
			}
		    } else {
			$this->sourceBuf .= $c.$nc;
		    }
		}

	    } else {

		// core task

		if($c == '@') {
		    $ic = $this->read();
		    $nc = $ic;
		    if($nc==CURLY_OPEN || $nc==ELLIPSE_OPEN || $nc=BRACKET_OPEN) {
			if($inMeta) {
			    // already in meta level
			    $subMeta++;
			    fwrite($this->out, $c.$nc);
			} else {
			    // on source level
			    $this->flushSource();
			    $this->metaMode = $nc;
			    $inMeta = true;
			    $blockStart = $nc == META_BLOCK;
			}
		    } else {
			// some unknown sequence, just keep it
			$this->logWarning('unknown @-sequence at '.$this->srcLocation());
			if($inMeta) {
			    fwrite($this->out, $c);
			} else {
			    $this->sourceBuf .= $c;
			}
			if($ic == -1) {
			    break; // eof
			}
			if($inMeta) {
			    fwrite($this->out, $nc);
			} else {
			    $this->sourceBuf .= $nc;
			}
		    }
		} else if($c == CURLY_CLOSE || $c == ELLIPSE_CLOSE || $c == BRACKET_CLOSE) {

		    // check for '@'
		    $ic = $this->read();
		    if($ic == -1) {
			break; // eof
		    }

		    $nc = $ic;
                    if($nc == '@') {
			if($inMeta) {
			    if($subMeta > 0) {
				// we are on a nested meta level
				$subMeta--;
				fwrite($this->out, $c.$nc);
			    } else {
				// last meta level closed, go back to
				// source level
				$inMeta = false;
				if($this->metaMode == META_OBJECT) {
				    // generate code for the string
				    // value
				    if($this->isAutoIndent()) {
					$this->setIndent($this->autoIndent+($this->lastWasOpenBlock ? 2 : 0));
				    }
				    fwrite($this->out, $this->indentStr.'fwrite($this->'.$this->outName.','.$metaBuf.");\n");
				} else if($this->metaMode == META_COMMAND) {

				    // execute the meta commands
				    $parts = explode(' ', $metaBuf);
				    $cmd = $parts[0];
				    // jump to the command handler
				    $cmdOk = false;
				    switch($cmd) {
				    case "include":
				        $cmdOk = $this->handleInclude($parts);
					break;
				    case "package":
				        $cmdOk = $this->handlePackage($parts);
					break;
				    case "extends":
				        $cmdOk = $this->handleExtends($parts);
					break;
				    case "implements":
				        $cmdOk = $this->handleImplements($parts);
					break;
				    case "imports":
				        $cmdOk = $this->handleImports($parts);
					break;
				    case "indent":
				        $cmdOk = $this->handleIndent($parts);
					break;
				    case "to":
				        $cmdOk = $this->handleOutName($parts);
					break;
				    case "indent":
				        $cmdOk = $this->handleArgs($parts);
					break;

				    default:
				        $this->logError("unknown meta-command '".$cmd."' at ".$this->srcLocation());
					break;
				    }
				    if($cmdOk === false) {
					break; // some error
				    }
				}
				$metaBuf = '';
			    }
			} else {
			    // close meta level encountered on source
			    // level: ignore and log an error
			    $this->logError("meta close encountered on output level '"
				    .$this->getOutName()."' at ".$this->srcLocation());
			    break;
			}
		    } else {
			// some other use of CURLY,ELLIPSE,BRACKET
			// close, push back nc and start over
			if($inMeta) {
			    if($this->metaMode == META_BLOCK) {
				fwrite($this->out, $c);
			    } else {
				$metaBuf .= $c;
			    }
			} else {
			    $this->sourceBuf .= $c;
			}
			$c = $nc;
			$dontRead = true;
			continue;
		    }


		} else {
		    // other character than '@'
		    if($inMeta) {
			if($this->metaMode == META_BLOCK) {
			    if($blockStart) {
				$blockStart = false;
				if($c != "\n") {
				    fwrite($this->out, $c);
				}
			    } else {
				fwrite($this->out, $c);
			    }
			    if($this->isAutoIndent()) {
				// check for auto indent
				if($c == "\n") {
				    $this->startAutoIndent = true;
				} else {
				    if($this->startAutoIndent) {
					$this->autoIndent = 0;
					$this->startAutoIndent = false;
					$this->inAutoIndent = true;
				    }
				    if($this->inAutoIndent) {
					if($c == ' ') {
					    $this->autoIndent++;
					} else {
					    $this->inAutoIndent = false;
					}
				    }
				    $this->lastWasOpenBlock = ($c == CURLY_OPEN);
				}
			    }
			} else {
			    $metaBuf .= $c;
			}
		    } else {
			$this->sourceBuf .= $c;
		    }
		}
	    }
	}

	// If there's something left in the source buffer, append that
	// and write a closing line
	$this->flushSource();

	// do some checks
	if($inMeta) {
	    $this->logError("unclosed meta level (".($subMeta + 1).")"."[".$metaBuf."]");
	}
	if(count($this->sourceList) == 0) {
	    $this->logWarning("no source level");
	}
	return($this->errors);
    }

    private function handleInclude($argv) {
	if(!isset($argv[1])) {
	    $this->logError("no include file defined: ".$this->srcLocation());
	    return(false);
	}
	if(count($argv) > 2) {
	    $this->logError("only one include file allowed: ".$this->srcLocation());
	    return(false);
	}
	$path = $argv[1];
	$path = WurbUtil::translateVars($path, null);

	if(isset($this->paths[$path])) {
	    $this->logError("include-loop while including '$path' at ".$this->srcLocation);
	    return(false);
	}
	$this->paths[$path] = $path;
        $this->in = NestedReader::fromPath($path);
	array_unshift($this->readers, $path); // insert at the beginning
	$this->sourceLoc = $this->srcLocation();


    }

    private function handlePackage($argv) {
	if(!isset($argv[1])) {
	    $this->logError("no package name defined: ".$this->srcLocation());
	    return(false);
	}
	if(count($argv) > 2) {
	    $this->logError("only one package name allowed: ".$this->srcLocation());
	    return(false);
	}
	if($this->packageName != null && $this->packageName!=$argv[1]) {
	    $this->logError("more than one package-command ignored at ".$this->srcLocation());
	    return(false);
	}
	$this->setPackageName($argv[1]);
	return(true);
    }

    private function handleExtends($argv) {
	if(!isset($argv[1])) {
	    $this->logError("no extends name defined: ".$this->srcLocation());
	    return(false);
	}
	if(count($argv) > 2) {
	    $this->logError("only one package name allowed: ".$this->srcLocation());
	    return(false);
	}
	if($this->parentClass != null && $this->parentClass!=$argv[1]) {
	    $this->logError("more than one extends-command ignored at ".$this->srcLocation());
	    return(false);
	}
	$this->setParentClass($argv[1]);
	return(true);
    }

    public function handleImplements($argv) {
	$len = count($argv);
	for($i = 1; $i < $len; $i++) {
	    $this->addInterface($argv[$i]);
	}
    }

    public function handleImports($argv) {
	$len = count($argv);
	for($i = 1; $i < $len; $i++) {
	    $this->addImports($argv[$i]);
	}
    }

    public function handleIndent($argv) {
        if(count($argv) != 2) {
	    $this->logError("no indent specified at ".$this->srcLocation());
	}
	$mode = $argv[1];
	if($mode == 'auto') {
	    $this->setAutoIndent(true);
	} else {
	    $val = intval($mode);
	    if($mode == "$val") {
		$this->setIndent((int)$mode);
		$this->setAutoIndent(false);
	    } else {
		$this->logError("unknown indent mode '$mode' ".$this->srcLocation());
	    }
	}
    }

    public function handleOutName($argv) {
	$this->setOutName($argv[1]);
    }

    public function handleArgs($argv) {
	$len = count($argv);
	for($i = 1; $i < $len; $i++) {
	    $this->argsp[] = $argv[$i];
	}
    }

    /**
     *  Read the next char from the input
     *  @return the next char
     */
    private function read() {
	$c = $this->in->read();
	if($c == -1) {
	    if(count($this->readers) <= 1) {
		return(-1); // EOF on main reader
	    }
	    // EOF on included file
	    array_shift($this->readers);
	    unset($this->paths[$this->in->path]);
	    $this->in = $this->readers[0];
	    $this->flushSource();
	    return($this->read());
	}
	return($c);
    }

    /**
     *  Get a small snippet from the input tring, make sure it is on
     *  one line.
     *  @param $str is the string
     *  @return the hint
     */
    private function hintString($str, $len=20) {
	// split the string along the undesirable characters
	$parts = split_string("[\t\n\r ]+", $str);
	$hint = implode(' ', $parts);
	if(strlen($hint) > $len) {
	    $hint = substr($hint, 0, $len).'...';
	}
	return($hint);
    }

    private function flushSource() {
	if(strlen($this->sourceBuf) > 0) {
	    if(($this->metaMode == META_BLOCK || $this->metaMode == META_COMMAND) &&
		    $this->sourceBuf[0] == "\n") {
		$this->sourceBuf = substr($this->sourceBuf, 1);
	    }
	    if(strlen($this->sourceBuf) > 0) {
		if($this->isAutoIndent()) {
		    $this->setIndent($this->autoIndent+($this->lastWasOpenBlock ? 2 : 0));
		}
		fwrite($this->out, $this->indentStr
			.'fwrite($this->'.$this->outName
			.',$this->source['.count($this->sourceList).']);'
			.' // '.$this->hintString($this->sourceBuf)."\n");

		$this->sourceList[] = $this->sourceBuf;
		$this->sourceBuf = '';
	    }
	}
	$this->sourceLoc = $this->srcLocation();
    }

    private function srcLocation() {
	return($this->in->line.':'.$this->in->pos);
    }

    public function getSourceList() {
	return($this->sourceList);
    }

    public function setSourceList($sourceList) {
	$this->sourceList = $sourceList;
    }

    public function getIndent() {
	return($this->indent);
    }

    public function setIndent($indent) {
	if($indent < 0) {
	    $indent = 0;
	}
	if($this->indent != $indent || $this->indentStr == null) {
	    $this->indent = $indent;
	    $buf = '';
	    while($indent-- > 0) {
		$buf .= ' ';
	    }
	    $this->indentStr = $buf;
	}
    }

    public function setAutoIndent($autoIndent) {
	$this->autoIndent = $autoIndent ? 0 : -1;
	$this->inAutoIndent = false;
    }

    public function isAutoIndent() {
	return($this->autoIndent >= 0);
    }

    public function setOutName($outName=null) {
	$this->outName = $outName == null ? OUTNAME : $outName;
    }

    public function getOutName() {
	return($this->outName);
    }

    public function logError($msg) {
	echo 'Error: '.$msg.' in '.$this->in->path."\n";
	$this->errors++;
    }

    public function logWarning($msg) {
	echo 'Warning: '.$msg.' in '.$this->in->path."\n";
    }

    public function getPackageName() {
	return($this->packageName);
    }

    public function setPackageName($packageName) {
	$this->packageName = $packageName;
    }

    public function getParentClass() {
	return($this->parentClass);
    }

    public function setParentClass($parentClass) {
	$this->parentClass = $parentClass;
    }

    public function addInterface($iface) {
	if($this->interfaces == null) {
	    $this->interfaces = array($iface => $iface);
	} else {
	    $this->interfaces[$iface] = $iface;
	}

    }

    public function addImports($import) {
	if($this->imports == null) {
	    $this->imports = array($import => $import);
	} else {
	    $this->imports[$import] = $import;
	}
    }

    public function getInterfaces() {
	return($this->interfaces ? $this->interfaces : array());
    }

    public function getArgs() {
	return($this->args);
    }

    public function setArgs($args) {
	$this->args = $args;
    }

    public function getImports() {
	return($this->imports ? $this->imports : array());
    }

    public function setImports($imports) {
	$this->imports = $imports;
    }
}

/**
 *  Handles includes
 */
class NestedReader {
    public $reader;	// the reader
    public $path;	// pathname of include file
    public $included;	// true = close at EOF
    public $line;	// line number
    public $pos;	// position in line;


    private function __construct($in, $inName) {
	$this->reader = $in;
	$this->path = $inName;
	$this->line = 1;
    }

    public static function fromHandle($in, $inName) {
	return(new NestedReader($in, $inName));
    }

    public static function fromPath($path) {
	$fp = fopen($path, 'r');
	if(null == $fp) {
	    $error = error_get_last();
	    throw new SourceException($error['message']);
	}
	$in = new NestedReader($fp, $path);
	$in->included = true;
	return($in);
    }


    public function read() {
	$c = fgetc($this->reader);
	$c = $c === false ? -1 : $c;
	if($c == -1 && $this->included) {
	    fclose($this->reader);
	}
	if($c == "\n") {
	    $this->line++;
	    $this->pos = 0;
	} else {
	    $this->pos++;
	}
	return($c);
    }
}

?>
