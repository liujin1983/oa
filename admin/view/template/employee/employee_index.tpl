<div id="wrapper-250">
  <ul class="accordion">
    <li id="one" class="files"> <a href="#one">邮件系统</a>
      <ul class="sub-menu">
        <li><a href="<?php echo $email_inner;?>" target="taskFirst">内部邮件</a></li>
        <li><a href="#" target="taskFirst">短信管理</a></li>
        <li><a href="<?php echo $email_outer;?>" target="taskFirst">外部邮件</a></li>
        <li><a href="#" target="taskFirst">邮件系统报表</a></li>
      </ul>
    </li>
    <li id="two" class="mail"> <a href="#two">个人办公</a>
      <ul class="sub-menu">
        <li><a href="#">日常事务</a></li>
        <li><a href="#">日常事务报表</a></li>
        <li><a href="#">知识管理</a></li>
        <li><a href="#">智能办公</a></li>
      </ul>
    </li>
    <li id="three" class="cloud"> <a href="#three">信息管理</a>
      <ul class="sub-menu">
        <li><a href="#">互动信息</a></li>
        <li><a href="#">公共信息</a></li>
      </ul>
    </li>
    <li id="four" class="sign"> <a href="#four">个人管理</a>
      <ul class="sub-menu">
        <li><a href="#">个人信息</a></li>
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