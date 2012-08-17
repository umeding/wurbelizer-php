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
require_once('Wurbler.php');
require_once('WurbletData.php');

/**
 *  Provides basic functionality of a wurblet container.
 *
 *  @author <a href="mailto:meding@yahoo.com">Uwe B. Meding</a>
 */
abstract class AbstractWurbler implements Wurbler {

    private $wurblet;
    private $otherProps;
    private $source;
    private $out;
    private $args;
    private $fixedArgs;

    /**
     *  Construct a wurbler
     */
    public function __construct() {
	$this->setPrintStream(STDOUT);
	$this->setArgs(null);
    }

    /**
     *  Loads a wurblet.
     *  @param $name is the wurblet name
     *  @param $paths are the path along which it is located
     *  @param $otherProps are extra properties for this wurblet
     */
    public function loadWurblet($name, $paths, $otherProps) {

	$this->otherProps = $otherProps;

	$datafile = null;
	foreach($paths as $path) {
	    $try1 = $path.$name.WRBL_EXTENSION.".php";
	    $try2 = $path.$name.".php";
	    $datafile = $path.$name.FILE_SOURCE_EXTENSION;

	    // try <filename>.wrbl.php and <filename>.php
	    if(file_exists($try1)) {
		$filename = $try1;
	    } else if(file_exists($try2)) {
		$filename = $try2;
	    }
	    if(isset($filename)) {
		if(!class_exists($name)) {
		    // echo "Loading $filename...\n";
		}
		require_once($filename);
		$this->setWurblet(new $name());
		break;
	    }
	}

	if(null == $this->getWurblet()) {
	    throw new SourceException("Unable to locate '$name' wurblet");
	}

	// load the fixed source
	if(file_exists($datafile)) {
	    // echo "Loading $datafile\n";
	    $content = file_get_contents($datafile);
	    $data = unserialize($content);
	    $this->setSource($data->getSource());
	    $this->setArgs($data->getArgs());
	    $this->setFixedArgs($data->getArgs() != null);
	}
    }


    /**
     *  Run the wurblet.
     */
    public function run() {
	if($this->wurblet) {
	    $this->wurblet->run();
	} else {
	    throw new SourceException("No wurblet loaded");
	}
    }

    public function getSource() {
	return($this->source);
    }

    public function setSource($source) {
	$this->source = $source;
    }


    public function getPrintStream() {
	return($this->out);
    }

    public function setPrintStream($out) {
	$this->out = $out;
    }
    
    public function getWurblet() {
	return($this->wurblet);
    }

    public function setWurblet(Wurblet $wurblet) {
	$this->wurblet = $wurblet;
	$wurblet->setContainer($this);
    }

    public function getArgs() {
	return($this->args);
    }

    public function setArgs($args) {
	if(null == $args) {
	    $args = array();
	}
	$this->args = $args;
    }

    public function hasFixedArgs() {
	return($this->fixedArgs);
    }

    public function setFixedargs($fixedArgs) {
	$this->fixedArgs = $fixedArgs;
    }


    public function getProperties($namespace) {
	// forx now return the environment properties
	if(0 == strcmp("$namespace", PROPSPACE_WURBLET)) {
	    return($this->otherProps);
	}
	return($GLOBALS['_SERVER']);
    }

    public function getProperty($namespace, $key) {
	$props = $this->getProperties($namespace);
	if(null != $props) {
	    return($props[$key]);
	}
	return(null);
    }

}

?>
