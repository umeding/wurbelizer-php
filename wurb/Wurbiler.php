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

require_once('Wurbler.php');

/**
 * The wurblet compiler.
 *
 * @author <a href="mailto:meding@yahoo.com">Uwe B. Meding</a>
 */
interface Wurbiler {
  
  /**
   * Compiles the wurbelizable.
   *
   * @return number of errors, 0 = no errors (warnings are not counted)
   */
  public function compile();
  
  
  /**
   * Gets the list of invariant wurblet strings of the output level.
   *
   * @return a list of strings
   */
  public function getSourceList();
  
  /**
   * Presets the list of invariant wurblet strings of the output level.
   *
   * @param sourceList the source list, null if compile() should initialize it
   */
  public function setSourceList($sourceList);
  
  
  /**
   * Gets the source indentation.
   *
   * @return the current indentation
   */
  public function getIndent();
  
  /**
   * Sets the indentation for code generating the source.
   *
   * @param indent the new indentation
   */
  public function setIndent($indent);
  
  
  /**
   * Sets the autoindent feature.
   *
   * @param autoIndent true to enable autoindent (default)
   */
  public function setAutoIndent($autoIndent);
  
  /**
   * Gets the autoindent feature.
   *
   * @return true if autoindent is enabled (default)
   */
  public function isAutoIndent();
  
  
  /**
   * Sets the name of the output stream in the generated source.
   *
   * @param outName null sets to the default value "out".
   */
  public function setOutName($outName="out");
  
  /**
   * Gets the name of the output stream
   *
   * @return the name of the output stream
   */
  public function getOutName();
  
  
  /**
   * Gets the package name.
   *
   * @return the package name, null if default package
   */
  public function getPackageName();
  
  /**
   * Sets the package name.
   *
   * @param packageName the package name
   */
  public function setPackageName($packageName);
  
  /**
   * Gets the parent class.
   *
   * @return the name of the parent class, null if default
   */
  public function getParentClass();
  
  /**
   * Sets the parent class.
   *
   * @param parentClass the name of the parent class, null if default (AbstractWurblet)
   */
  public function setParentClass($parentClass);
  
  /**
   * Gets the list of imports.
   *
   * @return array of imports
   */
  public function getImports();
  
  /**
   * Sets the imports.
   *
   * @param imports is the array of imports
   */
  public function setImports($imports);
  
  /**
   * Gets the preset wurblet args.
   *
   * @return wurblet args, null = default
   */
  public function getArgs();
  
  /**
   * Preset wurblet args. 
   * The default (null) means that the args
   * are determined at runtime by the wurbler, 
   * i.e. command-line, @wurblet-directive.
   *
   * @param args the wurblet args
   */
  public function setArgs($args);
  
}

?>
