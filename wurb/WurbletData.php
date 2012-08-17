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

/**
 *  Class holding the wurblet data. Serialized for each instance of a
 *  wurblet.
 *
 *  @author <a href="mailto:meding@yahoo.com">Uwe B. Meding</a>
 */
class WurbletData {

    private $source;
    private $args;

    /**
     *  Construct a wurblet data
     *  @param $source is the fixed source part
     *  @param $args wurblet args
     *  @return 
     */
    public function __construct($source, $args) {
	$this->setSource($source);
	$this->setArgs($args);
    }


    public function getArgs() {
	return($this->args);
    }

    public function setArgs($args) {
	$this->args = $args;
    }


    public function getSource() {
	return($this->source);
    }

    public function setSource($source) {
	$this->source = $source;
    }
}

?>
