<div id="wrapper-250">
  <ul class="accordion">
    <li id="one" class="files"> <a href="#one">My Files<span>495</span></a>
      <ul class="sub-menu">
        <li><a href="<?php echo $task_add;?>" target="taskFirst"><em>01</em>添加任务<span>42</span></a></li>
        <li><a href="#"><em>02</em>Skydrive<span>87</span></a></li>
        <li><a href="#"><em>03</em>FTP Server<span>366</span></a></li>
        <li><a href="#"><em>04</em>Dropbox<span>1</span></a></li>
        <li><a href="#"><em>05</em>Skydrive<span>10</span></a></li>
      </ul>
    </li>
    <li id="two" class="mail"> <a href="#two">Mail<span>26</span></a>
      <ul class="sub-menu">
        <li><a href="#"><em>01</em>Hotmail<span>9</span></a></li>
        <li><a href="#"><em>02</em>Yahoo<span>14</span></a></li>
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