<?php
/*
 * Read and populate fields from a database
 */

require_once('AbstractModel.php');

class AbstractDBRead extends AbstractModel {

    private $access;
    private $methodname;
    private $fieldname;
    private $mappedfield;

    public function __construct() {
	parent::__construct();
	$this->access = 'public';
	$this->methodname = 'readFromDB';
	$this->fieldname = null;
	$this->mappedfield = null;
    }


    /**
     *  Load the model.
     */
    public function run() {

	$av = $this->getContainer()->getArgs();
	$ac = count($av);
	for($i = 0; $i < $ac; $i++) {
	    switch($av[$i]) {
		case '-a':
		case '--access':
		    $this->access = $av[++$i];
		    break;
		case '-m':
		case '--method':
		    $this->name = $av[++$i];
		    break;
		case '-f':
		case '--field':
		    $this->fieldname = $av[++$i];
		    break;
		default:
		    $this->setModelName($av[$i]);
		    break;
	    }
	}

	// read the model now
	parent::run();

	// do some additonal checks+setups
	$dbstuff = $this->getDBStuff();
	if($this->fieldName) {
	    $map = $dbstuff['map'];
	    $this->mappedfield = $map[$this->fieldname];
	    if($this->mappedfield === null) {
		throw new Exception("Field ".$this->fieldname." not found");
	    }
	}
    }

    public function getAccess() {
	return($this->access);
    }
    /**
     *  Get name of the access function
     *  @return the name
     */
    public function getName() {
	return($this->name);
    }

    public function getFieldName() {
	return($this->fieldname);
    }

    public function getMappedFieldName() {
	return($this->mappedfield);
    }

}
?>
