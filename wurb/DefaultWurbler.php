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

require_once('AbstractWurbler.php');

class DefaultWurbler extends AbstractWurbler {

    public function __construct($args) {
	parent::__construct();

	$wurbletName = $args[1];
        $paths = array("./");

	parent::loadWurblet($wurbletName, $paths, null);

    }


    /**
     *  Get the loop count.
     *  @return the loop count
     */
    public function getInvocationCount() {
	return(1);
    }

    /**
     *  Get an information file.
     *  @param $name is the name of the information file
     *  @return the information file
     */
    public function getInfoFile($name) {
	return($name);
    }

    private function usage($prog) {
	echo "Usage: $prog <wurblet> [args...]\n";
    }

    public static function main($argv) {
	if(count($argv) > 1) {
	    try {
		$dw = new DefaultWurbler($argv);
		$dw->run();
	    } catch(Exception $ex) {
		echo "/*\nException in ".$ex->getFile().", line ".$ex->getLine().":\n"
		    ."   ".$ex->getMessage()."\n".$ex->getTraceAsString()."\n*/\n";
	    }
	} else {
	    self::usage($argv[0]);
	    exit(1);
	}
    }

}

?>
