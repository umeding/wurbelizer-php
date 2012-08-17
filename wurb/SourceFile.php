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

class SourceFile {

    private $filename;
    private $orgText;
    private $outStream;

    private static $files = array();


    /**
     *  Construct a new source file
     *  @param $filename is the filename
     */
    private function __construct($filename) {
	$this->filename = $filename;
	self::$files[] = $this;
    }

    public static function open($filename) {
	foreach(self::$files as $sf) {
	    if($sf->filename == $filename) {
		return($sf);
	    }
	}

	$sf = new SourceFile($filename);
	// read the source file content
	if(file_exists($filename)) {
	    $sf->orgText = file_get_contents($filename);
	} else {
	    $sf->orgText = null;
	}
	return($sf);
    }

    /**
     *  Get the orginal text
     *  @return the text
     */
    public function getOrgText() {
	return($this->orgText);
    }


    public function getStream() {
	if($this->outStream == null) {
	    $this->outStream = fopen('data://text/plain,', 'w');
	}
	return($this->outStream);
    }

    public function getNewText() {
	if($this->outStream == null) {
	    return(null);
	}
	$stat = fstat($this->outStream);
	fseek($this->outStream, 0);
	$content = fread($this->outStream, $stat['size']);
	return($content);
    }

    public function needsFlush() {
	if($this->orgText == null) {
	    return($this->outStream != null);
	} else {
	    $newText = $this->getNewText();
	    return($newText!=null && $this->orgText!=$newText);
	}
    }

    public function flush() {
	$fp = fopen($this->filename, 'w');
	$text = $this->getNewText();
	if($text) {
	    fwrite($fp, $text);
	}
	fclose($fp);
    }

    public function close() {
	if($this->needsFlush()) {
	    $this->flush();
	}
    }

    public static function initialize() {
	self::$files = array();
    }

    /**
     *  Rewinds all source files (empty them out)
     */
    public static function resetAll() {
	foreach(self::$files as $sf) {
	    if($sf->outStream != null) {
		ftruncate($sf->outStream, 0);
	    }
	}
    }

    public static function closeAll() {
	foreach(self::$files as $sf) {
	    $sf->close();
        }
	self::initialize();
    }
}
?>
