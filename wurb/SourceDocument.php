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

require_once('SourceException.php');
require_once('SourceElement.php');

/**
 *  A source file.
 *
 *  @author <a href="mailto:uwe@uwemeding.com">Uwe B. Meding</a>
 */


class SourceDocument {


    private $text;
    private $otherProps;
    private $elements;

    private $guardedBegin;
    private $guardedBeginLen;

    private $guardedEnd;
    private $guardedEndLen;


    public function __construct($text, &$otherProps, $lineComment='//') {
	//$this->guardedBegin = $lineComment.' Code generated by wurblet. Do not edit!/{{{';
	//$this->guardedEnd = $lineComment.' End of wurblet generated code./}}}';
	$this->guardedBegin = $lineComment.' <editor-fold defaultstate="collapsed" desc="Code generated by wurblet. Do not edit!">//{{{';
	$this->guardedEnd = $lineComment.' </editor-fold>//}}}';

	$this->guardedBeginLen = strlen($this->guardedBegin);
	$this->guardedEndLen = strlen($this->guardedEnd);

	$this->otherProps = &$otherProps;
	$this->setText($text, $lineComment);
    }

    /**
     *  Inserts a guarded section
     *  @param 
     *  @return 
     */
    public function insertGuarded($type, $guardedName, $str, $elemNdx) {

	// find the indent
	$indent = 0;
	$slen = strlen($str);
	for($i=0; $i < $slen; $i++) {
	    switch($str[$i]) {
		case " ":
		case "\t":
		    $indent++;
		break;

		case "\n":
		    $indent = 0;
		break;

		default:
		// something else, force end of loop
		$i = $slen;
		break;
	    }
	}

	$indentBuf = '';
	while($indent-- > 0) $indentBuf .= ' ';

	$str = "\n".$indentBuf.$this->guardedBegin.$guardedName."\n\n".$str
	    ."\n".$indentBuf.$this->guardedEnd."$guardedName\n";

	$size = count($this->elements);
	if($size == 0) {
	    // pathological case
	    $this->text = $str.$this->text;

	    $elem = new SourceElement($this, $type, 0, strlen($str), $this->otherProps);
	    $elem->setGuardedName($guardedName);
	    $this->elements[] = $elem;
	} if($elemNdx >= $size) {
	    // append at end
	    $begin = strlen($this->text);
	    $this->text .= $str;
	    $end = strlen($this->text);

	    $elem = new SourceElement($this, $type, $begin, $end, $this->otherProps);
	    $elem->setGuardedName($guardedName);
	    $this->elements[] = $elem;
	} else {
	    // somewhere inbetween (standard case)
	    $insElem = $this->elements[$elemNdx];
	    $begin = $insElem->getBegin();
	    $len = strlen($str);
	    $end = $begin + $len;
	    // insert str
	    $this->text = substr($this->text, 0, $begin).$str.substr($this->text, $begin);

	    $elem = new SourceElement($this, $type, $begin, $end, $this->otherProps);
	    $elem->setGuardedName($guardedName);

	    // insert into elements list
	    $elements = array();
	    $pos = 0;
	    foreach($this->elements as $e) {
		if($elemNdx == $pos++) {
		    $elements[] = $elem;
		}
		$elements[] = $e;
	    }
	    $this->elements = $elements;

	    for($i=$elemNdx+1; $i < count($this->elements); $i++) {
		$insElem = $this->elements[$i];
		$insElem->setBegin($insElem->getBegin() + $len);
		$insElem->setEnd($insElem->getEnd() + $len);
	    }
	}
    }


    public function deleteGuarded($guardedName, $startIndex=0) {
	for($i=$startIndex; $i < count($this->elements); $i++) {
	    $elem = $this->elements[$i];
	    if($elem->isGuarded() && $elem->getGuardedName()==$guardedName) {
		$this->deleteElement($i);

		/**
		 *  Because the guarded blocks begin with a single
		 *  line comment which is indended by a newline +
		 *  <spaces> the code-block before must be made
		 *  smaller to cut off this garbage. Otherwise the
		 *  generated sources would get longer and longer...
		 *  ;-)
		 */
		if($i > 0) {
		    $elem = $this->elements[$i - 1];
		    if($elem->isWhiteEmpty()) {
			// whitespace only - remove
			$i--;
			$this->deleteElement($i);
		    } else {
			$elemText = $elem->getText();
			$len = strlen($elemText);
			$ndx = last_index_of($elemText, "\n"); // last newline -> start of guarded section
			if($ndx >= 0) {
			    // check that the characters following ndx
			    // are indeed whitespaces
			    for($n=$ndx; $n < $len; $n++) {
				switch($elemText[$n]) {
				case ' ':
				case "\t":
				case "\n":
				case "\r":
				    break;
				default:
				    return($i); // WTF?
				}

			    }
			    $elem->deleteText($ndx, $len);
			}
		    }
		}
		return($i);
	    }
	}
	return(-1);
    }


    public function deleteText($start, $end) {
	$this->text = substr($this->text, 0, $start).substr($this->text, $end);
	$diff = $end - $start;
	foreach($this->elements as $elem) {
	    if($elem->getEnd() > $start) {
		if($elem->getBegin() >= $end) {
		    $elem->setBegin($elem->getBegin() - $diff);
		}
		$elem->setEnd($elem->getEnd() - $diff);
	    }
	}
    }

    public function replaceGuarded($type, $guardedName, $str) {
	$ndx = $this->deleteGuarded($guardedName);
	if($ndx >= 0) {
	    $this->insertGuarded($type, $guardedName, $str, $ndx);
	}
	return($ndx);
    }


