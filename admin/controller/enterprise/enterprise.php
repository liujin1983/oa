<?php 
class ControllerEnterpriseEnterprise extends Controller { 
	private $error = array();
   
  	public function index() {
  		
  		$this->template = 'enterprise/enterprise_index.tpl';
		$this->id = 'content';
		$this->layout = 'layout/oa';
		$this->render();
  	}
	
  	public function first()
  	{
  		$this->inner();
  	}
}
?>