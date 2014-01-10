<?php
/**
 * API class to submit data into RT
 Things that have been modified and changed from the original
 
 added function "refersTo()" 
	this allows users to add a refers to ticket
 modified setBody() 
	needed to do this for the use of quick ticket
 added find($query) for ticket search, documentation for the api can be found at 
 	http://requesttracker.wikia.com/wiki/REST
 */

class RT_Api {
	const URI = 'https://support.oit.pdx.edu/'; // Default REST URI for RT
	const URI_DEV = 'https://stage.support.oit.pdx.edu/'; // REST URI for RT stage
	//const URI_DEV = 'https://support-stage.oit.pdx.edu/REST/1.0/'; // REST URI for RT stage
	const PATH_ADD = 'ticket/new/';
	const PATH_SEARCH = 'search/ticket';
	
	public static $debug = 0; // Debug = 1 will use URI_DEV, 0 will use URI

	/**
	 * @var Zend_Http_Client
	 */
	protected $_client;
	protected $_username;
	protected $_password;
	
	public function __construct($username, $password){
		define('APP_DIR', dirname(__FILE__));
        set_include_path(APP_DIR . '/library' . PATH_SEPARATOR . get_include_path());
		include_once 'Zend/Http/Client.php';
			$this->_client = new Zend_Http_Client();
			$this->_username = $username;
			$this->_password = $password;
		
	}
	
	public function getById($ticketId){
		$uri = $this->getUri() . 'ticket/'.$ticketId.'/show?'. $this->getAuthenticationParams();
		$this->_client->setUri($uri);
		$response = $this->_client->request('GET');
		$tickets = $this->formatTicketList($response->getBody());
		return $tickets;
	}
	
	/**
	 * @todo finish it
	 */
	public function getLinksById($ticketId){
		$uri = $this->getUri() . 'ticket/'.$ticketId.'/links/show?'. $this->getAuthenticationParams();
		$this->_client->setUri($uri);
		$response = $this->_client->request('GET');
		$links = $this->formatLinkList($response->getBody());
		foreach ($links as $key => $ids){
			foreach ($ids as $i => $id){
				$ticketList = $this->getById($id);
				$links[$key][$i] = $ticketList;
			}
		}
		return $links;
	}
	
	/**
	 * 
	 * Get history of a ticket, format the response text into PHP friendly
	 * array format 
	 * @param int $ticketId
	 * @return Ambiguous (Array)
	 */
	public function getHistoryById($ticketId){
		$uri = $this->getUri() . 'ticket/'.$ticketId.'/history?format=l&'. $this->getAuthenticationParams();
		$this->_client->setUri($uri);
		$response = $this->_client->request('GET');
		$history = $this->formatHistoryList($response->getBody());
		return $history;
	}
	/**
	 * Search for ticket using query builder in RT 
	 * @param String $query
	 */
	public function find($input){
		$query = "Owner = '$input' OR id = '$input' OR Subject LIKE '$input'";
		$uri = $this->getUri() . RT_Api::PATH_SEARCH . '?' . $this->getAuthenticationParams();
		$this->_client->setUri($uri);
		$this->_client->setParameterGet('query',$query);
		$this->_client->setParameterGet('orderby',$id);
		$this->_client->setParameterGet('format','l');
		$response = $this->_client->request('GET');
		$tickets = $this->formatTicketList($response->getBody());
		return $tickets;
	}
	
