<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

if ( ! function_exists('xml_parser_create'))
{
	show_error('Your PHP installation does not support XML');
}


// ------------------------------------------------------------------------

/**
 * XML-RPC request handler class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	XML-RPC
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/xmlrpc.html
 */
class CI_Xmlrpc {

	var $debug			= FALSE;	// Debugging on or off
	var $xmlrpcI4		= 'i4';
	var $xmlrpcInt		= 'int';
	var $xmlrpcBoolean	= 'boolean';
	var $xmlrpcDouble	= 'double';
	var $xmlrpcString	= 'string';
	var $xmlrpcDateTime	= 'dateTime.iso8601';
	var $xmlrpcBase64	= 'base64';
	var $xmlrpcArray	= 'array';
	var $xmlrpcStruct	= 'struct';

	var $xmlrpcTypes	= array();
	var $valid_parents	= array();
	var $xmlrpcerr		= array();	// Response numbers
	var $xmlrpcstr		= array();  // Response strings

	var $xmlrpc_defencoding = 'UTF-8';
	var $xmlrpcName			= 'XML-RPC for CodeIgniter';
	var $xmlrpcVersion		= '1.1';
	var $xmlrpcerruser		= 800; // Start of user errors
	var $xmlrpcerrxml		= 100; // Start of XML Parse errors
	var $xmlrpc_backslash	= ''; // formulate backslashes for escaping regexp

	var $client;
	var $method;
	var $data;
	var $message			= '';
	var $error				= '';		// Error string for request
	var $result;
	var $response			= array();  // Response from remote server

	var $xss_clean			= TRUE;

	//-------------------------------------
	//  VALUES THAT MULTIPLE CLASSES NEED
	//-------------------------------------

	public function __construct($config = array())
	{
		$this->xmlrpcName		= $this->xmlrpcName;
		$this->xmlrpc_backslash = chr(92).chr(92);

		// Types for info sent back and forth
		$this->xmlrpcTypes = array(
			$this->xmlrpcI4	 		=> '1',
			$this->xmlrpcInt		=> '1',
			$this->xmlrpcBoolean	=> '1',
			$this->xmlrpcString		=> '1',
			$this->xmlrpcDouble		=> '1',
			$this->xmlrpcDateTime	=> '1',
			$this->xmlrpcBase64		=> '1',
			$this->xmlrpcArray		=> '2',
			$this->xmlrpcStruct		=> '3'
			);

		// Array of Valid Parents for Various XML-RPC elements
		$this->valid_parents = array('BOOLEAN'			=> array('VALUE'),
									 'I4'				=> array('VALUE'),
									 'INT'				=> array('VALUE'),
									 'STRING'			=> array('VALUE'),
									 'DOUBLE'			=> array('VALUE'),
									 'DATETIME.ISO8601'	=> array('VALUE'),
									 'BASE64'			=> array('VALUE'),
									 'ARRAY'			=> array('VALUE'),
									 'STRUCT'			=> array('VALUE'),
									 'PARAM'			=> array('PARAMS'),
									 'METHODNAME'		=> array('METHODCALL'),
									 'PARAMS'			=> array('METHODCALL', 'METHODRESPONSE'),
									 'MEMBER'			=> array('STRUCT'),
									 'NAME'				=> array('MEMBER'),
									 'DATA'				=> array('ARRAY'),
									 'FAULT'			=> array('METHODRESPONSE'),
									 'VALUE'			=> array('MEMBER', 'DATA', 'PARAM', 'FAULT')
									 );


		// XML-RPC Responses
		$this->xmlrpcerr['unknown_method'] = '1';
		$this->xmlrpcstr['unknown_method'] = 'This is not a known method for this XML-RPC Server';
		$this->xmlrpcerr['invalid_return'] = '2';
		$this->xmlrpcstr['invalid_return'] = 'The XML data received was either invalid or not in the correct form for XML-RPC.  Turn on debugging to examine the XML data further.';
		$this->xmlrpcerr['incorrect_params'] = '3';
		$this->xmlrpcstr['incorrect_params'] = 'Incorrect parameters were passed to method';
		$this->xmlrpcerr['introspect_unknown'] = '4';
		$this->xmlrpcstr['introspect_unknown'] = "Cannot inspect signature for request: method unknown";
		$this->xmlrpcerr['http_error'] = '5';
		$this->xmlrpcstr['http_error'] = "Did not receive a '200 OK' response from remote server.";
		$this->xmlrpcerr['no_data'] = '6';
		$this->xmlrpcstr['no_data'] ='No data received from server.';

		$this->initialize($config);

		log_message('debug', "XML-RPC Class Initialized");
	}


	//-------------------------------------
	//  Initialize Prefs
	//-------------------------------------

	function initialize($config = array())
	{
		if (count($config) > 0)
		{
			foreach ($config as $key => $val)
			{
				if (isset($this->$key))
				{
					$this->$key = $val;
				}
			}
		}
	}
	// END

	//-------------------------------------
	//  Take URL and parse it
	//-------------------------------------

	function server($url, $port=80)
	{
		if (substr($url, 0, 4) != "http")
		{
			$url = "http://".$url;
		}

		$parts = parse_url($url);

		$path = ( ! isset($parts['path'])) ? '/' : $parts['path'];

		if (isset($parts['query']) && $parts['query'] != '')
		{
			$path .= '?'.$parts['query'];
		}

		$this->client = new XML_RPC_Client($path, $parts['host'], $port);
	}
	// END

	//-------------------------------------
	//  Set Timeout
	//-------------------------------------

	function timeout($seconds=5)
	{
		if ( ! is_null($this->client) && is_int($seconds))
		{
			$this->client->timeout = $seconds;
		}
	}
	// END

	//-------------------------------------
	//  Set Methods
	//-------------------------------------

	function method($function)
	{
		$this->method = $function;
	}
	// END

	//-------------------------------------
	//  Take Array of Data and Create Objects
	//-------------------------------------

	function request($incoming)
	{
		if ( ! is_array($incoming))
		{
			// Send Error
		}

		$this->data = array();

		foreach ($incoming as $key => $value)
		{
			$this->data[$key] = $this->values_parsing($value);
		}
	}
	// END


	//-------------------------------------
	//  Set Debug
	//-------------------------------------

	function set_debug($flag = TRUE)
	{
		$this->debug = ($flag == TRUE) ? TRUE : FALSE;
	}

	//-------------------------------------
	//  Values Parsing
	//-------------------------------------

	function values_parsing($value, $return = FALSE)
	{
		if (is_array($value) && array_key_exists(0, $value))
		{
			if ( ! isset($value['1']) OR ( ! isset($this->xmlrpcTypes[$value['1']])))
			{
				if (is_array($value[0]))
				{
					$temp = new XML_RPC_Values($value['0'], 'array');
				}
				else
				{
					$temp = new XML_RPC_Values($value['0'], 'string');
				}
			}
			elseif (is_array($value['0']) && ($value['1'] == 'struct' OR $value['1'] == 'array'))
			{
				while (list($k) = each($value['0']))
				{
					$value['0'][$k] = $this->values_parsing($value['0'][$k], TRUE);
				}

				$temp = new XML_RPC_Values($value['0'], $value['1']);
			}
			else
			{
				$temp = new XML_RPC_Values($value['0'], $value['1']);
			}
		}
		else
		{
			$temp = new XML_RPC_Values($value, 'string');
		}

		return $temp;
	}
	// END


	//-------------------------------------
	//  Sends XML-RPC Request
	//-------------------------------------

	function send_request()
	{
		$this->message = new XML_RPC_Message($this->method,$this->data);
		$this->message->debug = $this->debug;

		if ( ! $this->result = $this->client->send($this->message))
		{
			$this->error = $this->result->errstr;
			return FALSE;
		}
		elseif ( ! is_object($this->result->val))
		{
			$this->error = $this->result->errstr;
			return FALSE;
		}

		$this->response = $this->result->decode();

		return TRUE;
	}
	// END

	//-------------------------------------
	//  Returns Error
	//-------------------------------------

	function display_error()
	{
		return $this->error;
	}
	// END

	//-------------------------------------
	//  Returns Remote Server Response
	//-------------------------------------

	function display_response()
	{
		return $this->response;
	}
	// END

	//-------------------------------------
	//  Sends an Error Message for Server Request
	//-------------------------------------

	function send_error_message($number, $message)
	{
		return new XML_RPC_Response('0',$number, $message);
	}
	// END


	//-------------------------------------
	//  Send Response for Server Request
	//-------------------------------------

