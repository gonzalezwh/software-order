<?php
/**
 *Fetch a users ldap data in a chunk, by a key or by an array of keys
 *This code was written by Thai Bui of Portland State University OIT.
 */
function ldap_fetch($username,$key=False,$ldap_server='ldap://ldap.oit.pdx.edu'){
	$connected = ldap_connect($ldap_server);
	$res_id = ldap_search($connected, "ou=people,dc=pdx,dc=edu", "uid=$username");
	$entry_id = ldap_first_entry($connected, $res_id);
	$ldata = $entry_id? ldap_get_attributes($connected, $entry_id) : False;
	if (!is_array($key))
    $data = ($key AND array_key_exists($key, $ldata))? $ldata[$key] : $ldata;
  elseif (!is_array($ldata)) return False;
	else foreach ($key as $value)
    $data[$value] =  array_key_exists($value, $ldata)? $ldata[$value][0] : '';
	return $data;
}

function ldap_fetch_pidm($pidm,$ldap_server='ldap://ldap.oit.pdx.edu'){
	$connected = ldap_connect($ldap_server);
	$res_id = ldap_search($connected, "ou=people,dc=pdx,dc=edu", "uniqueIdentifier=p$pidm");
	$entry_id = ldap_first_entry($connected, $res_id);
	$ldata = $entry_id? ldap_get_attributes($connected, $entry_id) : False;
  $data = $ldata['mail'];
  if($data) return $data;
  else return "FAILED on {$pidm}";
}
function nonumeric_keys($array){
  foreach($array as $k => $v) if(!is_int($k)) $keys[] = $k;
  return $keys;
}

// Append "?user=<username>" to receive LDAP data in JSON format
if(isset($_GET['user'])) {
  require_once 'jsonwrapper/jsonwrapper.php';
  $username = $_GET['user'];
  $data = ldap_fetch($username);
  echo json_encode($data);
}
?>
