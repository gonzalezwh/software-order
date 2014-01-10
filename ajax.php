<?php 
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 1);
    /**
        If you read sql.php most of the functions here will make sense,
            but in general the core of this endpoint is:
            1) Call a local function base on $_GET['requset']
            2) Create a new SQL Handler based on the $_GET['type']
            3) Call the shared methods of those Handlers to perform modular
               SQL requests.
        See auth.php for the authentication proccses.
    */
    include_once "cas_init.php"; //require authentication
    include_once "auth.php";
    include_once "sql.php";
    include_once APP_DIR."/library/ldap-fetch.php";
    if(isset($_GET['request'])){
        $request = $_GET['request'];
        $type = $_GET['type'];
        $options = array();
        if(isset($_GET['options'])){
            $temp = json_decode($_GET['options'],TRUE);
            if(is_array($temp)){
                $options = $temp;
            }
            else{
                $options = $_GET['options'];
            }
        }
        $param = array('type'=>$type,'options'=>$options);
        $result = call_user_func($request, $param);
        if(!$result){
            echo 'Invalid Request';
        }
        else{
            echo json_encode($result);
        }
    }
	function getdata($param){ 
        $type = $param['type'];
        $options = $param['options'];
		if(!check_read_access($type)){
			return FALSE;
		}
		$sql = $type.'Handler';
        $handler = new $sql();
		$list = $handler->get($options);
		$array = array();
		foreach($list as $key=>$item){
			$array[$key] = $item->getFields();
		}
		return $array;
	}
    function getcurrentuser(){
    	$odin = phpCAS::getUser();
		$uhandler = new UserHandler();
		$user = $uhandler->get(array(
			'field'=>'Odin',
			'operator'=>'LIKE',
			'value'=>$odin));
		$array = array();
		if(count($user) > 0){
			$array = $user[1]->getFields();
		}
		else{
			$array['UID'] = 0;
			$array['Odin'] = $odin;
		}
		return $array;
    }
    function getcurrentldapinfo(){
    	$odin = phpCAS::getUser();
        $user = ldap_fetch($odin);
        return $user;
    }
    function getsoftwarelist(){
        $config = parse_ini_file('config.ini');
        $server = $config['sql_server'];
        $user = $config['sql_user'];
        $pass = $config['sql_pass'];
        $db = $config['sql_db'];
        $conn = new mysqli($server, $user, $pass, $db);
        $software=softwarelist($conn);
        return $software;
    }

    function getldapinfo($param){
        $odin = $param['options'];
        $user = ldap_fetch($odin);
        return $user;
    }
    function getdepartmentlist(){
    	$departments = include_once("departments.inc");
    	return $departments;
    }
    //this makes it easier for the client to load form data, restricts amount of
    //ajax calls they have to deal with
    function getformdata(){
    	$array = array();
		$shandler = new SoftwareHandler();
		$options = array(
			'field'=>'Historic',
			'operator'=>'LIKE',
			'value'=>'0',
		);
		$list = $shandler->get($options);
		foreach($list as $key=>$item){
			$array['Softwares'][$key] = $item->getFields('omit',array('Historic'));
		}
		$vhandler = new VendorHandler();
		$list = $vhandler->get($options);
		foreach($list as $key=>$item){
			$array['Vendors'][$key] = $item->getFields('omit',array('Historic','Contact','Description'));
		}
		$oshandler = new OSHandler();
		$list = $oshandler->get();
		foreach($list as $key=>$item){
			$array['OSlist'][$key] = $item->getFields();
		}
		$ahandler = new AuthorizerHandler();
		$list = $ahandler->get(array(
			'field'=>'Active',
			'operator'=>'LIKE',
			'value'=>'1',
            'orderby'=>'Department',
            'assoc'=>'false', //javascript can't really understand array sorting
		));
		foreach($list as $key=>$item){
			$array['Authorizers'][$key] = $item->getFields('omit',array('Active'));
		}
        $array['Departments'] = getdepartmentlist();
        $array['Software'] = getsoftwarelist();
		$array['LDAPInfo'] = getcurrentldapinfo();
    	return $array;
    }
    function getmyorders($param){
        $odin = phpCAS::getUser();
        $type = $param['type'];
        $options = $param['options'];
        $start_date = $options['start_date']; 
        $end_date = $options['end_date'];
        $ohandler = new OrderHandler();
        $array = array();
        $array['Requested'] = array();
        $array['Authorized'] = array();
        $orders = $ohandler->get(array(
            'condition_tree'=> array(
                'field'=>'ReqOdin',
                'operator'=>'=',
                'value'=>$odin,
                'AND'=>array(
                    'field'=>'DateInstalled',
                    'operator'=>'>=',
                    'value'=>$start_date,
                    'AND'=>array(
                        'field'=>'DateInstalled',
                        'operator'=>'<=',
                        'value'=>$end_date,
                    ),
            )),
            'assoc'=>'false',
            'orderby'=>'DateOrdered'
        ));
        foreach($orders as $item){
			$array['Requested'][] = $item->getFields();
		}
        $orders = $ohandler->get(array(
            'condition_tree'=> array(
                'field'=>'Authorizer',
                'operator'=>'=',
                'value'=>$odin,
                'AND'=>array(
                    'field'=>'DateInstalled',
                    'operator'=>'>=',
                    'value'=>$start_date,
                    'AND'=>array(
                        'field'=>'DateInstalled',
                        'operator'=>'<=',
                        'value'=>$end_date,
                    ),
            )),
            'assoc'=>'false',
            'orderby'=>'DateOrdered'
        ));
        foreach($orders as $item){
			$array['Authorized'][] = $item->getFields();
		}
        return $array;
    }
    function add($param){
		$type = $param['type'];
		if(!check_write_access($type)){
			return FALSE;
		}
		$sql = $type.'Handler';
		$handler = new $sql();
		$object = new $type();
    	$result = $handler->add($object);
    	return $result;
	}
	function update($param){
		$type = $param['type'];
		$options = $param['options'];
		if(!check_write_access($type)){
			return FALSE;
		}
		$sql = $type.'Handler';
		$handler = new $sql();
		$object = new $type($options);
		$result = $handler->update($object);
		return $result;
	}
	function remove($param){
		$type = $param['type'];
		$id = $param['options'];
		if(!check_write_access($type)){
			return FALSE;
		}
		$sql = $type.'Handler';
		$handler = new $sql();
    	$result = $handler->remove($id);
    	return $result;
    }
    
    
function softwarelist(&$conn){
    $sql="select SID, NAME, PRICE  from software_main order by name ";
    $result=mysqli_query($conn,$sql);
    $string = "  ''=>'' ,";

    $data = array();
    while ($row = mysqli_fetch_array($result)) {
        $data[$row['NAME']] = $row['NAME'];
    }
    
    return $data;
}
?>
