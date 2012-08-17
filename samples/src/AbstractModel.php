<?php

/*
 * Reads a model file and creates an object model
 */

require_once('AbstractWurblet.php');


/*
 * Implement this to define constraint definitons
 */
interface Constraint {
    public function getName();
    public function getExpr($name, $parameter);
}
// {{{ abstract constraint
abstract class ConstraintImpl implements Constraint {
    private	$constName;
    public function __construct($name) {
	$this->constName = $name;
    }

    public function getName() {
	return($this->constName);
    }

    public function __toString() {
	return("<<constraint: {$this->constName}>>");
    }
}
/// }}}

// {{{one of constraint
class Constraint_oneof extends ConstraintImpl {

    public function __construct() {
	parent::__construct('one of');
    }

    public function getExpr($name, $parameter) {
	return("(in_array($name, ".var_export($parameter, true)."))");
    }

}
//}}}
// {{{length constraint
class Constraint_length extends ConstraintImpl {
    public function __construct() {
	parent::__construct('length');
    }

    public function getExpr($name, $parameter) {
	return("(strlen($name)<{$parameter[0]})");
    }

}
//}}}
// {{{between constraint
class Constraint_between extends ConstraintImpl {
    public function __construct() {
	parent::__construct('between');
    }

    public function getExpr($name, $parameter) {
	if(count($parameter) != 2) {
	    throw new Exception("Exactly 2 parameter must be passed to the 'between; constraint");
	}
	return("(($name>={$parameter[0]})&&($name<={$parameter[1]}))");
    }

}
//}}}
// {{{lt constraint
class Constraint_lt extends ConstraintImpl {
    public function __construct() {
	parent::__construct('lt');
    }

    public function getExpr($name, $parameter) {
	if(count($parameter) != 1) {
	    throw new Exception("Exactly 1 parameter must be passed to the 'lt' constraint");
	}
	return("($name<{$parameter[0]})");
    }

}
//}}}
// {{{le constraint
class Constraint_le extends ConstraintImpl {
    public function __construct() {
	parent::__construct('le');
    }

    public function getExpr($name, $parameter) {
	if(count($parameter) != 1) {
	    throw new Exception("Exactly 1 parameter must be passed to the 'le' constraint");
	}
	return("($name<={$parameter[0]})");
    }

}
//}}}
// {{{eq constraint
class Constraint_eq extends ConstraintImpl {
    public function __construct() {
	parent::__construct('eq');
    }

    public function getExpr($name, $parameter) {
	if(count($parameter) != 1) {
	    throw new Exception("Exactly 1 parameter must be passed to the 'eq' constraint");
	}
	return("($name=={$parameter[0]})");
    }

}
//}}}
// {{{gt constraint
class Constraint_gt extends ConstraintImpl {
    public function __construct() {
	parent::__construct('gt');
    }

    public function getExpr($name, $parameter) {
	if(count($parameter) != 1) {
	    throw new Exception("Exactly 1 parameter must be passed to the 'gt' constraint");
	}
	return("($name>{$parameter[0]})");
    }

}
//}}}
// {{{ge constraint
class Constraint_ge extends ConstraintImpl {
    public function __construct() {
	parent::__construct('ge');
    }

    public function getExpr($name, $parameter) {
	if(count($parameter) != 1) {
	    throw new Exception("Exactly 1 parameter must be passed to the 'ge' constraint");
	}
	return("($name>={$parameter[0]})");
    }

}
//}}}
// {{{readonly constraint
class Constraint_readonly extends ConstraintImpl {
    public function __construct() {
	parent::__construct('readonly');
    }

    public function getExpr($name, $parameter) {
	throw new Exception("'readonly' constraint cannot be evaluated");
    }

}
//}}}


class AbstractModel extends AbstractWurblet {

    private $modelName;
    private $attrs;
    private $contraints;
    private $dbstuff;

    public function __construct() {
	parent::__construct();

	$this->attrs = array();
	$this->constraints = array();
	$this->dbstuff = array();
    }

    public function setModelName($modelName) {
	$this->modelName = $modelName;
    }

    /**
     *  Load the model.
     */
    public function run() {
	parent::run();

	if($this->modelName == null) {
	    throw new Exception("attribute file not specified");
	}

	$fp = WurbUtil::openReader($this->modelName);
	while(!feof($fp)) {
	    $line = trim(fgets($fp, 200));

	    if(strlen($line)>0 && $line[0] != '#') {
		if(0 == strncmp($line, '.constraint', 11)) {
		    // set up a constraint
		    $this->setupConstraint(&$this->constraints, $line);
		} else if(0 == strncmp($line, '.db', 3)) {
		    // set up a constraint
		    $this->setupDBStuff($line);
		} else if($line[0] == '.') {
		    // ignore any other line starting with a '.'
		} else {
		    // attribute definition
		    $type = '';
		    $name = '';
		    $description = '';
		    sscanf($line, "%s %s %[^$]", $type, $name, $description);
		    $description = trim($description);
		    $this->attrs[] = array(
			    'name' => $name,
			    'type' => $type,
			    'methodSuffix' => ucfirst($name),
			    'comment' => trim($description)
			    );
		}
	    }
	}
	fclose($fp);
	//var_dump($this->dbstuff);
    }

