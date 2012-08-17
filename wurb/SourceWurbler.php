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
require_once('HeapFile.php');
require_once('AbstractWurbler.php');
require_once('SourceElement.php');
require_once('SourceFile.php');
require_once('SourceDocument.php');

/**
 *  Read a source file, scan for @wurblets and apply them.
 *
 *  @author <a href="mailto:meding@yahoo.com">Uwe B. Meding</a>
 */
define('WURBPROP_FILENAME', 'filename');
define('WURBPROP_CLASSNAME', 'classname');
define('WURBPROP_WURBNAME', 'wurbname');
define('WURBPROP_DIRNAME', 'dirname');
define('WURBPROP_PACKAGENAME', 'packagename');
define('WURBPROP_GUARDNAME', 'guardname');
define('WURBPROP_WURBLETNAME', 'wurbletname');
define('PROPERTY_GUARDTYPE', 'guardtype');

class SourceWurbler extends AbstractWurbler {

    private $filename;
    private $guardedName;
    private $wurbProps;
    private $wurletPath;
    private $infoDir;

    private $verbose;
    private $loopCount;
    private $lineComment;

    public function __construct($filename, $srcDirs, $wurbletPath, $infoDir, $verbose) {
	$this->filename = $filename;
	$this->wurbletPath = $wurbletPath;
	$this->infoDir = $infoDir;
	$this->verbose = $verbose;
	$this->lineComment = '//';


	$ndx = last_index_of($filename, '/');
	$dirname = $ndx > 0 ? substr($filename, 0, $ndx) : '.';
	$classname = $ndx > 0 && strlen($filename)>$ndx ? substr($filename, $ndx+1) : $filename;
	$ndx = last_index_of($classname, '.');
	if($ndx > 0) {
	    $classname = substr($classname, 0, $ndx);
	}
	$wurbname = $dirname.'/'.$classname.'.wurb';


	// set up some properties
	$this->wurbProps = array();
	$this->wurbProps[WURBPROP_FILENAME] = $filename;
	$this->wurbProps[WURBPROP_CLASSNAME] = $classname;
	$this->wurbProps[WURBPROP_WURBNAME] = $wurbname;
	$this->wurbProps[WURBPROP_DIRNAME] = $dirname;

	// set hte guarded type
	$this->guardedType = TYPE_GUARDED;

	// preset the guard type from the environment
	// ... not yet ...
	
	// load the wurb file if possible
	$fp = @fopen($wurbname, 'r');
	if($fp) {

	    while(!feof($fp)) {
		$key = WurbUtil.scanPropertyLine(fgets($fp, 100), $this->wurbProps);
		if($key != null && $key == PROPERTY_GUARDTYPE) {
		    $value = $this->wurbProps[PROPERTY_GUARDTYPE];
		    if($value) {
			$this->setGuardType($value);
		    }
		}
	    }

	    fclose($fp);
	}
    }

    private function setGuardType($value) {
	// additional mapping for additional guard types
    }
    
    /**
     *  Set the line comment characters
     *  @param 
     *  @return 
     */
    public function setLineComment($lc) {
	$this->lineComment = $lc;
    }

    public function getInvocationCount() {
	return($this->loopCount);
    }

    public function getInfoFile($name) {
	return($name);
    }

