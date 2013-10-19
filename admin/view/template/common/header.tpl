  <?php if ($logged) { ?>
  <div class="oa-top">
  <div class="oa-first">
  	<div class="oa-left">
	  	<div class="oa-logo">
	  	</div>
  	</div>
  	<div class="oa-right">
  		<ul class="nav">
	  		<li class="dropdown">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo $logged; ?> <b class="caret"></b></a>
				  <ul class="dropdown-menu">
				      <li><a href="<?php echo $logout; ?>" >  <?php echo $text_logout; ?></a></li>
		 		 </ul>
	  		</li>
		</ul>
	</div>
  </div>
  	 <div class="oa-nav-menu">
  		<ul class="nav">
		  <li class="first" id="h_catalog">
			  <a href="<?php echo $employee_index; ?>"  ><?php echo '员工门户'; ?> </a>
		  </li>
		  <li class="first" id="h_extension">
			  <a href="#"  ><?php echo '企业门户'; ?></a>
		  </li>
		  <li class="first" id="h_sale">
			<a href="<?php echo $task_index;?>"  ><?php echo '计划任务'; ?></a>
		  </li>
		  <li class="first" id="h_system">
			  <a href="<?php  echo  $task_index;?>"  <?php echo '工作流程'; ?></a>
		  </li>
		  <li class="first" id="h_system">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo '进销存'; ?></a>
		  </li>
		  <li class="first" id="h_system">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo '车辆管理'; ?></a>
		  </li>
		  <li class="first" id="h_system">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo '资产管理'; ?></a>
		  </li>
		  <li class="first" id="h_system">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo '用品管理'; ?></a>
		  </li>
		  
		   <li class="dropdown" id="h_system">
	  <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo $text_system; ?><b class="caret"></b></a>
	  <ul class="dropdown-menu">
		<li><a href="<?php echo $setting; ?>"><?php echo $text_setting; ?></a></li>
		<li><a href="<?php echo $parameter; ?>"><?php echo $text_localisation?></a></li>
		<li class="divider"></li>
	    <li><a href="<?php echo $user; ?>"><?php echo $text_user; ?></a></li>
		<li><a href="<?php echo $user_group; ?>"><?php echo $text_user_group; ?></a></li>
		<li class="divider"></li>
		<li><a href="<?php echo $error_log; ?>"><?php echo $text_error_log; ?></a></li>
	    <li><a href="<?php echo $backup; ?>"><?php echo $text_backup; ?></a></li>
	  </ul>
  </li>
		  
		 </ul>
		 </div>
  </div>
<?php } ?>
<script type="text/javascript"><!--
function getURLVar(urlVarName) {
	var urlHalves = String(document.location).toLowerCase().split('?');
	var urlVarValue = '';
	
	if (urlHalves[1]) {
		var urlVars = urlHalves[1].split('&');

		for (var i = 0; i <= (urlVars.length); i++) {
			if (urlVars[i]) {
				var urlVarPair = urlVars[i].split('=');
				
				if (urlVarPair[0] && urlVarPair[0] == urlVarName.toLowerCase()) {
					urlVarValue = urlVarPair[1];
				}
			}
		}
	}
	
	return urlVarValue;
} 

$(document).ready(function() {
	route = getURLVar('route');
	
	if (!route) {
		$('#h_dashboard').addClass('active');
	} else {
		part = route.split('/');
		
		url = part[0];
		
		if (part[1]) {
			url += '/' + part[1];
		}
		
		$('a[href*=\'' + url + '\']').parents('li[id]').addClass('active');
	}
});
//--></script> 