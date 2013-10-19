<div id="wrapper-250">
  <ul class="accordion">
    <li id="one" class="files"> <a href="#one">邮件系统</a>
      <ul class="sub-menu">
        <li><a href="<?php echo $email_inner;?>" target="taskFirst">内部邮件</a></li>
        <li><a href="<?php echo $email_outer;?>" target="taskFirst">外部邮件</a></li>
      </ul>
    </li>
    <li id="two" class="mail"> <a href="#two">个人办公</a>
      <ul class="sub-menu">
        <li><a href="#">日常事务</a></li>
      </ul>
    </li>
    <li id="three" class="cloud"> <a href="#three">Cloud<span>58</span></a>
      <ul class="sub-menu">
        <li><a href="#"><em>01</em>Connect<span>12</span></a></li>
        <li><a href="#"><em>02</em>Profiles<span>19</span></a></li>
        <li><a href="#"><em>03</em>Options<span>27</span></a></li>
        <li><a href="#"><em>04</em>Connect<span>12</span></a></li>
        <li><a href="#"><em>05</em>Profiles<span>19</span></a></li>
        <li><a href="#"><em>06</em>Options<span>27</span></a></li>
      </ul>
    </li>
    <li id="four" class="sign"> <a href="#four">Sign Out</a>
      <ul class="sub-menu">
        <li><a href="#"><em>01</em>Log Out</a></li>
        <li><a href="#"><em>02</em>Delete Account</a></li>
        <li><a href="#"><em>03</em>Freeze Account</a></li>
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