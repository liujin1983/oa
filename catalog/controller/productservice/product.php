<?php

function getRegistry(){
	global $registry;
	
	return $registry;
}

class ControllerProductserviceProduct extends Controller {
	private $error = array();
	public function index() {
		$this->load->library('lib/nusoap');
		$father = $this;
		$server = new soap_server();
		$_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF']."?route=Productservice/product";
		//配置WSDL namespace；
		$server->configureWSDL('userWsdl','http://www.somelocation1.com',false,'rpc','http://schemas.xmlsoap.org/soap/http','http://www.somelocation2.com');
		//注册服务
		$server->register('productList',
		array('operation' => 'xsd:string','statement' => 'xsd:string'),
		array('return' => 'xsd:Array'),   //string :  xsd:string
		    'http://www.somelocation.com',  
		    'http://www.somelocation.com#feelbad',  
		    'rpc',  
		    'encoded',  
		    'print feel bad'  
		    );	
		
		$server->register('deleteProduct',
		array('product_ids' => 'xsd:Array'),
		array('return' => 'xsd:int'),   //string :  xsd:string
		    'http://www.somelocation.com',  
		    'http://www.somelocation.com#feelbad',  
		    'rpc',  
		    'encoded',  
		    'print feel bad'  
		    );	
		    
		    
		    
			
		    function productList($operation,$statement){
	    		$registry=getRegistry();
	 			$registry->get('load')->model('catalog/productservice');
				$product_info = $registry->get('model_catalog_productservice')->getProducts(null);
		    	return $product_info;
		    }
		    
		    function deleteProduct($product_ids){
		    	$registry=getRegistry();
	 			$registry->get('load')->model('catalog/productservice');
	 			
	 			foreach ($product_ids as $product_id)
	 			{
					$registry->get('model_catalog_productservice')->deleteProduct($product_id);
	 			}
	 			
		    	return 0;
		    }
		    
		    $HTTP_RAW_POST_DATA = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");

		    $server->service($HTTP_RAW_POST_DATA);

	}
}

?>