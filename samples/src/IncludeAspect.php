<?php

require_once('AbstractWurblet.php');

class IncludeAspect extends AbstractWurblet {

    private $aspect;
    private $name;

    public function __construct() {
	parent::__construct();

    }


    /**
     *  Load the model.
     */
    public function run() {
	parent::run();

	foreach($this->getContainer()->getArgs() as $arg) {
	    // determine aspect 
	    $pos = last_index_of($arg, '.');
	    if($pos <= 0) {
		throw new SourceException($arg.": unable to determine extension");
	    }

	    $aspect = substr($arg, $pos+1);
	    if($arg[0] == '.') {
		$heapfile = substr($arg, 1);
		$onheap = true;
		$classname = substr($arg, 1, $pos-1);
	    } else {
		$heapfile = substr($arg, 0);
		$onheap = false;
		$classname = substr($arg, 0, $pos-1);
	    }

	    switch($aspect) {
	    case 'impl':
		$this->handleImplementation($heapfile, $classname, $onheap);
		break;

	    default:
		throw new SourceException("'$aspect' aspect not implemeted");
	    }
	}
    }


    /**
     *  Handle the implementation aspect
     *  @param $classname is the classname
     *  @param $onheap where to find the data
     */
    private function handleImplementation($heapfile, $classname, $onheap) {
	$filename = $classname.'.php';
	if(file_exists($filename)) {

	    $hf = HeapFile::get($heapfile);
	    if($hf == null) {
		$text = SourceFile::open($filename)->getOrgText();
		$props = $this->getContainer()->getProperties('all');
		$doc = new SourceDocument($text, $props);


		$elements = $doc->getElements();
		for($i = 0; $i < count($doc->getElements()); $i++) {
		    $elem = $elements[$i];

		    if($elem->containsHereDocuments()) {
			foreach($elem->getHereDocuments() as $hereDoc) {
			    $hereName = WurbUtil::translateVars($hereDoc->getHereName(), $props);
			    $hereText = WurbUtil::translateVars($hereDoc->getHereText(), $props);
			    if($hereName[0] == '.') {
				new HeapFile(substr($hereName, 1), $hereText);
			    } else {
				// regular file
				echo "file: $hereName\n";
				$file = SourceFile::open($hereName);
				fwrite($file->getStream(), $hereText);
				$file->close();
			    }

			}
		    }
		}
	    }

	    if($onheap) {
		$hf = HeapFile::get($heapfile);
		$content = $hf->getText();
		fwrite($this->out, $content);
	    }
	} else {
	    throw new SourceException("Unable to locate '$filename' -- check the wurbler path\n");
	}
    }

}
?>
