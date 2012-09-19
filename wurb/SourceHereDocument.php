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

/**
 *  Here document (all text between @> and @<)
 *
 *  @author <a href="mailto:uwe@uwemeding.com">Uwe B. Meding</a>
 */
class SourceHereDocument {

    private $hereName;
    private $hereText;

    public function __construct($name, $text) {
	$this->hereName = $name;
	$this->hereText = $text;
    }

    public function getHereName() {
	return($this->hereName);
    }

    public function setHereName($name) {
	$this->hereName = $name;
    }

    public function getHereText() {
	return($this->hereText);
    }

    public function setHereText() {
	$this->hereText = $hereText;
    }
}
?>