	function send_response($response)
	{
		// $response should be array of values, which will be parsed
		// based on their data and type into a valid group of XML-RPC values

		$response = $this->values_parsing($response);

		return new XML_RPC_Response($response);
	}
	// END

} // END XML_RPC Class



/**
 * XML-RPC Client class
 *
 * @category	XML-RPC
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/xmlrpc.html
 */
class XML_RPC_Client extends CI_Xmlrpc
{
	var $path			= '';
	var $server			= '';
	var $port			= 80;
	var $errno			= '';
	var $errstring		= '';
	var $timeout		= 5;
	var $no_multicall	= FALSE;

	public function __construct($path, $server, $port=80)
	{
		parent::__construct();

		$this->port = $port;
		$this->server = $server;
		$this->path = $path;
	}

	function send($msg)
	{
		if (is_array($msg))
		{
			// Multi-call disabled
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['multicall_recursion'],$this->xmlrpcstr['multicall_recursion']);
			return $r;
		}

		return $this->sendPayload($msg);
	}

	function sendPayload($msg)
	{
		$fp = @fsockopen($this->server, $this->port,$this->errno, $this->errstr, $this->timeout);

		if ( ! is_resource($fp))
		{
			error_log($this->xmlrpcstr['http_error']);
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['http_error'],$this->xmlrpcstr['http_error']);
			return $r;
		}

		if (empty($msg->payload))
		{
			// $msg = XML_RPC_Messages
			$msg->createPayload();
		}

		$r = "\r\n";
		$op  = "POST {$this->path} HTTP/1.0$r";
		$op .= "Host: {$this->server}$r";
		$op .= "Content-Type: text/xml$r";
		$op .= "User-Agent: {$this->xmlrpcName}$r";
		$op .= "Content-Length: ".strlen($msg->payload). "$r$r";
		$op .= $msg->payload;


		if ( ! fputs($fp, $op, strlen($op)))
		{
			error_log($this->xmlrpcstr['http_error']);
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['http_error'], $this->xmlrpcstr['http_error']);
			return $r;
		}
		$resp = $msg->parseResponse($fp);
		fclose($fp);
		return $resp;
	}

} // end class XML_RPC_Client


/**
 * XML-RPC Response class
 *
 * @category	XML-RPC
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/xmlrpc.html
 */
class XML_RPC_Response
{
	var $val = 0;
	var $errno = 0;
	var $errstr = '';
	var $headers = array();
	var $xss_clean = TRUE;

	public function __construct($val, $code = 0, $fstr = '')
	{
		if ($code != 0)
		{
			// error
			$this->errno = $code;
			$this->errstr = htmlentities($fstr);
		}
		else if ( ! is_object($val))
		{
			// programmer error, not an object
			error_log("Invalid type '" . gettype($val) . "' (value: $val) passed to XML_RPC_Response.  Defaulting to empty value.");
			$this->val = new XML_RPC_Values();
		}
		else
		{
			$this->val = $val;
		}
	}

	function faultCode()
	{
		return $this->errno;
	}

	function faultString()
	{
		return $this->errstr;
	}

	function value()
	{
		return $this->val;
	}

	function prepare_response()
	{
		$result = "<methodResponse>\n";
		if ($this->errno)
		{
			$result .= '<fault>
	<value>
		<struct>
			<member>
				<name>faultCode</name>
				<value><int>' . $this->errno . '</int></value>
			</member>
			<member>
				<name>faultString</name>
				<value><string>' . $this->errstr . '</string></value>
			</member>
		</struct>
	</value>
</fault>';
		}
		else
		{
			$result .= "<params>\n<param>\n" .
					$this->val->serialize_class() .
					"</param>\n</params>";
		}
		$result .= "\n</methodResponse>";
		return $result;
	}

	function decode($array=FALSE)
	{
		$CI =& get_instance();
		
		if ($array !== FALSE && is_array($array))
		{
			while (list($key) = each($array))
			{
				if (is_array($array[$key]))
				{
					$array[$key] = $this->decode($array[$key]);
				}
				else
				{
					$array[$key] = ($this->xss_clean) ? $CI->security->xss_clean($array[$key]) : $array[$key];
				}
			}

			$result = $array;
		}
		else
		{
			$result = $this->xmlrpc_decoder($this->val);

			if (is_array($result))
			{
				$result = $this->decode($result);
			}
			else
			{
				$result = ($this->xss_clean) ? $CI->security->xss_clean($result) : $result;
			}
		}

		return $result;
	}



	//-------------------------------------
	//  XML-RPC Object to PHP Types
	//-------------------------------------

	function xmlrpc_decoder($xmlrpc_val)
	{
		$kind = $xmlrpc_val->kindOf();

		if ($kind == 'scalar')
		{
			return $xmlrpc_val->scalarval();
		}
		elseif ($kind == 'array')
		{
			reset($xmlrpc_val->me);
			list($a,$b) = each($xmlrpc_val->me);
			$size = count($b);

			$arr = array();

			for ($i = 0; $i < $size; $i++)
			{
				$arr[] = $this->xmlrpc_decoder($xmlrpc_val->me['array'][$i]);
			}
			return $arr;
		}
		elseif ($kind == 'struct')
		{
			reset($xmlrpc_val->me['struct']);
			$arr = array();

			while (list($key,$value) = each($xmlrpc_val->me['struct']))
			{
				$arr[$key] = $this->xmlrpc_decoder($value);
			}
			return $arr;
		}
	}


	//-------------------------------------
	//  ISO-8601 time to server or UTC time
	//-------------------------------------

	function iso8601_decode($time, $utc=0)
	{
		// return a timet in the localtime, or UTC
		$t = 0;
		if (preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})/', $time, $regs))
		{
			$fnc = ($utc == 1) ? 'gmmktime' : 'mktime';
			$t = $fnc($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
		}
		return $t;
	}

} // End Response Class



/**
 * XML-RPC Message class
 *
 * @category	XML-RPC
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/xmlrpc.html
 */
class XML_RPC_Message extends CI_Xmlrpc
{
	var $payload;
	var $method_name;
	var $params			= array();
	var $xh				= array();

	public function __construct($method, $pars=0)
	{
		parent::__construct();

		$this->method_name = $method;
		if (is_array($pars) && count($pars) > 0)
		{
			for ($i=0; $i<count($pars); $i++)
			{
				// $pars[$i] = XML_RPC_Values
				$this->params[] = $pars[$i];
			}
		}
	}

	//-------------------------------------
	//  Create Payload to Send
	//-------------------------------------

	function createPayload()
	{
		$this->payload = "<?xml version=\"1.0\"?".">\r\n<methodCall>\r\n";
		$this->payload .= '<methodName>' . $this->method_name . "</methodName>\r\n";
		$this->payload .= "<params>\r\n";

		for ($i=0; $i<count($this->params); $i++)
		{
			// $p = XML_RPC_Values
			$p = $this->params[$i];
			$this->payload .= "<param>\r\n".$p->serialize_class()."</param>\r\n";
		}

		$this->payload .= "</params>\r\n</methodCall>\r\n";
	}

	//-------------------------------------
	//  Parse External XML-RPC Server's Response
	//-------------------------------------

	function parseResponse($fp)
	{
		$data = '';

		while ($datum = fread($fp, 4096))
		{
			$data .= $datum;
		}

		//-------------------------------------
		//  DISPLAY HTTP CONTENT for DEBUGGING
		//-------------------------------------

		if ($this->debug === TRUE)
		{
			echo "<pre>";
			echo "---DATA---\n" . htmlspecialchars($data) . "\n---END DATA---\n\n";
			echo "</pre>";
		}

		//-------------------------------------
		//  Check for data
		//-------------------------------------

		if ($data == "")
		{
			error_log($this->xmlrpcstr['no_data']);
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['no_data'], $this->xmlrpcstr['no_data']);
			return $r;
		}


		//-------------------------------------
		//  Check for HTTP 200 Response
		//-------------------------------------