    public function deleteElement($elemNdx) {
	if($elemNdx >= 0 && $elemNdx < count($this->elements)) {
	    $elem = $this->elements[$elemNdx];
	    $begin = $elem->getBegin();
	    $end = $elem->getEnd();
	    $len = $end - $begin;
	    $this->text = substr($this->text, 0, $begin).substr($this->text, $end);
	    // remove an entry and shift the remaining entries
	    unset($this->elements[$elemNdx]);
	    $elements = array();
	    foreach($this->elements as $elem) {
		$elements[] = $elem;
	    }
	    $this->elements = $elements;
	    for($i = $elemNdx; $i < count($this->elements); $i++) {
		$elem = $this->elements[$i];
		$elem->setBegin($elem->getBegin() - $len);
		$elem->setEnd($elem->getEnd() - $len);
	    }
	}
    }

    public function getText() {
	return($this->text);
    }

    public function setText($text, $lineComment) {
	$this->text = $text;

	$this->elements = array();

	// first pass: extract the comment block only

	$begin = 0;
	$type = TYPE_UNKNOWN;
	$remains = strlen($text);

	for($pos=0; $pos<strlen($text);$pos++,$remains--) {
	    if($type == TYPE_UNKNOWN) {
		$c = $text[$pos];
		if($c == $lineComment[0] && $remains > 0) {
		    $c = $text[$pos+1];
		    if($c == $lineComment[1]) {
			$type = TYPE_SINGLE_COMMENT;
			$begin = $pos;
		    } else if($c == '*') {
			$type = TYPE_BLOCK_COMMENT;
			$begin = $pos;
		    }
		}
	    } else {
		$endFound = false;
		if($type == TYPE_SINGLE_COMMENT) {
		    if($text[$pos] == "\n") {
			// group several single line comments
			if($remains < 2 || $text[$pos+1]!=$lineComment[0] || $text[$pos+2] != $lineComment[1]) {
			    $endFound = true;
			}
		    }
		} else {
		    // block style comment
		    $endFound = $remains>1 && $text[$pos]=='*' && $text[$pos+1]=='/';
		    if($endFound) {
			$pos++;
			$remains--;
		    }
		}

		if($endFound) {
		    $elem = new SourceElement($this, $type, $begin, $pos+1,$this->otherProps);
		    $this->elements[] = $elem;
		    $begin = $pos + 1;
		    $type = TYPE_UNKNOWN;
		}
	    }
	}

	// echo "[1>>".print_r($this->elements,true)."\n";
	// now check the single line comments for guarded patterns
	$guardedList = array();
	$guardedName = null;
	$guardedStart = null;
	$guardedType = 0;
	foreach($this->elements as $elem) {
	    if($guardedName != null) {
		// looking for the end of guarded block
		if($elem->getType() == TYPE_SINGLE_COMMENT) {
		    $elemText = $elem->getText();
		    
		    $substr_len = min(strlen($elemText), $this->guardedEndLen);
		    if($guardedType == TYPE_GUARDED
			&& 0==substr_compare($elemText, $this->guardedEnd, 0, $substr_len)
			&& trim(substr($elemText, $this->guardedEndLen))==$guardedName ) {

			// consolidate all the segments in one guarded
			// segment
			$guardElem = new SourceElement($this, $guardedType, 
				$guardedStart->getBegin(), $elem->getEnd(),
				$this->otherProps);
			$guardElem->setGuardedName($guardedName);
			$guardedList[] = $guardElem;
			$guardedName = null;
			$guardedType = 0;
		    }
		}
	    } else {

		// extract the next guarded block
		if($elem->getType() == TYPE_SINGLE_COMMENT) {
		    $elemText = $elem->getText();
		    $substr_len = min(strlen($elemText), $this->guardedBeginLen);
		    if(0 == substr_compare($elemText, $this->guardedBegin, 0, $substr_len)) {
			$guardedName = trim(substr($elemText, $substr_len));
			$guardedStart = $elem;
			$guardedType = TYPE_GUARDED;
		    }
		}
		if($guardedName == null) {
		    $guardedList[] = $elem;
		}
	    }
	}
	$this->elements = $guardedList;
	//echo "[2>".print_r($this->elements,true)."\n";

	// phase 3: insert code elements for everythign else
	$docList = array();
	$pos = 0;
	foreach($this->elements as $elem) {
	    if($elem->getBegin() > $pos) {
		$docList[] = new SourceElement($this, TYPE_CODE, $pos, $elem->getBegin(), $this->otherProps);
	    }
	    $docList[] = $elem;
	    $pos = $elem->getEnd();
	}

	if($pos < strlen($text)) {
	    $docList[] = new SourceElement($this, TYPE_CODE, $pos, strlen($text), $this->otherProps);
	}
	$this->elements = $docList;
	//echo "[3>".print_r($this->elements,true)."\n";

    }

    private function dumpElements($note) {
	echo "#### $note -- SourceElement sections:\n";
	$cnt = 0;
	foreach($this->elements as $elem) {
	    $type = gettype($elem);
	    echo "##### $cnt element '$type'\n";
	    echo "[>>$elem<]\n";
	    $cnt++;
	} 
	echo "##############################################\n\n";
    }

    public function getElements() {
	return($this->elements);
    }

    public function getElementAt($pos) {
	return($this->elements[$pos]);
    }

    public function setElements($elements) {
	$this->elements = $elements;
    }
}

?>
