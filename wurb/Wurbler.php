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
 *  The wurblet container.
 *
 *  @author <a href="mailto:uwe@uwemeding.com">Uwe B. Meding</a>
 */
define('FILE_SOURCE_EXTENSION', '.ser');
define('PROPSPACE_ENV', 'env');
define('PROPSPACE_WURBLET', 'wurblet');
define('PROPSPACE_EXTRA', 'extra');
define('WRBL_EXTENSION', '.wrbl');

interface Wurbler {


    /**
     *  Gets the fixed source text.
     *  @return all the text
     */
    public function getSource();

    /**
     *  Get a handle to write the generated code to.
     *  @return the handle
     */
    public function getPrintStream();


    /**
     *  Get the wurblet arguments.
     *  @return the arguments
     */
    public function getArgs();


    /**
     *  Gets the invokation count for the wurblet.
     *  @return the number of invocations (starting at 1)
     */
    public function getInvocationCount();

    /**
     *  Get a property value.
     *  @param $namespace is the namespace 
     *  @param $key is the property's name
     *  @return the value
     */
    public function getProperty($namespace, $key);

    /**
     *  Get all properties in a namespace
     *  @param $namespace is the namespace
     *  @return the properties
     */
    public function getProperties($namespace);


    /**
     *  Get an information file.
     *  @param $name is the name of the info file.
     *  @return the info file
     */
    public function getInfoFile($name);
}

?>