		if (strncmp($data, 'HTTP', 4) == 0 && ! preg_match('/^HTTP\/[0-9\.]+ 200 /', $data))
		{
			$errstr= substr($data, 0, strpos($data, "\n")-1);
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['http_error'], $this->xmlrpcstr['http_error']. ' (' . $errstr . ')');
			return $r;
		}

		//-------------------------------------
		//  Create and Set Up XML Parser
		//-------------------------------------

		$parser = xml_parser_create($this->xmlrpc_defencoding);

		$this->xh[$parser]					= array();
		$this->xh[$parser]['isf']			= 0;
		$this->xh[$parser]['ac']			= '';
		$this->xh[$parser]['headers']		= array();
		$this->xh[$parser]['stack']			= array();
		$this->xh[$parser]['valuestack']	= array();
		$this->xh[$parser]['isf_reason']	= 0;

		xml_set_object($parser, $this);
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
		xml_set_element_handler($parser, 'open_tag', 'closing_tag');
		xml_set_character_data_handler($parser, 'character_data');
		//xml_set_default_handler($parser, 'default_handler');


		//-------------------------------------
		//  GET HEADERS
		//-------------------------------------

		$lines = explode("\r\n", $data);
		while (($line = array_shift($lines)))
		{
			if (strlen($line) < 1)
			{
				break;
			}
			$this->xh[$parser]['headers'][] = $line;
		}
		$data = implode("\r\n", $lines);


		//-------------------------------------
		//  PARSE XML DATA
		//-------------------------------------

		if ( ! xml_parse($parser, $data, count($data)))
		{
			$errstr = sprintf('XML error: %s at line %d',
					xml_error_string(xml_get_error_code($parser)),
					xml_get_current_line_number($parser));
			//error_log($errstr);
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['invalid_return'], $this->xmlrpcstr['invalid_return']);
			xml_parser_free($parser);
			return $r;
		}
		xml_parser_free($parser);

		// ---------------------------------------
		//  Got Ourselves Some Badness, It Seems
		// ---------------------------------------

		if ($this->xh[$parser]['isf'] > 1)
		{
			if ($this->debug === TRUE)
			{
				echo "---Invalid Return---\n";
				echo $this->xh[$parser]['isf_reason'];
				echo "---Invalid Return---\n\n";
			}

			$r = new XML_RPC_Response(0, $this->xmlrpcerr['invalid_return'],$this->xmlrpcstr['invalid_return'].' '.$this->xh[$parser]['isf_reason']);
			return $r;
		}
		elseif ( ! is_object($this->xh[$parser]['value']))
		{
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['invalid_return'],$this->xmlrpcstr['invalid_return'].' '.$this->xh[$parser]['isf_reason']);
			return $r;
		}

		//-------------------------------------
		//  DISPLAY XML CONTENT for DEBUGGING
		//-------------------------------------

		if ($this->debug === TRUE)
		{
			echo "<pre>";

			if (count($this->xh[$parser]['headers'] > 0))
			{
				echo "---HEADERS---\n";
				foreach ($this->xh[$parser]['headers'] as $header)
				{
					echo "$header\n";
				}
				echo "---END HEADERS---\n\n";
			}

			echo "---DATA---\n" . htmlspecialchars($data) . "\n---END DATA---\n\n";

			echo "---PARSED---\n" ;
			var_dump($this->xh[$parser]['value']);
			echo "\n---END PARSED---</pre>";
		}

		//-------------------------------------
		//  SEND RESPONSE
		//-------------------------------------

		$v = $this->xh[$parser]['value'];

		if ($this->xh[$parser]['isf'])
		{
			$errno_v = $v->me['struct']['faultCode'];
			$errstr_v = $v->me['struct']['faultString'];
			$errno = $errno_v->scalarval();

			if ($errno == 0)
			{
				// FAULT returned, errno needs to reflect that
				$errno = -1;
			}

			$r = new XML_RPC_Response($v, $errno, $errstr_v->scalarval());
		}
		else
		{
			$r = new XML_RPC_Response($v);
		}

		$r->headers = $this->xh[$parser]['headers'];
		return $r;
	}

	// ------------------------------------
	//  Begin Return Message Parsing section
	// ------------------------------------

	// quick explanation of components:
	//   ac - used to accumulate values
	//   isf - used to indicate a fault
	//   lv - used to indicate "looking for a value": implements
	//		the logic to allow values with no types to be strings
	//   params - used to store parameters in method calls
	//   method - used to store method name
	//	 stack - array with parent tree of the xml element,
	//			 used to validate the nesting of elements

	//-------------------------------------
	//  Start Element Handler
	//-------------------------------------

	function open_tag($the_parser, $name, $attrs)
	{
		// If invalid nesting, then return
		if ($this->xh[$the_parser]['isf'] > 1) return;

		// Evaluate and check for correct nesting of XML elements

		if (count($this->xh[$the_parser]['stack']) == 0)
		{
			if ($name != 'METHODRESPONSE' && $name != 'METHODCALL')
			{
				$this->xh[$the_parser]['isf'] = 2;
				$this->xh[$the_parser]['isf_reason'] = 'Top level XML-RPC element is missing';
				return;
			}
		}
		else
		{
			// not top level element: see if parent is OK
			if ( ! in_array($this->xh[$the_parser]['stack'][0], $this->valid_parents[$name], TRUE))
			{
				$this->xh[$the_parser]['isf'] = 2;
				$this->xh[$the_parser]['isf_reason'] = "XML-RPC element $name cannot be child of ".$this->xh[$the_parser]['stack'][0];
				return;
			}
		}

		switch($name)
		{
			case 'STRUCT':
			case 'ARRAY':
				// Creates array for child elements

				$cur_val = array('value' => array(),
								 'type'	 => $name);

				array_unshift($this->xh[$the_parser]['valuestack'], $cur_val);
			break;
			case 'METHODNAME':
			case 'NAME':
				$this->xh[$the_parser]['ac'] = '';
			break;
			case 'FAULT':
				$this->xh[$the_parser]['isf'] = 1;
			break;
			case 'PARAM':
				$this->xh[$the_parser]['value'] = NULL;
			break;
			case 'VALUE':
				$this->xh[$the_parser]['vt'] = 'value';
				$this->xh[$the_parser]['ac'] = '';
				$this->xh[$the_parser]['lv'] = 1;
			break;
			case 'I4':
			case 'INT':
			case 'STRING':
			case 'BOOLEAN':
			case 'DOUBLE':
			case 'DATETIME.ISO8601':
			case 'BASE64':
				if ($this->xh[$the_parser]['vt'] != 'value')
				{
					//two data elements inside a value: an error occurred!
					$this->xh[$the_parser]['isf'] = 2;
					$this->xh[$the_parser]['isf_reason'] = "'Twas a $name element following a ".$this->xh[$the_parser]['vt']." element inside a single value";
					return;
				}

				$this->xh[$the_parser]['ac'] = '';
			break;
			case 'MEMBER':
				// Set name of <member> to nothing to prevent errors later if no <name> is found
				$this->xh[$the_parser]['valuestack'][0]['name'] = '';

				// Set NULL value to check to see if value passed for this param/member
				$this->xh[$the_parser]['value'] = NULL;
			break;
			case 'DATA':
			case 'METHODCALL':
			case 'METHODRESPONSE':
			case 'PARAMS':
				// valid elements that add little to processing
			break;
			default:
				/// An Invalid Element is Found, so we have trouble
				$this->xh[$the_parser]['isf'] = 2;
				$this->xh[$the_parser]['isf_reason'] = "Invalid XML-RPC element found: $name";
			break;
		}

		// Add current element name to stack, to allow validation of nesting
		array_unshift($this->xh[$the_parser]['stack'], $name);

		if ($name != 'VALUE') $this->xh[$the_parser]['lv'] = 0;
	}
	// END


	//-------------------------------------
	//  End Element Handler
	//-------------------------------------

	function closing_tag($the_parser, $name)
	{
		if ($this->xh[$the_parser]['isf'] > 1) return;

		// Remove current element from stack and set variable
		// NOTE: If the XML validates, then we do not have to worry about
		// the opening and closing of elements.  Nesting is checked on the opening
		// tag so we be safe there as well.

		$curr_elem = array_shift($this->xh[$the_parser]['stack']);

		switch($name)
		{
			case 'STRUCT':
			case 'ARRAY':
				$cur_val = array_shift($this->xh[$the_parser]['valuestack']);
				$this->xh[$the_parser]['value'] = ( ! isset($cur_val['values'])) ? array() : $cur_val['values'];
				$this->xh[$the_parser]['vt']	= strtolower($name);
			break;
			case 'NAME':
				$this->xh[$the_parser]['valuestack'][0]['name'] = $this->xh[$the_parser]['ac'];
			break;
			case 'BOOLEAN':
			case 'I4':
			case 'INT':
			case 'STRING':
			case 'DOUBLE':
			case 'DATETIME.ISO8601':
			case 'BASE64':
				$this->xh[$the_parser]['vt'] = strtolower($name);

				if ($name == 'STRING')
				{
					$this->xh[$the_parser]['value'] = $this->xh[$the_parser]['ac'];
				}
				elseif ($name=='DATETIME.ISO8601')
				{
					$this->xh[$the_parser]['vt']	= $this->xmlrpcDateTime;
					$this->xh[$the_parser]['value'] = $this->xh[$the_parser]['ac'];
				}
				elseif ($name=='BASE64')
				{
					$this->xh[$the_parser]['value'] = base64_decode($this->xh[$the_parser]['ac']);
				}
				elseif ($name=='BOOLEAN')
				{
					// Translated BOOLEAN values to TRUE AND FALSE
					if ($this->xh[$the_parser]['ac'] == '1')
					{
						$this->xh[$the_parser]['value'] = TRUE;
					}
					else
					{
						$this->xh[$the_parser]['value'] = FALSE;
					}
				}
				elseif ($name=='DOUBLE')
				{
					// we have a DOUBLE
					// we must check that only 0123456789-.<space> are characters here
					if ( ! preg_match('/^[+-]?[eE0-9\t \.]+$/', $this->xh[$the_parser]['ac']))
					{
						$this->xh[$the_parser]['value'] = 'ERROR_NON_NUMERIC_FOUND';
					}
					else
					{
						$this->xh[$the_parser]['value'] = (double)$this->xh[$the_parser]['ac'];
					}
				}
				else
				{
					// we have an I4/INT
					// we must check that only 0123456789-<space> are characters here
					if ( ! preg_match('/^[+-]?[0-9\t ]+$/', $this->xh[$the_parser]['ac']))
					{
						$this->xh[$the_parser]['value'] = 'ERROR_NON_NUMERIC_FOUND';
					}
					else
					{
						$this->xh[$the_parser]['value'] = (int)$this->xh[$the_parser]['ac'];
					}
				}
				$this->xh[$the_parser]['ac'] = '';
				$this->xh[$the_parser]['lv'] = 3; // indicate we've found a value
			break;
			case 'VALUE':
				// This if() detects if no scalar was inside <VALUE></VALUE>
				if ($this->xh[$the_parser]['vt']=='value')
				{
					$this->xh[$the_parser]['value']	= $this->xh[$the_parser]['ac'];
					$this->xh[$the_parser]['vt']	= $this->xmlrpcString;
				}

				// build the XML-RPC value out of the data received, and substitute it
				$temp = new XML_RPC_Values($this->xh[$the_parser]['value'], $this->xh[$the_parser]['vt']);

				if (count($this->xh[$the_parser]['valuestack']) && $this->xh[$the_parser]['valuestack'][0]['type'] == 'ARRAY')
				{
					// Array
					$this->xh[$the_parser]['valuestack'][0]['values'][] = $temp;
				}
				else
				{
					// Struct
					$this->xh[$the_parser]['value'] = $temp;
				}
			break;
			case 'MEMBER':
				$this->xh[$the_parser]['ac']='';

				// If value add to array in the stack for the last element built
				if ($this->xh[$the_parser]['value'])
				{
					$this->xh[$the_parser]['valuestack'][0]['values'][$this->xh[$the_parser]['valuestack'][0]['name']] = $this->xh[$the_parser]['value'];
				}
			break;
			case 'DATA':
				$this->xh[$the_parser]['ac']='';
			break;
			case 'PARAM':
				if ($this->xh[$the_parser]['value'])
				{
					$this->xh[$the_parser]['params'][] = $this->xh[$the_parser]['value'];
				}
			break;
			case 'METHODNAME':
				$this->xh[$the_parser]['method'] = ltrim($this->xh[$the_parser]['ac']);
			break;
			case 'PARAMS':
			case 'FAULT':
			case 'METHODCALL':
			case 'METHORESPONSE':
				// We're all good kids with nuthin' to do
			break;
			default:
				// End of an Invalid Element.  Taken care of during the opening tag though
			break;
		}
	}

	//-------------------------------------
	//  Parses Character Data
	//-------------------------------------

	function character_data($the_parser, $data)
	{
		if ($this->xh[$the_parser]['isf'] > 1) return; // XML Fault found already

		// If a value has not been found
		if ($this->xh[$the_parser]['lv'] != 3)
		{
			if ($this->xh[$the_parser]['lv'] == 1)
			{
				$this->xh[$the_parser]['lv'] = 2; // Found a value
			}

			if ( ! @isset($this->xh[$the_parser]['ac']))
			{
				$this->xh[$the_parser]['ac'] = '';
			}

			$this->xh[$the_parser]['ac'] .= $data;
		}
	}


	function addParam($par) { $this->params[]=$par; }

	function output_parameters($array=FALSE)
	{
		$CI =& get_instance();
		
		if ($array !== FALSE && is_array($array))
		{
			while (list($key) = each($array))
			{
				if (is_array($array[$key]))
				{
					$array[$key] = $this->output_parameters($array[$key]);
				}
				else
				{
					// 'bits' is for the MetaWeblog API image bits
					// @todo - this needs to be made more general purpose
					$array[$key] = ($key == 'bits' OR $this->xss_clean == FALSE) ? $array[$key] : $CI->security->xss_clean($array[$key]);
				}
			}

			$parameters = $array;
		}
		else
		{
			$parameters = array();

			for ($i = 0; $i < count($this->params); $i++)
			{
				$a_param = $this->decode_message($this->params[$i]);

				if (is_array($a_param))
				{
					$parameters[] = $this->output_parameters($a_param);
				}
				else
				{
					$parameters[] = ($this->xss_clean) ? $CI->security->xss_clean($a_param) : $a_param;
				}
			}
		}

		return $parameters;
	}


	function decode_message($param)
	{
		$kind = $param->kindOf();

		if ($kind == 'scalar')
		{
			return $param->scalarval();
		}
		elseif ($kind == 'array')
		{
			reset($param->me);
			list($a,$b) = each($param->me);

			$arr = array();

			for($i = 0; $i < count($b); $i++)
			{
				$arr[] = $this->decode_message($param->me['array'][$i]);
			}

			return $arr;
		}
		elseif ($kind == 'struct')
		{
			reset($param->me['struct']);

			$arr = array();

			while (list($key,$value) = each($param->me['struct']))
			{
				$arr[$key] = $this->decode_message($value);
			}

			return $arr;
		}
	}

} // End XML_RPC_Messages class



