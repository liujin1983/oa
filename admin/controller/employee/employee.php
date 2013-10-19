<?php 
class ControllerEmployeeEmployee extends Controller { 
	private $error = array();
   
  	public function index() {
  		
  		//读取数据库中员工门户的菜单列表
  		
//  	$this->data['task_add'] = $this->url->link('task/task/add', 'token=' . $this->session->data['token'], 'SSL');
  		
  		
  		//此处可以读取数据库操作,类似如下
//  		$data = array();
//  		$this->load->model('task/task');
//  		$results = $this->model_task_task->getTasks($data);
//  		$this->data['tasks'] = $results;

  		$this->data['email_inner'] = $this->url->link('employee/employee/inner', 'token=' . $this->session->data['token'], 'SSL');
  		$this->data['email_outer'] = $this->url->link('employee/employee/outer', 'token=' . $this->session->data['token'], 'SSL');
  		
  		$this->template = 'employee/employee_index.tpl';
		$this->id = 'content';
		$this->layout = 'layout/oa';
		$this->render();
  	}
	
  	public function first()
  	{
  		$this->inner();
  	}
  	public function inner()
  	{
  		$this->template = 'employee/employee_inner.tpl';
		$this->id = 'content';
		$this->layout = 'layout/oa';
		$this->render();
  	}
  	
  	public function outer()
  	{
  		$this->template = 'employee/employee_outer.tpl';
		$this->id = 'content';
		$this->layout = 'layout/oa';
		$this->render();
  	}
}
?>