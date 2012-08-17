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

require_once('WurbUtil.php');
require_once('SourceException.php');
require_once('ArgScanner.php');
require_once('SourceWurblet.php');
require_once('SourceHereDocument.php');
require_once('SourcePropertyDocument.php');
require_once('SourceDocument.php');

/**
 *  A section in a source document.
 *
 *  @author <a href="mailto:meding@yahoo.com">Uwe B. Meding</a>
 */
define('TYPE_UNKNOWN', 0);
define('TYPE_BLOCK_COMMENT', 1);
define('TYPE_SINGLE_COMMENT', 2);
define('TYPE_GUARDED', 3);
define('TYPE_CODE', 4);

class SourceElement {

    private $doc;		// the source document
    private $type;
    private $otherProps;	// extra properties
    private $begin;		// offset in the document
    private $end;		// end of the document
    private $guardedName;	// the guarded name

    private $wurblets;
    private $hereDocuments;
    private $propertyDocuments;


    public function __construct(SourceDocument $doc, $type, $begin, $end, &$otherProps) {

	$this->doc = $doc;
	$this->type = $type;
	$this->begin = $begin;
	$this->end = $end;
	$this->otherProps = &$otherProps;


	$this->scan();

	if($this->containsPropertyDocuments()) {
	    foreach($this->propertyDocuments as $propDoc) {
		$lines = split_string("[\n\r]", $propDoc->getPropertyText());
		foreach($lines as $line) {
		    WurbUtil::scanPropertyLine($line, $this->otherProps);
		}
	    }
	}
/*	var_dump($this->otherProps);*/
    }

    public function scan() {

	if($this->isComment() || $this->isGuarded()) {
	    $this->wurblets = array();
	    $this->hereDocuments = array();
	    $this->propertyDocuments = array();
	    $text = $this->getText();
	    $start = 0;

	    $text_len = strlen($text);

	    //echo "[>>wurblet reference $text";

	    while(($start = index_of($text, '@wurblet', $start)) >= 0) {
		if($start == 0 || ($start>0 && $text[$start-1]!='\\')) {

		    $argList = array();
		    $wrgList = array();
		    $guardedName = null;
		    $wurbletName = null;

		    $end = $start;
		    for(;;) {
			$end = index_of($text, "\n", $end);
			if($end == -1) {
			    $end = strlen($text); // no terminator
			    break;
			} else {
			    if($end > 0 && $text[$end-1] == '\\') {
				// continuation line
				$end++;
			    } else {
				// eol
				break;
			    }
			}
		    }
		    // skip @wurblet
		    $start += 8;

		    // check whether @wurblet is followed by wurbler
		    // arguments
		    if($text[$start] == '(') {
			$start++;
			$wend = index_of($text, ')', $start);
			if($wend > $start) {
			    $argText = substr($text, $start, $wend-$start);
			    $scanner = new ArgScanner($argText, $this->otherProps);
			    while($arg = $scanner->next()) {
				$wrgList[] = $arg;
			    }
			    $start = $wend+1;
			}
		    }
		    // cut @wurbet section
		    $argText = substr($text, $start, $end-$start);
		    //echo "Arg: textLen=$text_len s=$start e=$end [$argText]\n";
		    $scanner = new ArgScanner($argText, $this->otherProps);
		    while($arg = $scanner->next()) {
			if($guardedName == null) {
			    $guardedName = $arg;
			} else if($wurbletName == null) {
			    $wurbletName = $arg;
			} else {
			    $argList[] = $arg;
			}
		    }
		    $wurblet = new SourceWurblet(
			    $guardedName,
			    $wurbletName,
			    $argList,
			    $wrgList);
		    $this->wurblets[] = $wurblet;
		    $start = $end;
		} else {
		    $start++;
		}
	    }
	    //echo "Wurblet definition:\n".print_r($this->wurblets, true)."\n";

	    // scan for here- and property documents
	    $start = 0;
	    $hereStart = 0;
	    $propStart = 0;
	    while(($hereStart = index_of($text, '@>', $start)) >= 0 ||
		    ($propStart = index_of($text, '@{', $start)) >= 0) {

		if($hereStart >= 0) {
		    $start = $hereStart;
		    $propStart = -1;
		} else {
		    $start = $propStart;
		    $hereStart = -1;
		}

		if($start == 0 || ($start > 0 && $text[$start-1]!='\\')) {
		    // find the lead in string
		    $leadNdx = $start;
		    while($leadNdx >= 0) {
			if($text[$leadNdx] == "\n") {
			    // start of line found
			    break;
			}
			$leadNdx--;
		    }
		    $leadNdx++;
		    $leadLen = $start - $leadNdx;
		    if($leadLen < 0) {
			$leadLen = 0;
		    }

		    // process the document text
		    $end = index_of($text, "\n", $start);
		    if($end >= 0 && $end < $text_len-1) {
			$sub = trim(substr($text, $start+2, $end-$start-2));
			$stok = split_string("[\t ]+", $sub);
			if($propStart>=0 || count($stok)>0) {
			    $filename = $propStart>=0 ? null : $stok[0];
			    // get content
			    $body = substr($text, $end+1);
			    $end = $hereStart >= 0 ? index_of($body, '@<') 
				: index_of($body, '@}');
			    if($end <= 0) {
				throw new SourceException("missing '".($hereStart>=0?"@<":"@}")."' in comment block");
			    }

			    if($body[$end-1] != '\\') {
				// now cut the lead in from every line
				$body = substr($body, 0, $end);
				$lines = split_string("\n", $body);
				$buf = '';
				$delim = '';
				foreach($lines as $line) {
				    $line = substr($line, $leadLen);
				    $buf .= $delim.$line;
				    $delim = "\n";
				}
				$body = str_replace("\\@", "@", $buf);

				if(strlen($body) > 0) {
				    if($hereStart >= 0) {
					$this->hereDocuments[] = new SourceHereDocument($filename, $body);
				    } else {
					$this->propertyDocuments[] = new SourcePropertyDocument($body);
				    }
				}
			    }
			}
		    }
		    $start = $start+$end-1;
		} else {
		    $start++;
		}
	    }
	    // echo print_r($this->hereDocuments, true)."\n";
	    // echo print_r($this->propertyDocuments, true)."\n";
	}

    }