/**
 * XML-RPC Values class
 *
 * @category	XML-RPC
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/xmlrpc.html
 */
class XML_RPC_Values extends CI_Xmlrpc
{
	var $me		= array();
	var $mytype	= 0;

	public function __construct($val=-1, $type='')
	{
		parent::__construct();

		if ($val != -1 OR $type != '')
		{
			$type = $type == '' ? 'string' : $type;

			if ($this->xmlrpcTypes[$type] == 1)
			{
				$this->addScalar($val,$type);
			}
			elseif ($this->xmlrpcTypes[$type] == 2)
			{
				$this->addArray($val);
			}
			elseif ($this->xmlrpcTypes[$type] == 3)
			{
				$this->addStruct($val);
			}
		}
	}

	function addScalar($val, $type='string')
	{
		$typeof = $this->xmlrpcTypes[$type];

		if ($this->mytype==1)
		{
			echo '<strong>XML_RPC_Values</strong>: scalar can have only one value<br />';
			return 0;
		}

		if ($typeof != 1)
		{
			echo '<strong>XML_RPC_Values</strong>: not a scalar type (${typeof})<br />';
			return 0;
		}

		if ($type == $this->xmlrpcBoolean)
		{
			if (strcasecmp($val,'true')==0 OR $val==1 OR ($val==true && strcasecmp($val,'false')))
			{
				$val = 1;
			}
			else
			{
				$val=0;
			}
		}

		if ($this->mytype == 2)
		{
			// adding to an array here
			$ar = $this->me['array'];
			$ar[] = new XML_RPC_Values($val, $type);
			$this->me['array'] = $ar;
		}
		else
		{
			// a scalar, so set the value and remember we're scalar
			$this->me[$type] = $val;
			$this->mytype = $typeof;
		}
		return 1;
	}

	function addArray($vals)
	{
		if ($this->mytype != 0)
		{
			echo '<strong>XML_RPC_Values</strong>: already initialized as a [' . $this->kindOf() . ']<br />';
			return 0;
		}

		$this->mytype = $this->xmlrpcTypes['array'];
		$this->me['array'] = $vals;
		return 1;
	}

	function addStruct($vals)
	{
		if ($this->mytype != 0)
		{
			echo '<strong>XML_RPC_Values</strong>: already initialized as a [' . $this->kindOf() . ']<br />';
			return 0;
		}
		$this->mytype = $this->xmlrpcTypes['struct'];
		$this->me['struct'] = $vals;
		return 1;
	}

