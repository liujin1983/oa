<div id="wrapper-250">
  <ul class="accordion">
    <li id="one" class="files"> <a href="#one">计划管理</a>
      <ul class="sub-menu">
        <li><a href="<?php echo $task_add;?>" target="taskFirst">我的计划</a></li>
        <li><a href="<?php echo $task_add;?>" target="taskFirst">下属计划</a></li>
      </ul>
    </li>
    <li id="two" class="mail"> <a href="#two">任务管理</a>
      <ul class="sub-menu">
        <li><a href="#">我的任务</a></li>
        <li><a href="#">下属任务</a></li>
      </ul>
    </li>
    <li id="three" class="cloud"> <a href="#three">报表分析</a>
      <ul class="sub-menu">
        <li><a href="#">计划报表</a></li>
        <li><a href="#">任务报表</a></li>
      </ul>
    </li>
  </ul>
</div>


<script type="text/javascript">
		$(document).ready(function() {
			// Store variables
			var accordion_head = $('.accordion > li > a'),
				accordion_body = $('.accordion li > .sub-menu');
			// Open the first tab on load
			accordion_head.first().addClass('active').next().slideDown('normal');
			// Click function
			accordion_head.on('click', function(event) {
				// Disable header links
				event.preventDefault();
				// Show and hide the tabs on click
				if ($(this).attr('class') != 'active'){
					accordion_body.slideUp('normal');
					$(this).next().stop(true,true).slideToggle('normal');
					accordion_head.removeClass('active');
					$(this).addClass('active');
				}
			});
		});
	</script>