    public function getText() {
	$len = $this->end - $this->begin;
	return(substr($this->doc->getText(), $this->begin, $len));
    }

    public function isCode() {
	return($this->type == TYPE_CODE);
    }

    public function isGuarded() {
	return($this->type == TYPE_GUARDED);
    }

    public function isComment() {
	return($this->type == TYPE_BLOCK_COMMENT || $this->type == TYPE_SINGLE_COMMENT);
    }

    public function containsWurblets() {
	return($this->wurblets != null && count($this->wurblets)>0);
    }

    public function getWurblets() {
	return($this->wurblets);
    }

    public function containsHereDocuments() {
	return($this->hereDocuments != null && count($this->hereDocuments)>0);
    }
    
    public function getHereDocuments() {
	return($this->hereDocuments);
    }

    public function containsPropertyDocuments() {
	return($this->propertyDocuments && count($this->propertyDocuments)>0);
    }

    public function getPropertyDocuments() {
	return($this->propertyDocuments);
    }


    public function isWhiteEmpty() {
	$str = trim($this->getText());
	return(strlen($str) == 0);
    }

    public function getDoc() {
	return($this->doc);
    }

    public function setDoc($doc) {
	$this->doc = $doc;
    }

    public function getType() {
	return($this->type);
    }

    public function setType($type) {
	$this->type = $type;
    }

    public function getBegin() {
	return($this->begin);
    }

    public function setBegin($begin) {
	$this->begin = $begin;
    }

    public function getEnd() {
	return($this->end);
    }

    public function setEnd($end) {
	$this->end = $end;
    }

    public function deleteText($startNdx, $endNdx) {
	$this->doc->deleteText($this->begin+$startNdx, $this->end+$endNdx);
    }

    public function getGuardedName() {
	return($this->guardedName);
    }

    public function setGuardedName($name) {
	$this->guardedName = $name;
    }

    public function __toString() {
	$len = $this->end - $this->begin;
	$text = substr($this->doc->getText(), $this->begin, $len);

	return('type='.$this->type.', text='.$text);
    }
}
?>