    public function wurbelize() {

	SourceFile::initialize();

	if($this->verbose) {
	    echo "--- ".$this->filename." ---\n";
	}

	$text = SourceFile::open($this->filename)->getOrgText();

	$errors = 0;
	for($this->loopCount = 1;; $this->loopCount++) {

	    $doc = new SourceDocument($text, $this->wurbProps, $this->lineComment);

	    $guardedNames = array();
	    $currentGuardName = null;

	    $orgText = $doc->getText();
	    for($i = 0; $i < count($doc->getElements()); $i++) {
		$elements = $doc->getElements();
		$elem = $elements[$i];

		//echo "[>>".$elem->getText()."\n";
		if($elem->containsHereDocuments()) {
		    foreach($elem->getHereDocuments() as $hereDoc) {
			$hereName = WurbUtil::translateVars($hereDoc->getHereName(), $this->wurbProps);
			$hereText = WurbUtil::translateVars($hereDoc->getHereText(), $this->wurbProps);
			if($hereName[0] == '.') {
			    // in memory document, process once
			    if($this->loopCount == 1) {
				if($this->verbose) {
				    echo "[".$this->loopCount."]heapfile: $hereName\n";
				}
				new HeapFile(substr($hereName, 1), $hereText);
			    }
			} else {
			    // regular file
			    if($this->verbose) {
				echo "[".$this->loopCount."]file: $hereName\n";
			    }
			    $file = SourceFile::open($hereName);
			    fwrite($file->getStream(), $hereText);
			    $file->close();
			}

		    }
		}

		if($elem->containsWurblets()) {

		    // provcess the wurblets in reverse order
		    $wurbletList = $elem->getWurblets();
		    $wurbletListSize = count($wurbletList) - 1;
		    for($wurbletIndex=$wurbletListSize; $wurbletIndex>=0;$wurbletIndex--) {

			$wurblet = $wurbletList[$wurbletIndex];
			$indent = 0;
			$invoke = true;
			foreach($wurblet->getWurblerArgs() as $warg) {
			    if(0 == strncmp($warg, 'indent=', 7)) {
				$indent = intval(substr($warg, 7));
			    } else if(0 == strncmp($warg, 'test:', 5)) {
				$invoke = $this->simpleCondition(substr($warg, 5));
			    }
			}

			// set up the wurblet eval
			$ps = fopen('data://text/plain,', 'w+');
			try {
			    $currentGuardName = $wurblet->getGuardedName();
			    if(isset($guardedNames[$currentGuardName])) {
				throw new SourceException("Guarded name '$currentGuardName' not unique");
			    }
			    $guardedNames[$currentGuardName] = $currentGuardName;

			    $this->wurbProps[WURBPROP_GUARDNAME] = $currentGuardName;
			    $wurbletName = $wurblet->getWurbletName();
			    $this->wurbProps[WURBPROP_WURBLETNAME] = $wurbletName;

			    if($this->verbose) {
				echo "[".$this->loopCount."]wurblet: $currentGuardName, $wurbletName\n";
			    }

			    $this->loadWurblet($wurbletName, $this->wurbletPath, $this->wurbProps);

			    if(!$this->hasFixedArgs()) {
				$this->setArgs($wurblet->getWurbletArgs());
			    }
			    $this->setPrintStream($ps);

			    if($invoke) {
				// run the wurblet
				echo $this->filename.": wurbling $wurbletName($currentGuardName)\n";
				$this->run();
			    } else {
				fwrite($ps, "// condition not met\n");
			    }

			} catch(Exception $ex) {
			    // place the exception in the source file
			    $errors++;
			    echo "Exception: $ex\n";
			    fwrite($ps, "/*\nException in ".$ex->getFile().", line "
				    .$ex->getLine().":\n"."   ".$ex->getMessage()
				    ."\n".$ex->getTraceAsString()."\n*/\n");
			}

			// build the generated text
			$stat = fstat($ps);
			fseek($ps, 0);
			$srcText = fread($ps, $stat['size']);

			$srcText = WurbUtil::indent($srcText, $indent);
			if(-1 == $doc->replaceGuarded($this->guardedType, $wurblet->getGuardedName(), $srcText)) {
			    $doc->insertGuarded($this->guardedType, $wurblet->getGuardedName(), $srcText, $i+1);
			}
		    }
		}
	    }

	    // go thru all sections and remove guarded block without
	    // corresponding wurblet directive
	    $max = count($doc->getElements());
	    for($i=0; $i<$max;$i++) {
		$elem = $doc->getElementAt($i);


		if($elem->isGuarded() && !in_array($elem->getGuardedName(), $guardedNames) && $doc->deleteGuarded($elem->getGuardedName(), $i) >= 0) {
		    $max = count($doc->getElements());
		    $i--;
		}
	    }

	    // compare the output with the original
            $newText = $doc->getText();
	    if($newText == $orgText) {
		break;
	    } else {

		echo "writing output: ".$this->filename."\n";
		$fp = fopen($this->filename, 'w');
		fwrite($fp, $newText);
		fclose($fp);
		if($errors == 0) {
		    $errors = -1;
		} else if($errors > 0) {
		    break; // abort due to errors
		}
		$text = $newText;
	    }

	    // rewind all the source files
	    SourceFile::resetAll();
	}
        // write back all created sources if they differ from the
	// original
	SourceFile::closeAll();
	return($errors);
    }