	function kindOf()
	{
		switch($this->mytype)
		{
			case 3:
				return 'struct';
				break;
			case 2:
				return 'array';
				break;
			case 1:
				return 'scalar';
				break;
			default:
				return 'undef';
		}
	}

	function serializedata($typ, $val)
	{
		$rs = '';

		switch($this->xmlrpcTypes[$typ])
		{
			case 3:
				// struct
				$rs .= "<struct>\n";
				reset($val);
				while (list($key2, $val2) = each($val))
				{
					$rs .= "<member>\n<name>{$key2}</name>\n";
					$rs .= $this->serializeval($val2);
					$rs .= "</member>\n";
				}
				$rs .= '</struct>';
			break;
			case 2:
				// array
				$rs .= "<array>\n<data>\n";
				for($i=0; $i < count($val); $i++)
				{
					$rs .= $this->serializeval($val[$i]);
				}
				$rs.="</data>\n</array>\n";
				break;
			case 1:
				// others
				switch ($typ)
				{
					case $this->xmlrpcBase64:
						$rs .= "<{$typ}>" . base64_encode((string)$val) . "</{$typ}>\n";
					break;
					case $this->xmlrpcBoolean:
						$rs .= "<{$typ}>" . ((bool)$val ? '1' : '0') . "</{$typ}>\n";
					break;
					case $this->xmlrpcString:
						$rs .= "<{$typ}>" . htmlspecialchars((string)$val). "</{$typ}>\n";
					break;
					default:
						$rs .= "<{$typ}>{$val}</{$typ}>\n";
					break;
				}
			default:
			break;
		}
		return $rs;
	}

	function serialize_class()
	{
		return $this->serializeval($this);
	}

	function serializeval($o)
	{
		$ar = $o->me;
		reset($ar);

		list($typ, $val) = each($ar);
		$rs = "<value>\n".$this->serializedata($typ, $val)."</value>\n";
		return $rs;
	}

	function scalarval()
	{
		reset($this->me);
		list($a,$b) = each($this->me);
		return $b;
	}


	//-------------------------------------
	// Encode time in ISO-8601 form.
	//-------------------------------------

	// Useful for sending time in XML-RPC

	function iso8601_encode($time, $utc=0)
	{
		if ($utc == 1)
		{
			$t = strftime("%Y%m%dT%H:%i:%s", $time);
		}
		else
		{
			if (function_exists('gmstrftime'))
				$t = gmstrftime("%Y%m%dT%H:%i:%s", $time);
			else
				$t = strftime("%Y%m%dT%H:%i:%s", $time - date('Z'));
		}
		return $t;
	}

}
// END XML_RPC_Values Class

/* End of file Xmlrpc.php */
/* Location: ./system/libraries/Xmlrpc.php */

