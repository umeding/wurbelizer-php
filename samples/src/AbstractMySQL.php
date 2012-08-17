<?php
/*
 * Read and populate fields from a database
 */

require_once('AbstractModel.php');

class AbstractMySQL extends AbstractModel {

    public function __construct() {
	parent::__construct();
    }


    /**
     *  Load the model.
     */
    public function run() {

	$av = $this->getContainer()->getArgs();
	$ac = count($av);
	for($i = 0; $i < $ac; $i++) {
	    switch($av[$i]) {
/*		case '-a':*/
/*		case '--access':*/
/*		    $this->access = $av[++$i];*/
/*		    break;*/
/*		case '-m':*/
/*		case '--method':*/
/*		    $this->name = $av[++$i];*/
/*		    break;*/
/*		case '-f':*/
/*		case '--field':*/
/*		    $this->fieldname = $av[++$i];*/
/*		    break;*/
		default:
		    $this->setModelName($av[$i]);
		    break;
	    }
	}

	// read the model now
	parent::run();

    }

}
?>