    /**
     *  Test a simple condition 'expr1==expr2' or 'expr1!=expr2'
     *  @param $condStr is the condition string
     *  @return 
     */
    private function simpleCondition($condStr) {
	$invert = false;
	if($condStr) {
	    $ndx = index_of($condStr, '==');
	    if($ndx < 0) {
		$ndx = index_of($condStr, '!=');
		$invert = true;
	    }
	    if($ndx > 0) {
		$str1 = trim(substr($condStr, 0, $ndx));
		$str2 = trim(substr($condStr, $ndx+2));
		if($invert) {
		    return($str1 != $str2);
		} else {
		    return($str1 == $str2);
		}
	    }
	}
	return($invert);
    }


    public static function printUsage($prog) {
	$pos = last_index_of($prog, '/');
	$prog = substr($prog, ++$pos);
	echo "Usage: $prog [options] <file>\n";
	echo "Options:\n";
	echo " -h | --help         Display this information\n";
	echo " -Dname[=value]      Set an environment variable\n";
	echo " -w | --wurbletpath  wurblet search path of directories\n";
	echo " -v | --verbose      Verbose processing mode\n";
    }

    /**
     *  The main wurbler.
     *  @param 
     *  @return 
     */
    public static function main($argv) {

        $wurbletpath = array('./');
        $verbose = false;

        $argc = count($argv);
        for($i=1; $i < $argc; $i++) {
            if(strlen($argv[$i]) > 2 && 0 == strncmp($argv[$i], '-D', 2)) {
                $pos = index_of($argv[$i], '=');
                if($pos > 0) {
                    $defname = substr($argv[$i], 2, $pos-2);
                    $defvalue = substr($argv[$i], $pos+1);
                } else {
                    $defname = substr($argv[$i], 2);
                    $defvalue = "";
                }
                $GLOBALS['_SERVER'][$defname] = $defvalue;
            } else {
                switch($argv[$i]) {
                    case '-h': case '--help':
                        self::printUsage($argv[0]);
                        exit(0);

                    case '-w': case '--wurbletpath':
                        $wurbletpath = split_string("[;:, \t]+", $argv[$i+1]);
                        set_include_path(get_include_path()
                                .PATH_SEPARATOR
                                .implode(PATH_SEPARATOR, $wurbletpath));
                        for($j=0;$j<count($wurbletpath);$j++) {
                            $wurbletpath[$j] = $wurbletpath[$j].'/';
                        }
                        break;

                    case '-l': case '--line-comment':
                        $lineComment = $argv[++$i].' ';
                        break;

                    case '-v': case '--verbose':
                        $verbose = true;
                        break;

                    default:
                        if($argv[$i][0] == '-') {
                            // illegal option
                            echo "option ".$argv[$i]." illegal -- igored\n";
                        } else {
                            $filename = $argv[$i];
                        }
                }
            }
        }

        if(!isset($filename)) {
            echo "wurbler: no input file\n\n";
            self::printUsage($argv[0]);
            exit(1);
        }

        try {
            $sw = new SourceWurbler($filename, null, $wurbletpath, null, $verbose);
            if(isset($lineComment)) {
                $lineComment = trim($lineComment);
                if(2 != strlen($lineComment)) {
                    throw new Exception("line-comment must be 2 characters");
                }
                $sw->setLineComment(trim($lineComment));
            }
            $sw->wurbelize();
        } catch(Exception $ex) {
            echo "\nException in ".$ex->getFile().", line ".$ex->getLine().":\n"
                ."   ".$ex->getMessage()."\n".$ex->getTraceAsString()."\n";

        }
    }
}

?>
