<?php 
class ControllerTaskTask extends Controller { 
	private $error = array();
   
  	public function index() {
  		
  		$this->data['task_add'] = $this->url->link('task/task/add', 'token=' . $this->session->data['token'], 'SSL');
  		
  		//此处可以读取数据库操作,类似如下
//  		$data = array();
//  		$this->load->model('task/task');
//  		$results = $this->model_task_task->getTasks($data);
//  		$this->data['tasks'] = $results;
  		
  		$this->template = 'task/task_index.tpl';
		$this->id = 'content';
		$this->layout = 'layout/oa';
		$this->render();
  	}

  	public function first()
  	{
  		$this->template = 'task/task_first.tpl';
		$this->id = 'content';
		$this->layout = 'layout/oa';
		$this->render();
  	}
  	
  	public function add()
  	{
  		$this->template = 'task/task_add.tpl';
		$this->id = 'content';
		$this->layout = 'layout/oa';
		$this->render();
  	}
}
?>