	protected function formatLinkList($content){
		$tmpLinks = $this->formatTicketList($content);
		$links = array();
		$preKey = '';
		unset($tmpLinks['id']);
		foreach($tmpLinks as $key => $link){
			if(empty($link)){
				$tmpId = explode('/', $key);
				$id = $tmpId[count($tmpId)-1];
				$links[$preKey] .= $id;
			} else {
				$tmpId = explode('/', $link);
				$links[$key] = $tmpId[count($tmpId)-1];
				$preKey = $key;
			}
		}
		foreach ($links as $key => $value){
			$links[$key] = explode(',', $value);
		}
		return $links;
	}
	/**
	 * Take an input of RT ticket returned from REST API and 
	 * translate it into PHP Array contain the ticket with its
	 * attributes
	 * 
	 * @param text $content
	 * @return array
	 */
	protected function formatTicketList($content){
		// Looking for successful header
		$resultsList = explode("200 Ok\n",$content);
		// Separate the header and the actual body that contains the ticket list
		$results = explode("\n\n--\n", $resultsList[1]);
		// Check if there is any ticket in the body
		if (!empty($results) && strpos($results[0], "id: ticket") > 0) { // if result is not empty
			// Translate the text list into array
			foreach ($results as $j => $ticket){
				// If there are Content attribute, 
				$attributes = explode("\n",$ticket);
				if(empty($attributes[0])) unset($attributes[0]);
				if(empty($attributes[count($attributes)-1])) unset($attributes[count($attributes)-1]);
				foreach ($attributes as $i => $data){
					$keys = explode(": ", $data);
					unset($attributes[$i]);
					// Remove the 'ticket/' part of the id field
					if ($keys[0] == 'id'){
						$tmp = explode('/', $keys[1]);
						$keys[1] = $tmp[1];
					}
					if (!empty($keys[0])) $attributes[$keys[0]] = $keys[1];
				}
				$results[$j] = $attributes;
			}
		}
		return (count($results) > 1) ? $results : $results[0];
	}
	
	/**
	 * Take an input of RT ticket returned from REST API and 
	 * translate it into PHP Array contain the ticket with its
	 * attributes
	 * 
	 * @param text $content
	 * @return array
	 */
	protected function formatHistoryList($content){
		// Looking for successful header
		$resultsList = explode("200 Ok\n",$content);
		// Separate the header and the actual body that contains the ticket list
		$results = explode("\n\n--\n", $resultsList[1]);
		// Check if there is any ticket in the body
		if (!empty($results) && strpos($results[0], "id: ") > 0) { // if result if not empty
			// Translate the text list into array
			foreach ($results as $j => $ticket){
				// Get content atttribute out and treat it separately
				$head = explode("Content: ", $ticket);
				$tail = explode("Creator:", $head[1]);
				$contentBody = $tail[0];
				$ticket = $head[0] . "Creator:" . $tail[1];
				$attributes = explode("\n",$ticket);
				if(empty($attributes[0])) unset($attributes[0]);
				if(empty($attributes[count($attributes)-1])) unset($attributes[count($attributes)-1]);
				foreach ($attributes as $i => $data){
					$keys = explode(": ", $data);
					unset($attributes[$i]);
					if (!empty($keys[0])) $attributes[$keys[0]] = $keys[1];
				}
				$attributes['Content'] = trim($contentBody);
				$results[$j] = $attributes;
			}
		}
		return $results;
	}
	
	/**
	 * Create new ticket using the API then return the 
	 * ticket after populating the new ticket ID
	 * @param Ticket $ticket
	 * @return Ticket
	 */
	public function createTicket(Ticket $ticket)
	{
		$uri = $this->getUri() . RT_Api::PATH_ADD . '?' . $this->getAuthenticationParams();
		$this->_client->setUri($uri);
		RT_Api::$debug ? $ticket->Subject = '[TEST TICKET, PLEASE IGNORE]' . $ticket->Subject : null;
		$this->_client->setParameterPost('content', (string)$ticket);
		$response = $this->_client->request('POST');
		preg_match('/Ticket(.*)created/', $response->getBody(), $ticketId);
		if (!isset($ticketId[1]) || trim($ticketId[1]) == 'ticket/new') {
			$ticket->id = false;
			$body = $response->getBody();
			if(RT_Api::$debug){
				throw new RT_Api_Exception("Cannot create new ticket.\n\n DEBUG: {var_dump($response->getBody())} \n\n", 1);	
			}
		} else {
			$ticket->id = trim($ticketId[1]);			
		}
		return $ticket;
	}
    
    public function reply($id, $text, $cc=false, $bcc=false, $timeworked=false, $attachment=false){ //added by Samuel Mortenson 7/13/12
		$content = "id: $id\nAction: correspond\nText: $text\n";
		if($cc)
			$content = $content . "Cc: $cc\n";
		if($bcc)
			$content = $content . "Bcc: $bcc\n";
		if($timeworked)
			$content = $content . "TimeWorked: $timeworked\n";
		if($attachment)
			$content = $content . "Attachment: $attachment\n";
		$uri = $this->getUri() . "ticket/$id/comment/" . 'NoAuth?' . $this->getAuthenticationParams();
		$this->_client->setUri($uri);
		$this->_client->setParameterPost('content', $content);
		$response = $this->_client->request('POST');
		return($response);
	}
	