    private function setupDBStuff($line) {
	$db = '';
	$cmd = '';
	$args = '';
	sscanf($line, "%s %s %[^$]", $db, $cmd, $args);
	switch($cmd) {
	    case 'mapall':
		foreach($this->getAttributeList() as $attrItem) {
		    $attr = &$this->findAttribute($attrItem['name']);
		    $attr['dbname'] = $attr['name'];
		    $this->dbstuff['map'][$name] = $name;
		}
		break;
	    case 'map':
		$name = '';
		$dbname = '';
		sscanf($args, "%s %s", $name, $dbname);
		if($attr = &$this->findAttribute($name)) {
		    // do a specific mapping
		    $attr['dbname'] = $dbname;
		    $this->dbstuff['map'][$name] = $dbname;
		}
		break;
	    case 'include':
		$include = '';
		sscanf($args, "%s", $include);
		$fp = WurbUtil::openReader($include);
		$contents = stream_get_contents($fp);
		fclose($fp);
		$this->dbstuff['include'][] = $contents;
		break;
	    case 'primary':
		$primary = '';
		sscanf($args, "%[^$]", $primary);
		$this->dbstuff['primary'] = $primary;
		break;
	    case 'foreign':
		$foreign = '';
		sscanf($args, "%[^$]", $foreign);
		$this->dbstuff['foreign'][] = $foreign;
		break;
	    case 'autoincr':
		$autoincr = '';
		sscanf($args, "%s", $autoincr);
		$this->dbstuff['autoincr'] = $autoincr;
		break;
	    case 'table':
		$tablename = '';
		sscanf($args, "%s", $tablename);
		$this->dbstuff['table'] = $tablename;
		break;
	    case 'index':
		$indexname = '';
		$columns = '';
		sscanf($args, "%s %[^$]", $indexname, $columns);
		$this->dbstuff['index'][$indexname] = $columns;
		break;
	}
    }

    private function setupConstraint(&$constraints, $line) {
	if(0 == strncmp($line, '.constraint', 11)) {
	    $from = strpos($line, '(');
	    $to = strpos($line, ')');
	    $variable = trim(substr($line, $from+1, $to-$from-1));
	    $expr = trim(substr($line, $to+1));

	    $from = strpos($expr, '(');
	    $to = strpos($expr, ')');
	    if($from == null) {
		$constraint = trim($expr);
	    } else {
		$constraint = trim(substr($expr, 0, $from));
	    }
	    $parameter = 'return array('.trim(substr($expr, $from+1, $to-$from-1)).');';
	    $class = "Constraint_$constraint";

	    $parameter = eval($parameter);

	    $inst = new $class();

	    if(!isset($constraints[$variable])) $constraints[$variable] = array();
	    $cv = &$constraints[$variable];
	    if(!isset($cv[$constraint])) $cv[$constraint] = array();
	    $const = &$cv[$constraint];

	    if(isset($const['parameter'])) {
		$const['parameter'] = array_merge($const['parameter'], $parameter);
	    } else {
		$const['parameter'] = $parameter;
	    }

	    if(!isset($const['inst'])) {
		$const['inst'] = $inst;
	    }
	} 
    }

    /**
     *  Get the list of attributes
     *  @return the attributes
     */
    public function getAttributeList() {
	return($this->attrs);
    }

    /**
     *  Find a particular attribute definition
     *  @param $name we are looking for
     *  @return the name (or null)
     */
    public function &findAttribute($name) {
	foreach($this->attrs as &$attr) {
	    if($attr['name'] == $name) {
		return($attr);
	    }
	}
	return(null);
    }

    /**
     *  Get the list of constraints
     *  @return the constraints
     */
    public function getConstraints() {
	return($this->constraints);
    }

    /**
     *  Find a constraint by name and type
     *  @param $name is the name of the variable
     *  @param $type is the type name of the constraint
     *  @return the constraint (or null)
     */
    public function findConstraint($name, $type) {
	$consts = $this->constraints[$name];
	if($consts == null) {
	    return(null);
	}
	return($consts[$type]);
    }

    /**
     *  Get a constraints name
     *  @param $const is the constraint name
     *  @return the name
     */
    public function getConstraintName($const) {
	$name = $const['inst']->getName();
	return($name);
    }

    /**
     *  Evaluate a constraint
     *  @param $varname is the variable name
     *  @param $const is the constraint
     *  @return the code text
     */
    public function evalConstraint($varname, $const) {
	$val = $const['inst']->getExpr($varname, $const['parameter']);
	return($val);
    }

    /**
     *  Get the DB stuff
     *  @return the db stuff
     */
    public function getDBStuff() {
	return($this->dbstuff);
    }
}
?>
