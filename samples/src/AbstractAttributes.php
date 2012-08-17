<?php

require_once('AbstractModel.php');

class AbstractAttributes extends AbstractModel {

    private $enableConstraintChecks;
    private $createAccess;
    private $createInterface;
    private $readOnly;

    public function __construct() {
	parent::__construct();

	$this->enableConstraintChecks = false;
	$this->createAccess = true;
	$this->createInterface = false;
	$this->readOnly = false;
    }


    /**
     *  Load the model.
     */
    public function run() {

	$av = $this->getContainer()->getArgs();
	$ac = count($av);
	for($i = 0; $i < $ac; $i++) {
	    switch($av[$i]) {
		case '-i':
		case '--just-interface':
		    $this->createInterface = true;
		    break;
		case '-r':
		case '--readonly':
		    $this->readOnly = true;
		    break;
		case '-d':
		case '--just-decls':
		    $this->createAccess = false;
		    break;
		case '-c':
		case '--enable-constraints':
		    $this->enableConstraintChecks = true;
		    break;
		default:
		    $this->setModelName($av[$i]);
		    break;
	    }
	}
	if($this->createInterface) {
	    $this->createAccess = false;
	}

	// read the model now
	parent::run();
    }

    public function needBeanAccess() {
	return($this->createAccess);
    }

    public function needConstraints() {
	return($this->enableConstraintChecks);
    }

    public function justInterface() {
	return($this->createInterface);
    }

    public function readOnly() {
	return($this->readOnly);
    }

}
?>