	public function setLinks($ticketId, $linkKey, $linkValue){
		$uri = $this->getUri() . 'ticket/' . $ticketId . '/links' . '?' . $this->getAuthenticationParams();
		$this->_client->setUri($uri);
		$values = explode(' ',$linkValue);
		$content;
		foreach ($values as $id){
			$content[] = $linkKey . ': fsck.com-rt://pdx.edu/ticket/' . $id;
		}
		$content = implode("\n", $content);
		$this->_client->setParameterPost('content', $content);
		$response = $this->_client->request('POST');
		$body = $response->getBody();
		//echo $body;
		//print_r($this->_client->getLastRequest());die;
		return preg_match("/OK/ui", $body);
	}
	
	//this function sets the "Refers To" field in rt if you supply the ticket and the referred ticket
	public function refersTo($ticket,$referredTicket){
		$uri = $this->getUri() . 'ticket/' . $ticket . '/links' . '?' . $this->getAuthenticationParams();
		$this->_client->setUri($uri);
		$this->_client->setParameterPost('content', 'RefersTo: fsck.com-rt://pdx.edu/ticket/' . $referredTicket );
		$this->_client->request('POST');
	}
	
	protected function getAuthenticationParams()
	{
		return 'user='.$this->_username.'&pass='.$this->_password;
	}
	
	protected function getUri(){
		$domain = (RT_Api::$debug) ? RT_Api::URI_DEV : RT_Api::URI;
		$uri = $domain . '/NoAuthCAS/REST/1.0/';
		return $uri;
	}
	
	public function getUrl() {
		return $domain = (RT_Api::$debug) ? RT_Api::URI_DEV : RT_Api::URI;
	}
}

class Ticket {
	public $id;
	public $Queue;
	public $Requestor;
	public $Subject;
	public $Cc;
	public $AdminCc;
	public $Owner;
	public $Status;
	public $Priority;
	public $InitialPriority;
	public $FinalPriority;
	public $TimeEstimated;
	public $Starts;
	public $Due;
	public $Text;
	protected $_customFields = array();
	
	/**
	 * Set custom field for Ticket
	 * @param string $name
	 * @param string $value
	 */
	public function setCustomField($name, $value){
		$this->_customFields[$name] = $value;
	}
	
	/**
	 * Convert object to string
	 */
	public function __toString(){
		$data = array();
		$vars = get_object_vars($this);
		foreach ($vars as $key => $value){
			if ($key == '_customFields'){
				foreach ($this->_customFields as $cfkey => $cfvalue){
					$data[] = 'CF-' . $cfkey . ': ' . $cfvalue;
				}
			} else {
				$data[] = $key . ': ' . $value;				
			}
		}
		$string = implode("\n", $data);
		return $string;
	}
	
	public function setBody($subject, $requester, $info, $keywords='QT2', $extra='') {
		$strinfo = '';
		foreach($info as $k => $v) {
			// Fix new line character problem for the form
			$v = str_replace("\n", " \n ", $v);
			$strinfo .= $v ? " $k: $v\n" : " $k:\n"; //add any request specific data
		} 
			
		$keywords = is_array($keywords)? implode(" ", $keywords) : $keywords; //add however many keywords
		
		#format the email
		$requester_info = '';
		foreach($requester as $key => $value){
			// email and name will be put in different way
			$key = trim($key);
			$value = trim($value);
			if($key != 'name' || $key != 'email')
			// Fix new line character problem for the form
			$value = str_replace("\n", " \n ", $value);
			$requester_info .= " $key: $value \n";
		}
		
		$body=" $requester_info ".
		" $strinfo ".
		" \n \n \n \n ";
		if ($keywords) $body .= "Keywords: {$keywords}";
		
		$this->Text = $body;	
}
}
class RT_Api_Exception extends Exception {}
