<div id="wrapper-250">
  <ul class="accordion">
    <li id="one" class="files"> <a href="#one">资料管理</a>
      <ul class="sub-menu">
        <li><a href="#" target="taskFirst">知识库</a></li>
        <li><a href="#" target="taskFirst">公司管理</a></li>
      </ul>
    </li>
    <li id="two" class="mail"> <a href="#two">信息管理</a>
      <ul class="sub-menu">
        <li><a href="#">公共信息</a></li>
        <li><a href="#">招聘信息</a></li>
      </ul>
    </li>
    <li id="three" class="cloud"> <a href="#three">会议管理</a>
      <ul class="sub-menu">
        <li><a href="#">基础设置</a></li>
        <li><a href="#">会议管理</a></li>
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