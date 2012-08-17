<?php
/*
 * Read and populate fields from a database
 */

require_once('AbstractModel.php');

class AbstractHTMLForm extends AbstractModel {

    private $method;
    private $action;

    public function __construct() {
	parent::__construct();
	$this->action = '';
	$this->method = 'post';
    }


    /**
     *  Load the model.
     */
    public function run() {

	$av = $this->getContainer()->getArgs();
	$ac = count($av);
	for($i = 0; $i < $ac; $i++) {
	    switch($av[$i]) {
		case '-->':
		    break;
		case '-a':
		case '--action':
		    $this->action = $av[++$i];
		    break;
		case '-m':
		case '--method':
		    $this->method = $av[++$i];
		    break;
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


    public function getAction() {
	return($this->action);
    }

    public function getMethod() {
	return($this->method);
    }
}
?>
