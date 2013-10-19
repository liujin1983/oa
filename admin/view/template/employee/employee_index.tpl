<style>
.nav {
	width: 213px;
	padding: 40px 28px 25px 0;
	font-family: "Microsoft yahei", Arial, Helvetica, sans-serif;
}

ul.nav {
	padding: 0;
	margin: 0;
	font-size: 1em;
	line-height: 0.5em;
	list-style: none;
	list-style: none;
}

ul.nav li {
}

ul.nav li a {
	line-height: 10px;
	font-size: 14px;
	padding: 10px 5px;
	color: #000;
	display: block;
	text-decoration: none;
	font-weight: bolder;
}

ul.nav li a:hover {
	background-color: #675C7C;
	color: white;
}

ul.nav ul {
	margin: 0;
	padding: 0;
	display: none;
}

ul.nav ul li {
	margin: 0;
	padding: 0;
	clear: both;
}

ul.nav ul li a {
	padding-left: 20px;
	font-size: 12px;
	font-weight: normal;
}

ul.nav ul li a:hover {
	background-color: #D3C99C;
	color: #675C7C;
}

ul.nav ul ul li a {
	color: silver;
	padding-left: 40px;
}

ul.nav ul ul li a:hover {
	background-color: #D3CEB8;
	color: #675C7C;
}

ul.nav span {
	float: right;
}
</style>
<script
	type="text/javascript" src="view/javascript/jquery/accordion.js"></script>
<div id="wrapper-250">
<ul class="nav">
	<li><a href="#">邮件系统</a>
	<ul>
		<li><a href="#">内部邮件</a>
			<ul>
				<li><a  href="#">发送邮件</a></li>
				<li><a  href="#">接收邮件</a></li>
			</ul>
		</li>
		<li><a href="#">短信管理</a></li>
		<li><a href="#">外部邮件</a></li>
		<li><a href="#">邮件系统报表</a></li>
	</ul>
	</li>
	<li><a href="#">个人办公</a>
		<ul>
		<li><a href="#">日常事务</a></li>
		<li><a href="#">日常事务报表</a></li>
		<li><a href="#">知识管理</a></li>
		<li><a href="#">办公智能报表</a></li>
	</ul>
	
	</li>
	<li><a href="#">信息管理</a>
		<ul>
		<li><a href="#">互动信息</a></li>
		<li><a href="#">公共管理</a></li>
	</ul>
	</li>
	<li><a href="http://www.helloweba.com/about.html" target="_blank">关于</a></li>
</ul>
</div>


<script type="text/javascript">
		$(document).ready(function() {

			 $(".nav").accordion({
			        accordion: true,
			        speed: 500,
				    closedSign: '[+]',
					openedSign: '[-]'
				});

			
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