class xmlrpc_client
	{
		var $path;
		var $server;
		var $port=0;
		var $method='http';
		var $errno;
		var $errstr;
		var $debug=0;
		var $username='';
		var $password='';
		var $authtype=1;
		var $cert='';
		var $certpass='';
		var $cacert='';
		var $cacertdir='';
		var $key='';
		var $keypass='';
		var $verifypeer=true;
		var $verifyhost=1;
		var $no_multicall=false;
		var $proxy='';
		var $proxyport=0;
		var $proxy_user='';
		var $proxy_pass='';
		var $proxy_authtype=1;
		var $cookies=array();
		/**
		* List of http compression methods accepted by the client for responses.
		* NB: PHP supports deflate, gzip compressions out of the box if compiled w. zlib
		*
		* NNB: you can set it to any non-empty array for HTTP11 and HTTPS, since
		* in those cases it will be up to CURL to decide the compression methods
		* it supports. You might check for the presence of 'zlib' in the output of
		* curl_version() to determine wheter compression is supported or not
		*/
		var $accepted_compression = array();
		/**
		* Name of compression scheme to be used for sending requests.
		* Either null, gzip or deflate
		*/
		var $request_compression = '';
		/**
		* CURL handle: used for keep-alive connections (PHP 4.3.8 up, see:
		* http://curl.haxx.se/docs/faq.html#7.3)
		*/
		var $xmlrpc_curl_handle = null;
		/// Wheter to use persistent connections for http 1.1 and https
		var $keepalive = false;
		/// Charset encodings that can be decoded without problems by the client
		var $accepted_charset_encodings = array();
		/// Charset encoding to be used in serializing request. NULL = use ASCII
		var $request_charset_encoding = '';
		/**
		* Decides the content of xmlrpcresp objects returned by calls to send()
		* valid strings are 'xmlrpcvals', 'phpvals' or 'xml'
		*/
		var $return_type = 'xmlrpcvals';

		/**
		* @param string $path either the complete server URL or the PATH part of the xmlrc server URL, e.g. /xmlrpc/server.php
		* @param string $server the server name / ip address
		* @param integer $port the port the server is listening on, defaults to 80 or 443 depending on protocol used
		* @param string $method the http protocol variant: defaults to 'http', 'https' and 'http11' can be used if CURL is installed
		*/
		function xmlrpc_client($path, $server='', $port='', $method='')
		{
			// allow user to specify all params in $path
			if($server == '' and $port == '' and $method == '')
			{
				$parts = parse_url($path);
				$server = $parts['host'];
				$path = isset($parts['path']) ? $parts['path'] : '';
				if(isset($parts['query']))
				{
					$path .= '?'.$parts['query'];
				}
				if(isset($parts['fragment']))
				{
					$path .= '#'.$parts['fragment'];
				}
				if(isset($parts['port']))
				{
					$port = $parts['port'];
				}
				if(isset($parts['scheme']))
				{
					$method = $parts['scheme'];
				}
				if(isset($parts['user']))
				{
					$this->username = $parts['user'];
				}
				if(isset($parts['pass']))
				{
					$this->password = $parts['pass'];
				}
			}
			if($path == '' || $path[0] != '/')
			{
				$this->path='/'.$path;
			}
			else
			{
				$this->path=$path;
			}
			$this->server=$server;
			if($port != '')
			{
				$this->port=$port;
			}
			if($method != '')
			{
				$this->method=$method;
			}

			// if ZLIB is enabled, let the client by default accept compressed responses
			if(function_exists('gzinflate') || (
				function_exists('curl_init') && (($info = curl_version()) &&
				((is_string($info) && strpos($info, 'zlib') !== null) || isset($info['libz_version'])))
			))
			{
				$this->accepted_compression = array('gzip', 'deflate');
			}

			// keepalives: enabled by default ONLY for PHP >= 4.3.8
			// (see http://curl.haxx.se/docs/faq.html#7.3)
			if(version_compare(phpversion(), '4.3.8') >= 0)
			{
				$this->keepalive = true;
			}

			// by default the xml parser can support these 3 charset encodings
			$this->accepted_charset_encodings = array('UTF-8', 'ISO-8859-1', 'US-ASCII');
		}

		/**
		* Enables/disables the echoing to screen of the xmlrpc responses received
		* @param integer $debug values 0, 1 and 2 are supported (2 = echo sent msg too, before received response)
		* @access public
		*/
		function setDebug($in)
		{
			$this->debug=$in;
		}

		/**
		* Add some http BASIC AUTH credentials, used by the client to authenticate
		* @param string $u username
		* @param string $p password
		* @param integer $t auth type. See curl_setopt man page for supported auth types. Defaults to CURLAUTH_BASIC (basic auth)
		* @access public
		*/
		function setCredentials($u, $p, $t=1)
		{
			$this->username=$u;
			$this->password=$p;
			$this->authtype=$t;
		}

		/**
		* Add a client-side https certificate
		* @param string $cert
		* @param string $certpass
		* @access public
		*/
		function setCertificate($cert, $certpass)
		{
			$this->cert = $cert;
			$this->certpass = $certpass;
		}

		/**
		* Add a CA certificate to verify server with (see man page about
		* CURLOPT_CAINFO for more details
		* @param string $cacert certificate file name (or dir holding certificates)
		* @param bool $is_dir set to true to indicate cacert is a dir. defaults to false
		* @access public
		*/
		function setCaCertificate($cacert, $is_dir=false)
		{
			if ($is_dir)
			{
				$this->cacertdir = $cacert;
			}
			else
			{
				$this->cacert = $cacert;
			}
		}

		/**
		* Set attributes for SSL communication: private SSL key
		* NB: does not work in older php/curl installs
		* Thanks to Daniel Convissor
		* @param string $key The name of a file containing a private SSL key
		* @param string $keypass The secret password needed to use the private SSL key
		* @access public
		*/
		function setKey($key, $keypass)
		{
			$this->key = $key;
			$this->keypass = $keypass;
		}

		/**
		* Set attributes for SSL communication: verify server certificate
		* @param bool $i enable/disable verification of peer certificate
		* @access public
		*/
		function setSSLVerifyPeer($i)
		{
			$this->verifypeer = $i;
		}

		/**
		* Set attributes for SSL communication: verify match of server cert w. hostname
		* @param int $i
		* @access public
		*/
		function setSSLVerifyHost($i)
		{
			$this->verifyhost = $i;
		}

		/**
		* Set proxy info
		* @param string $proxyhost
		* @param string $proxyport Defaults to 8080 for HTTP and 443 for HTTPS
		* @param string $proxyusername Leave blank if proxy has public access
		* @param string $proxypassword Leave blank if proxy has public access
		* @param int $proxyauthtype set to constant CURLAUTH_NTLM to use NTLM auth with proxy
		* @access public
		*/
		function setProxy($proxyhost, $proxyport, $proxyusername = '', $proxypassword = '', $proxyauthtype = 1)
		{
			$this->proxy = $proxyhost;
			$this->proxyport = $proxyport;
			$this->proxy_user = $proxyusername;
			$this->proxy_pass = $proxypassword;
			$this->proxy_authtype = $proxyauthtype;
		}

		/**
		* Enables/disables reception of compressed xmlrpc responses.
		* Note that enabling reception of compressed responses merely adds some standard
		* http headers to xmlrpc requests. It is up to the xmlrpc server to return
		* compressed responses when receiving such requests.
		* @param string $compmethod either 'gzip', 'deflate', 'any' or ''
		* @access public
		*/
		function setAcceptedCompression($compmethod)
		{
			if ($compmethod == 'any')
				$this->accepted_compression = array('gzip', 'deflate');
			else
				$this->accepted_compression = array($compmethod);
		}

		/**
		* Enables/disables http compression of xmlrpc request.
		* Take care when sending compressed requests: servers might not support them
		* (and automatic fallback to uncompressed requests is not yet implemented)
		* @param string $compmethod either 'gzip', 'deflate' or ''
		* @access public
		*/
		function setRequestCompression($compmethod)
		{
			$this->request_compression = $compmethod;
		}

		/**
		* Adds a cookie to list of cookies that will be sent to server.
		* NB: setting any param but name and value will turn the cookie into a 'version 1' cookie:
		* do not do it unless you know what you are doing
		* @param string $name
		* @param string $value
		* @param string $path
		* @param string $domain
		* @param int $port
		* @access public
		*
		* @todo check correctness of urlencoding cookie value (copied from php way of doing it...)
		*/
		function setCookie($name, $value='', $path='', $domain='', $port=null)
		{
			$this->cookies[$name]['value'] = urlencode($value);
			if ($path || $domain || $port)
			{
				$this->cookies[$name]['path'] = $path;
				$this->cookies[$name]['domain'] = $domain;
				$this->cookies[$name]['port'] = $port;
				$this->cookies[$name]['version'] = 1;
			}
			else
			{
				$this->cookies[$name]['version'] = 0;
			}
		}

		/**
		* Send an xmlrpc request
		* @param mixed $msg The message object, or an array of messages for using multicall, or the complete xml representation of a request
		* @param integer $timeout Connection timeout, in seconds, If unspecified, a platform specific timeout will apply
		* @param string $method if left unspecified, the http protocol chosen during creation of the object will be used
		* @return xmlrpcresp
		* @access public
		*/
		function& send($msg, $timeout=0, $method='')
		{
			// if user deos not specify http protocol, use native method of this client
			// (i.e. method set during call to constructor)
			if($method == '')
			{
				$method = $this->method;
			}

			if(is_array($msg))
			{
				// $msg is an array of xmlrpcmsg's
				$r = $this->multicall($msg, $timeout, $method);
				return $r;
			}
			elseif(is_string($msg))
			{
				$n =& new xmlrpcmsg('');
				$n->payload = $msg;
				$msg = $n;
			}

			// where msg is an xmlrpcmsg
			$msg->debug=$this->debug;

			if($method == 'https')
			{
				$r =& $this->sendPayloadHTTPS(
					$msg,
					$this->server,
					$this->port,
					$timeout,
					$this->username,
					$this->password,
					$this->authtype,
					$this->cert,
					$this->certpass,
					$this->cacert,
					$this->cacertdir,
					$this->proxy,
					$this->proxyport,
					$this->proxy_user,
					$this->proxy_pass,
					$this->proxy_authtype,
					$this->keepalive,
					$this->key,
					$this->keypass
				);
			}
			elseif($method == 'http11')
			{
				$r =& $this->sendPayloadCURL(
					$msg,
					$this->server,
					$this->port,
					$timeout,
					$this->username,
					$this->password,
					$this->authtype,
					null,
					null,
					null,
					null,
					$this->proxy,
					$this->proxyport,
					$this->proxy_user,
					$this->proxy_pass,
					$this->proxy_authtype,
					'http',
					$this->keepalive
				);
			}
			else
			{
				$r =& $this->sendPayloadHTTP10(
					$msg,
					$this->server,
					$this->port,
					$timeout,
					$this->username,
					$this->password,
					$this->authtype,
					$this->proxy,
					$this->proxyport,
					$this->proxy_user,
					$this->proxy_pass,
					$this->proxy_authtype
				);
			}

			return $r;
		}

		/**
		* @access private
		*/
		function &sendPayloadHTTP10($msg, $server, $port, $timeout=0,
			$username='', $password='', $authtype=1, $proxyhost='',
			$proxyport=0, $proxyusername='', $proxypassword='', $proxyauthtype=1)
		{
			if($port==0)
			{
				$port=80;
			}

			// Only create the payload if it was not created previously
			if(empty($msg->payload))
			{
				$msg->createPayload($this->request_charset_encoding);
			}

			$payload = $msg->payload;
			// Deflate request body and set appropriate request headers
			if(function_exists('gzdeflate') && ($this->request_compression == 'gzip' || $this->request_compression == 'deflate'))
			{
				if($this->request_compression == 'gzip')
				{
					$a = @gzencode($payload);
					if($a)
					{
						$payload = $a;
						$encoding_hdr = "Content-Encoding: gzip\r\n";
					}
				}
				else
				{
					$a = @gzcompress($payload);
					if($a)
					{
						$payload = $a;
						$encoding_hdr = "Content-Encoding: deflate\r\n";
					}
				}
			}
			else
			{
				$encoding_hdr = '';
			}

			// thanks to Grant Rauscher <grant7@firstworld.net> for this
			$credentials='';
			if($username!='')
			{
				$credentials='Authorization: Basic ' . base64_encode($username . ':' . $password) . "\r\n";
				if ($authtype != 1)
				{
					error_log('XML-RPC: xmlrpc_client::send: warning. Only Basic auth is supported with HTTP 1.0');
				}
			}

			$accepted_encoding = '';
			if(is_array($this->accepted_compression) && count($this->accepted_compression))
			{
				$accepted_encoding = 'Accept-Encoding: ' . implode(', ', $this->accepted_compression) . "\r\n";
			}

			$proxy_credentials = '';
			if($proxyhost)
			{
				if($proxyport == 0)
				{
					$proxyport = 8080;
				}
				$connectserver = $proxyhost;
				$connectport = $proxyport;
				$uri = 'http://'.$server.':'.$port.$this->path;
				if($proxyusername != '')
				{
					if ($proxyauthtype != 1)
					{
						error_log('XML-RPC: xmlrpc_client::send: warning. Only Basic auth to proxy is supported with HTTP 1.0');
					}
					$proxy_credentials = 'Proxy-Authorization: Basic ' . base64_encode($proxyusername.':'.$proxypassword) . "\r\n";
				}
			}
			else
			{
				$connectserver = $server;
				$connectport = $port;
				$uri = $this->path;
			}

			// Cookie generation, as per rfc2965 (version 1 cookies) or
			// netscape's rules (version 0 cookies)
			$cookieheader='';
			if (count($this->cookies))
			{
				$version = '';
				foreach ($this->cookies as $name => $cookie)
				{
					if ($cookie['version'])
					{
						$version = ' $Version="' . $cookie['version'] . '";';
						$cookieheader .= ' ' . $name . '="' . $cookie['value'] . '";';
						if ($cookie['path'])
							$cookieheader .= ' $Path="' . $cookie['path'] . '";';
						if ($cookie['domain'])
							$cookieheader .= ' $Domain="' . $cookie['domain'] . '";';
						if ($cookie['port'])
							$cookieheader .= ' $Port="' . $cookie['port'] . '";';
					}
					else
					{
						$cookieheader .= ' ' . $name . '=' . $cookie['value'] . ";";
					}
				}
				$cookieheader = 'Cookie:' . $version . substr($cookieheader, 0, -1) . "\r\n";
			}

			$op= 'POST ' . $uri. " HTTP/1.0\r\n" .
				'User-Agent: ' . $GLOBALS['xmlrpcName'] . ' ' . $GLOBALS['xmlrpcVersion'] . "\r\n" .
				'Host: '. $server . ':' . $port . "\r\n" .
				$credentials .
				$proxy_credentials .
				$accepted_encoding .
				$encoding_hdr .
				'Accept-Charset: ' . implode(',', $this->accepted_charset_encodings) . "\r\n" .
				$cookieheader .
				'Content-Type: ' . $msg->content_type . "\r\nContent-Length: " .
				strlen($payload) . "\r\n\r\n" .
				$payload;

			if($this->debug > 1)
			{
				print "<PRE>\n---SENDING---\n" . htmlentities($op) . "\n---END---\n</PRE>";
				// let the client see this now in case http times out...
				flush();
			}

			if($timeout>0)
			{
				$fp=@fsockopen($connectserver, $connectport, $this->errno, $this->errstr, $timeout);
			}
			else
			{
				$fp=@fsockopen($connectserver, $connectport, $this->errno, $this->errstr);
			}
			if($fp)
			{
				if($timeout>0 && function_exists('stream_set_timeout'))
				{
					stream_set_timeout($fp, $timeout);
				}
			}
			else
			{
				$this->errstr='Connect error: '.$this->errstr;
				$r=&new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['http_error'], $this->errstr . ' (' . $this->errno . ')');
				return $r;
			}

			if(!fputs($fp, $op, strlen($op)))
			{
    			fclose($fp);
				$this->errstr='Write error';
				$r=&new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['http_error'], $this->errstr);
				return $r;
			}
			else
			{
				// reset errno and errstr on succesful socket connection
				$this->errstr = '';
			}
			// G. Giunta 2005/10/24: close socket before parsing.
			// should yeld slightly better execution times, and make easier recursive calls (e.g. to follow http redirects)
			$ipd='';
			do
			{
				// shall we check for $data === FALSE?
				// as per the manual, it signals an error
				$ipd.=fread($fp, 32768);
			} while(!feof($fp));
			fclose($fp);
			$r =& $msg->parseResponse($ipd, false, $this->return_type);
			return $r;

		}

		/**
		* @access private
		*/
		function &sendPayloadHTTPS($msg, $server, $port, $timeout=0, $username='',
			$password='', $authtype=1, $cert='',$certpass='', $cacert='', $cacertdir='',
			$proxyhost='', $proxyport=0, $proxyusername='', $proxypassword='', $proxyauthtype=1,
			$keepalive=false, $key='', $keypass='')
		{
			$r =& $this->sendPayloadCURL($msg, $server, $port, $timeout, $username,
				$password, $authtype, $cert, $certpass, $cacert, $cacertdir, $proxyhost, $proxyport,
				$proxyusername, $proxypassword, $proxyauthtype, 'https', $keepalive, $key, $keypass);
			return $r;
		}

		/**
		* Contributed by Justin Miller <justin@voxel.net>
		* Requires curl to be built into PHP
		* NB: CURL versions before 7.11.10 cannot use proxy to talk to https servers!
		* @access private
		*/
		function &sendPayloadCURL($msg, $server, $port, $timeout=0, $username='',
			$password='', $authtype=1, $cert='', $certpass='', $cacert='', $cacertdir='',
			$proxyhost='', $proxyport=0, $proxyusername='', $proxypassword='', $proxyauthtype=1, $method='https',
			$keepalive=false, $key='', $keypass='')
		{
			if(!function_exists('curl_init'))
			{
				$this->errstr='CURL unavailable on this install';
				$r=&new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['no_curl'], $GLOBALS['xmlrpcstr']['no_curl']);
				return $r;
			}
			if($method == 'https')
			{
				if(($info = curl_version()) &&
					((is_string($info) && strpos($info, 'OpenSSL') === null) || (is_array($info) && !isset($info['ssl_version']))))
				{
					$this->errstr='SSL unavailable on this install';
					$r=&new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['no_ssl'], $GLOBALS['xmlrpcstr']['no_ssl']);
					return $r;
				}
			}

			if($port == 0)
			{
				if($method == 'http')
				{
					$port = 80;
				}
				else
				{
					$port = 443;
				}
			}

			// Only create the payload if it was not created previously
			if(empty($msg->payload))
			{
				$msg->createPayload($this->request_charset_encoding);
			}

			// Deflate request body and set appropriate request headers
			$payload = $msg->payload;
			if(function_exists('gzdeflate') && ($this->request_compression == 'gzip' || $this->request_compression == 'deflate'))
			{
				if($this->request_compression == 'gzip')
				{
					$a = @gzencode($payload);
					if($a)
					{
						$payload = $a;
						$encoding_hdr = 'Content-Encoding: gzip';
					}
				}
				else
				{
					$a = @gzcompress($payload);
					if($a)
					{
						$payload = $a;
						$encoding_hdr = 'Content-Encoding: deflate';
					}
				}
			}
			else
			{
				$encoding_hdr = '';
			}

			if($this->debug > 1)
			{
				print "<PRE>\n---SENDING---\n" . htmlentities($payload) . "\n---END---\n</PRE>";
				// let the client see this now in case http times out...
				flush();
			}

			if(!$keepalive || !$this->xmlrpc_curl_handle)
			{
				$curl = curl_init($method . '://' . $server . ':' . $port . $this->path);
				if($keepalive)
				{
					$this->xmlrpc_curl_handle = $curl;
				}
			}
			else
			{
				$curl = $this->xmlrpc_curl_handle;
			}

			// results into variable
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

			if($this->debug)
			{
				curl_setopt($curl, CURLOPT_VERBOSE, 1);
			}
			curl_setopt($curl, CURLOPT_USERAGENT, $GLOBALS['xmlrpcName'].' '.$GLOBALS['xmlrpcVersion']);
			// required for XMLRPC: post the data
			curl_setopt($curl, CURLOPT_POST, 1);
			// the data
			curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

			// return the header too
			curl_setopt($curl, CURLOPT_HEADER, 1);

			// will only work with PHP >= 5.0
			// NB: if we set an empty string, CURL will add http header indicating
			// ALL methods it is supporting. This is possibly a better option than
			// letting the user tell what curl can / cannot do...
			if(is_array($this->accepted_compression) && count($this->accepted_compression))
			{
				//curl_setopt($curl, CURLOPT_ENCODING, implode(',', $this->accepted_compression));
				// empty string means 'any supported by CURL' (shall we catch errors in case CURLOPT_SSLKEY undefined ?)
				if (count($this->accepted_compression) == 1)
				{
					curl_setopt($curl, CURLOPT_ENCODING, $this->accepted_compression[0]);
				}
				else
					curl_setopt($curl, CURLOPT_ENCODING, '');
			}
			// extra headers
			$headers = array('Content-Type: ' . $msg->content_type , 'Accept-Charset: ' . implode(',', $this->accepted_charset_encodings));
			// if no keepalive is wanted, let the server know it in advance
			if(!$keepalive)
			{
				$headers[] = 'Connection: close';
			}
			// request compression header
			if($encoding_hdr)
			{
				$headers[] = $encoding_hdr;
			}

			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			// timeout is borked
			if($timeout)
			{
				curl_setopt($curl, CURLOPT_TIMEOUT, $timeout == 1 ? 1 : $timeout - 1);
			}

			if($username && $password)
			{
				curl_setopt($curl, CURLOPT_USERPWD, $username.':'.$password);
				if (defined('CURLOPT_HTTPAUTH'))
				{
					curl_setopt($curl, CURLOPT_HTTPAUTH, $authtype);
				}
				else if ($authtype != 1)
				{
					error_log('XML-RPC: xmlrpc_client::send: warning. Only Basic auth is supported by the current PHP/curl install');
				}
			}

			if($method == 'https')
			{
				// set cert file
				if($cert)
				{
					curl_setopt($curl, CURLOPT_SSLCERT, $cert);
				}
				// set cert password
				if($certpass)
				{
					curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $certpass);
				}
				// whether to verify remote host's cert
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->verifypeer);
				// set ca certificates file/dir
				if($cacert)
				{
					curl_setopt($curl, CURLOPT_CAINFO, $cacert);
				}
				if($cacertdir)
				{
					curl_setopt($curl, CURLOPT_CAPATH, $cacertdir);
				}
				// set key file (shall we catch errors in case CURLOPT_SSLKEY undefined ?)
				if($key)
				{
					curl_setopt($curl, CURLOPT_SSLKEY, $key);
				}
				// set key password (shall we catch errors in case CURLOPT_SSLKEY undefined ?)
				if($keypass)
				{
					curl_setopt($curl, CURLOPT_SSLKEYPASSWD, $keypass);
				}
				// whether to verify cert's common name (CN); 0 for no, 1 to verify that it exists, and 2 to verify that it matches the hostname used
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->verifyhost);
			}

			// proxy info
			if($proxyhost)
			{
				if($proxyport == 0)
				{
					$proxyport = 8080; // NB: even for HTTPS, local connection is on port 8080
				}
				curl_setopt($curl, CURLOPT_PROXY, $proxyhost.':'.$proxyport);
				//curl_setopt($curl, CURLOPT_PROXYPORT,$proxyport);
				if($proxyusername)
				{
					curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxyusername.':'.$proxypassword);
					if (defined('CURLOPT_PROXYAUTH'))
					{
						curl_setopt($curl, CURLOPT_PROXYAUTH, $proxyauthtype);
					}
					else if ($proxyauthtype != 1)
					{
						error_log('XML-RPC: xmlrpc_client::send: warning. Only Basic auth to proxy is supported by the current PHP/curl install');
					}
				}
			}

			// NB: should we build cookie http headers by hand rather than let CURL do it?
			// the following code does not honour 'expires', 'path' and 'domain' cookie attributes
			// set to client obj the the user...
			if (count($this->cookies))
			{
				$cookieheader = '';
				foreach ($this->cookies as $name => $cookie)
				{
					$cookieheader .= $name . '=' . $cookie['value'] . '; ';
				}
				curl_setopt($curl, CURLOPT_COOKIE, substr($cookieheader, 0, -2));
			}

			$result = curl_exec($curl);

			if ($this->debug > 1)
			{
				print "<PRE>\n---CURL INFO---\n";
				foreach(curl_getinfo($curl) as $name => $val)
					 print $name . ': ' . htmlentities($val). "\n";
				print "---END---\n</PRE>";
			}

			if(!$result) /// @todo we should use a better check here - what if we get back '' or '0'?
			{
				$this->errstr='no response';
				$resp=&new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['curl_fail'], $GLOBALS['xmlrpcstr']['curl_fail']. ': '. curl_error($curl));
				curl_close($curl);
				if($keepalive)
				{
					$this->xmlrpc_curl_handle = null;
				}
			}
			else
			{
				if(!$keepalive)
				{
					curl_close($curl);
				}
				$resp =& $msg->parseResponse($result, true, $this->return_type);
			}
			return $resp;
		}

		/**
		* Send an array of request messages and return an array of responses.
		* Unless $this->no_multicall has been set to true, it will try first
		* to use one single xmlrpc call to server method system.multicall, and
		* revert to sending many successive calls in case of failure.
		* This failure is also stored in $this->no_multicall for subsequent calls.
		* Unfortunately, there is no server error code universally used to denote
		* the fact that multicall is unsupported, so there is no way to reliably
		* distinguish between that and a temporary failure.
		* If you are sure that server supports multicall and do not want to
		* fallback to using many single calls, set the fourth parameter to FALSE.
		*
		* NB: trying to shoehorn extra functionality into existing syntax has resulted
		* in pretty much convoluted code...
		*
		* @param array $msgs an array of xmlrpcmsg objects
		* @param integer $timeout connection timeout (in seconds)
		* @param string $method the http protocol variant to be used
		* @param boolean fallback When true, upon receiveing an error during multicall, multiple single calls will be attempted
		* @return array
		* @access public
		*/
		function multicall($msgs, $timeout=0, $method='', $fallback=true)
		{
			if ($method == '')
			{
				$method = $this->method;
			}
			if(!$this->no_multicall)
			{
				$results = $this->_try_multicall($msgs, $timeout, $method);
				if(is_array($results))
				{
					// System.multicall succeeded
					return $results;
				}
				else
				{
					// either system.multicall is unsupported by server,
					// or call failed for some other reason.
					if ($fallback)
					{
						// Don't try it next time...
						$this->no_multicall = true;
					}
					else
					{
						if (is_a($results, 'xmlrpcresp'))
						{
							$result = $results;
						}
						else
						{
							$result =& new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['multicall_error'], $GLOBALS['xmlrpcstr']['multicall_error']);
						}
					}
				}
			}
			else
			{
				// override fallback, in case careless user tries to do two
				// opposite things at the same time
				$fallback = true;
			}

			$results = array();
			if ($fallback)
			{
				// system.multicall is (probably) unsupported by server:
				// emulate multicall via multiple requests
				foreach($msgs as $msg)
				{
					$results[] =& $this->send($msg, $timeout, $method);
				}
			}
			else
			{
				// user does NOT want to fallback on many single calls:
				// since we should always return an array of responses,
				// return an array with the same error repeated n times
				foreach($msgs as $msg)
				{
					$results[] = $result;
				}
			}
			return $results;
		}

		/**
		* Attempt to boxcar $msgs via system.multicall.
		* Returns either an array of xmlrpcreponses, an xmlrpc error response
		* or false (when received response does not respect valid multicall syntax)
		* @access private
		*/
		function _try_multicall($msgs, $timeout, $method)
		{
			// Construct multicall message
			$calls = array();
			foreach($msgs as $msg)
			{
				$call['methodName'] =& new xmlrpcval($msg->method(),'string');
				$numParams = $msg->getNumParams();
				$params = array();
				for($i = 0; $i < $numParams; $i++)
				{
					$params[$i] = $msg->getParam($i);
				}
				$call['params'] =& new xmlrpcval($params, 'array');
				$calls[] =& new xmlrpcval($call, 'struct');
			}
			$multicall =& new xmlrpcmsg('system.multicall');
			$multicall->addParam(new xmlrpcval($calls, 'array'));

			// Attempt RPC call
			$result =& $this->send($multicall, $timeout, $method);

			if($result->faultCode() != 0)
			{
				// call to system.multicall failed
				return $result;
			}

			// Unpack responses.
			$rets = $result->value();

			if ($this->return_type == 'xml')
			{
					return $rets;
			}
			else if ($this->return_type == 'phpvals')
			{
				///@todo test this code branch...
				$rets = $result->value();
				if(!is_array($rets))
				{
					return false;		// bad return type from system.multicall
				}
				$numRets = count($rets);
				if($numRets != count($msgs))
				{
					return false;		// wrong number of return values.
				}

				$response = array();
				for($i = 0; $i < $numRets; $i++)
				{
					$val = $rets[$i];
					if (!is_array($val)) {
						return false;
					}
					switch(count($val))
					{
						case 1:
							if(!isset($val[0]))
							{
								return false;		// Bad value
							}
							// Normal return value
							$response[$i] =& new xmlrpcresp($val[0], 0, '', 'phpvals');
							break;
						case 2:
							///	@todo remove usage of @: it is apparently quite slow
							$code = @$val['faultCode'];
							if(!is_int($code))
							{
								return false;
							}
							$str = @$val['faultString'];
							if(!is_string($str))
							{
								return false;
							}
							$response[$i] =& new xmlrpcresp(0, $code, $str);
							break;
						default:
							return false;
					}
				}
				return $response;
			}
			else // return type == 'xmlrpcvals'
			{
				$rets = $result->value();
				if($rets->kindOf() != 'array')
				{
					return false;		// bad return type from system.multicall
				}
				$numRets = $rets->arraysize();
				if($numRets != count($msgs))
				{
					return false;		// wrong number of return values.
				}

				$response = array();
				for($i = 0; $i < $numRets; $i++)
				{
					$val = $rets->arraymem($i);
					switch($val->kindOf())
					{
						case 'array':
							if($val->arraysize() != 1)
							{
								return false;		// Bad value
							}
							// Normal return value
							$response[$i] =& new xmlrpcresp($val->arraymem(0));
							break;
						case 'struct':
							$code = $val->structmem('faultCode');
							if($code->kindOf() != 'scalar' || $code->scalartyp() != 'int')
							{
								return false;
							}
							$str = $val->structmem('faultString');
							if($str->kindOf() != 'scalar' || $str->scalartyp() != 'string')
							{
								return false;
							}
							$response[$i] =& new xmlrpcresp(0, $code->scalarval(), $str->scalarval());
							break;
						default:
							return false;
					}
				}
				return $response;
			}
		}
	} // end class xmlrpc_client
