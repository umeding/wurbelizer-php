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

require_once('SourceException.php');

/**
 *  In-Memory files.
 *
 *  @author <a href="mailto:meding@yahoo.com">Uwe B. Meding</a>
 */
class HeapFile {

    private static $heapFiles = array();


    /**
     *  Get a heap file
     *  @param $name is the name
     *  @return the heap file
     */
    public static function get($name) {
	if(array_key_exists($name, self::$heapFiles)) {
	    return(self::$heapFiles[$name]);
	} else {
	    return(null);
	}
    }

    /**
     *  Delete a file from the heap
     *  @param $name is the name of the heap file
     */
    public static function delete($name) {
	unset(self::$heapFiles[$name]);
    }


    private $name;  // name of the heap file
    private $fp;    // internal stream
    private $invocationCount;

    public function __construct($name, $text='') {

	if(self::get($name)) {
	    throw new SourceException("HeapFile: '$name' already exists");
	}
	
	//echo "---> Heap file: $name\n";
	$this->name = $name;
	$this->fp = fopen('data://text/plain,', 'w+');
	$this->invokationCount = 0;

	fwrite($this->fp, $text);

	self::$heapFiles[$name] = $this;
    }


    /**
     *  Reset (empty) the heap files
     */
    public function reset() {
	fflush($this->fp);
	ftruncate($this->fp, 0);
    }

    /**
     *  Get the text from a heap file
     *  @return the text
     */
    public function getText() {
	$stat = fstat($this->fp);
	fseek($this->fp, 0);
	$content = fread($this->fp, $stat['size']);
	return($content);
    }

    /**
     *  Get the reader for the text
     *  @return the reader
     */
    public function getReader() {
	$text = $this->getText();
	$fp = fopen('data://text/plain,'.$text, 'r');
	return($fp);
    }

   
    /**
     *  Get the output stream
     *  @return the output stream
     */
    public function getPrintStream() {
	return($this->fp);
    }

    public function getInvocationCount() {
	return($this->invocationCount);
    }

    public function setInvocationCount($ic) {
	$this->invocationCount = $ic;
    }

    public function __toString() {
	$stat = fstat($this->fp);
	return('HeapFile: "'.$this->name.'", '.$stat['size'].' bytes');
    }
}

?>
