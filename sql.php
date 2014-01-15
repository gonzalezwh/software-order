<?php
    /*
    	Class: SQLBase (sql.php)
        A class for abstracting SQL calls, _very restrictive in nature_.
        Basic use is constructing a Handler (or make your own), and doing all
        database calls through that class. 
    */
	class SQLBase{
        public $mysqli;

      //  protected $mysqli; 
        /*
        	Function: __construct()
            Reads data from the config file and initializes a mysqli object.
            See subclasses below for configuration if a new subclass/table is 
			created or needed.
        */
		function __construct(){
			$config = parse_ini_file('config.ini');
			$server = $config['sql_server'];
			$user = $config['sql_user'];
			$pass = $config['sql_pass'];
			$db = $config['sql_db'];
			$this->mysqli = new mysqli($server, $user, $pass, $db);
			if ($this->mysqli->connect_error){
				$this->mysqli = FALSE;
			}
			$config = parse_ini_file('config.ini');
			$this->table = $config[$this->config_table];
		}
		//placeholder function
		function __deconstruct(){}
        /*
        	Function: processGetResult
            Takes most types of MySQL results, and returns an array of Objects based on the
            Handler's "rowtype" Object.
            
            Parameters:
            	$result - The result of a mysqli->query()
        */
		protected function processGetResult($result){
            if($this->mysqli->error){
                echo $this->mysqli->error;
                return FALSE;
            }
			$list = array();
            while($row = $result->fetch_assoc()){
                $list[] = new $this->rowtype($row);
            } 
			mysqli_free_result($result);
			return $list;
		}
        /*
        	Function: removeRow
            Removes a single row from SQL based on the Object it is passed. Only really 
            requires the index to remove, but .
            
            Parameters:
            	$object - A 'rowtype' Object
        */
        protected function removeRow($object){
            $index = $object->getIndex();
            $index2 = $this->mysqli->real_escape_string($object->getField($index));
			$query = "DELETE FROM ".$this->table."
					 WHERE $index=$index2
					 LIMIT 1";
			$this->mysqli->query($query);
			if($this->mysqli->error){
                echo $this->mysqli->error;
                return FALSE;
			}
			else{
				return TRUE;
			}
        }
        /*
        	Function: updateRow
            Performs an update, overwriting an SQL row with the object's data. The object 
            passed here should probably be something retrieved with 'get'.
            
            Parameters:
            	$object - A 'rowtype' Object
        */
		protected function updateRow($object){
			$fields = $object->getFields();
			$index = $object->getIndex();
			$setarray = array();
			$index2 =  $this->mysqli->real_escape_string($fields[$index]);
			foreach($fields as $key=>$value){
				$key = $this->mysqli->real_escape_string($key);
				$value = $this->mysqli->real_escape_string($value);
				$setarray[] = "$key='$value'";
			}
			$string = implode(", ", $setarray);
			$query = "UPDATE ".$this->table."
					 SET $string
					 WHERE $index=$index2
					 LIMIT 1";
			$this->mysqli->query($query);
			if($this->mysqli->error){
                echo $this->mysqli->error;
                return FALSE;
			}
			else{
				return TRUE;
			}
		}
        /*
        	Function: addRow
            Adds a row to SQL, but first gets the highest available index from the table. '
            Does not fill holes for historical reasons, so a table with indexes 1,2,4,5 
            will get a row at the end at 6, not 3.
                
            Parameters:
            	$object - A 'rowtype' Object
        */
		protected function addRow($object){
			if(!$object){
				$object = new $this->rowtype();
			}
			$fields = $object->getFields();
			$index = $object->getIndex();
			$values = array();
			$columns = array();
			$query = "SELECT MAX($index) FROM ".$this->table;
			$result = $this->mysqli->query($query);
			$row = $result->fetch_row();
			$maxindex = $row[0];
			$fields[$index] = $maxindex+1;
			foreach($fields as $key=>$value){
				$key = $this->mysqli->real_escape_string($key);
				$value = $this->mysqli->real_escape_string($value);
				$columns[] = $key;
				$values[] = $value;
			}
			$columnstring = implode(", ", $columns);
			$valuestring = implode("', '", $values);
			$query = "INSERT INTO ".$this->table." ($columnstring)
					 VALUES ('$valuestring')";
			$this->mysqli->query($query);
			if($this->mysqli->error){
                echo $this->mysqli->error;
                return FALSE;
			}
			else{
				return TRUE;
			}
		}
        /*
        	Function: escapeArray
            Recursively escapes values in an associative array.
            
            Parameters:
            	$array - An associative array
        */
        public function escapeArray($array){
            $return = array();
            foreach($array as $key=>$value){
                if(is_array($value)){
                    $return[$key] = $this->escapeArray($value);
                }
                else{
                    $return[$key] = $this->mysqli->real_escape_string($value);
                }
            }
            return $return;
        }
        /*
        	Function: getConditionString
            Processes a condition tree recursively into a valid sql string.
            
            Parameters:
            	$tree - An associative array that works like a tree. This example will return
            	rows that have their Name column equal to either Samuel or David.
            	array(
            		'field'=>'Name',
            		'operator'=>'=',
            		'value'=>'Samuel',
            		'OR' => array(
						'field'=>'Name',
						'operator'=>'=',
						'value'=>'David',
						'OR' => false,
						'AND' => false,
            		),
            		'AND'=>false
            	);
        */
        public function getConditionString($tree){
            $string = '';
            $field = $tree['field'];
            $operator = $tree['operator'];
            $value = $tree['value'];
            $string .= "`$field` $operator '$value'";
            if(is_array($tree['OR'])){
                $string .= ' AND  ('.$this->getConditionString($tree['AND']).')';
            }
            if(is_array($tree['AND'])){
                $string .= ' AND ('.$this->getConditionString($tree['AND']).')';
            }
            return $string.'';
        }
        /*
        	Function: get
            A very simple database abstraction, that can be called simply with= 
            '...Handler->get()' or with a more complicated options array.
            
            Parameters:
            	$options - Associative array, options listed below:
                field - String representing a column
                operator - String representing any SQL operator (LIKE, =, etc.)
                value - String representing a value to check against field and operator
                orderby - String representing a column to orderby
                order - String representing sorting order (ASC/DESC)
                index - Int representing a row offset
                limit - Int representing max rows returned, default is 499
                assoc - Bool, if true, returns an associative array indexed by 'index'
        */
		public function get($options){
			$query = array();
			if($options){
                $query = $this->escapeArray($options);
			}
			$object = new $this->rowtype();
			$orderby = $query['orderby'] ? $query['orderby'] : $object->getIndex();
			$order = $query['order'] ? $query['order'] : 'DESC';
			$index = $query['index'] ? $query['index'] : '0';
			$limit = $query['limit'] ? $query['limit'] : '499';
            $assoc = $query['assoc'] ? $query['assoc'] : 'true';
			$condition = '';
			if($query['field'] and $query['operator'] and $query['value'] !== false){
				$value = "'".$query['value']."'";
				//custom logic for NULL searches
				if($query['value'] == "NULL"){
					$value = "NULL";
				}
                $condition = 'WHERE `'.$query['field'].'` '.$query['operator']." ".$value;
            }
            elseif($query['condition_tree']){
                $condition = 'WHERE '.$this->getConditionString($query['condition_tree']);
            }
			$query = "SELECT * from ".$this->table."\n".
					 " $condition\n".
					 " ORDER BY $orderby $order\n".
                     " LIMIT $index, $limit"; 
			$result = $this->mysqli->query($query);
			$list = $this->processGetResult($result);
            if($assoc == 'true'){
                $assoc_list = array();
                $temp = new $this->rowtype();
                $index = $temp->getIndex();
                for($i=0;$i<count($list);++$i){
                    $assoc_list[$list[$i]->getField($index)] = $list[$i];
                }
                return $assoc_list;
            }
            else{
                return $list;
            }
		}
		/*
			Function: update
			To maintain naming consistency, calls updateRow
		*/
		public function update($object){ 
			return $this->updateRow($object);
		}
		/*
			Function: add
			To maintain naming consistency, calls addRow
		*/
		public function add($object){
			return $this->addRow($object);
		}
		/*
			Function: remove
			To maintain naming consistency, calls removeRow. Allows users to remove based
			on an index instead of an object.
		*/
        public function remove($id){
            $object = new $this->rowtype();
            $object->setField($object->getIndex(),$id);
            return $this->removeRow($object);
        }
	}
	
	class OrderHandler extends SQLBase{
		protected $table = '';
		protected $rowtype = 'Order';
		protected $config_table = 'sql_orders_table';
	}
	
	class UserHandler extends SQLBase{
		protected $table = '';
		protected $rowtype = 'User';
		protected $config_table = 'sql_users_table';
	}
	
	class AuthorizerHandler extends SQLBase{
		protected $table = '';
		protected $rowtype = 'Authorizer';
		protected $config_table = 'sql_authorized_table';
	}
    class VendorHandler extends SQLBase{
		protected $table = '';
		protected $rowtype = 'Vendor';
		protected $config_table = 'sql_vendors_table';
	}
    
    class OSHandler extends SQLBase{
		protected $table = '';
		protected $rowtype = 'OS';
		protected $config_table = 'sql_os_table';
	}
    
	class SoftwareHandler extends SQLBase{
		protected $table = '';
		protected $rowtype = 'Software';
		protected $config_table = 'sql_names_table';
	}
	
    /*
    	Class: Base
        This is the base function for the objects that get passed
        around in the Handlers.
    */
    class Base{
    	/*
    		Function: __construct
    		Copies the passed array to the member array ($fields).
    		
    		Parameters:
    			$fields - Associative array representing a row in MySQL
    	*/
        function __construct($fields){
			foreach($fields as $field=>$value){
				if(isset($this->fields[$field])){
					$this->fields[$field] = $value;
				}
			}
		}
		/*
			Function: setField
			Sets a single field.
			
			Parameters:
				$field - A valid field for this object
				$value - A variable that can be evaluated as a String (not an Object or Array)
		*/
		public function setField($field, $value){
			if(isset($this->fields[$field])){
				$this->fields[$field] = $value;
			}
		}
		/*
			Function: setField
			Sets multiple fields.
			
			Parameters:
				$fields - Associative array mapping fields to values
		*/
        public function setFields($fields){
            foreach($fields as $field=>$value){
                if(isset($this->fields[$field])){
                    $this->fields[$field] = $value;
                }
            }
        }
        /*
        	Function: getField
        	Gets a single field.
        	
        	Parameters:
        		$field - Valid field key
        */
		public function getField($field){
			if(isset($this->fields[$field])){
				return $this->fields[$field];
			}
		}
		/*
			Function: getFields
			Get a set of fields
			
			Parameters:
				$type - Can be 'omit' or 'include'
				$list - Array of fields, either a black or white list based on $type
		*/
		public function getFields($type,$list){
			$fields = $this->fields;
			if($type == 'omit'){
				foreach($list as $field){
					unset($fields[$field]);
				}
			}
			elseif($type == 'include'){
				foreach($fields as $field=>$value){
					if(!in_array($field,$list)){
						unset($fields[$field]);
					}
				}
			}
			return $fields;
		}
		/*
			Function: getIndex
			Returns the index that operations like DELETE, UPDATE and INSERT rely on. 
		*/
		public function getIndex(){
			return $this->index;
		}
    }
    
    class Vendor extends Base{
		protected $fields = array(
			'VID'=>'', 
			'Name'=>'',
            'Contact'=>'',
            'Description'=>'',
            'PreviousVID'=>'',
			'Historic'=>'',);
        protected $index = 'VID';
	}
	
	class OS extends Base{
		protected $fields = array(
			'OSID'=>'', 
			'Name'=>'',);
		protected $index = 'OSID';
	}
    
    class Software extends Base{
		protected $fields = array(
			'SID'=>'', 
			'Name'=>'', 
			'Price'=>'',
			'OSID'=>'',
            'VID'=>'',
            'Notes'=>'',
            'Historic'=>'');
        protected $index = 'SID';
	}
	
	class Order extends Base{
		protected $fields = array(
            'OID'=>'', 
            'RTNumber'=>'', 
            'DateOrdered'=>'', 
            'DateInstalled'=>'', 
            'SoftwareName'=>'', 
            'CompOSName'=>'', 
            'Price'=>'',
            'ReqOdin'=>'', 
            'CompSerial'=>'', 
            'Authorizer'=>'', 
            'AccountIndex'=>'', 
            'PONumber'=>'', 
            'JVNumber'=>'', 
            'OETCNumber'=>'',
            'Notes'=>'', 
        );
        protected $index = 'OID';
	}
	
	class User extends Base{
		protected $fields = array(
			'UID'=>'', 
			'Odin'=>'', 
			'GID'=>'',
			'Flag'=>'',
		);
		protected $index = 'UID';
	}
	
	class Authorizer extends Base{
		protected $fields = array(
			'AID'=>'', 
			'Active'=>'', 
			'Odin'=>'',
			'FullName'=>'',
            'Department'=>'',
            'Software'=>'',
		);
		protected $index = 'AID';
	}
?>
