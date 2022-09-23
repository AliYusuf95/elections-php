<?php
include 'config.php';

?>
<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

	<title>نظام التصويت الإلكتروني</title>

	<!-- Main Styles -->
	<link rel="stylesheet" href="assets/styles/style-horizontal.min.css">

	<!-- Material Design Icon -->
	<link rel="stylesheet" href="assets/fonts/material-design/css/materialdesignicons.css">

	<!-- mCustomScrollbar -->
	<link rel="stylesheet" href="assets/plugin/mCustomScrollbar/jquery.mCustomScrollbar.min.css">

	<!-- Waves Effect -->
	<link rel="stylesheet" href="assets/plugin/waves/waves.min.css">

	<!-- Sweet Alert -->
	<link rel="stylesheet" href="assets/plugin/sweet-alert/sweetalert.css">

	<!-- RTL -->
	<link rel="stylesheet" href="assets/styles/style-rtl.min.css">

	<style>
	.custom-file-input::-webkit-file-upload-button {
  visibility: hidden;
}
.custom-file-input::before {
  content: 'اختر الصورة';
  display: inline-block;
  background: #eee;
  border: 1px solid #999;
  border-radius: 3px;
  padding: 5px 8px;
  outline: none;
  white-space: nowrap;
  -webkit-user-select: none;
  cursor: pointer;
  text-shadow: 1px 1px #fff;
  font-weight: 700;
  font-size: 10pt;
}
.custom-file-input:hover::before {
  border-color: black;
}
.custom-file-input:active::before {
  background: -webkit-linear-gradient(top, #e3e3e3, #f9f9f9);
}
	</style>
</head>

<body>
<header class="fixed-header">
	<div class="header-top" style="height: 20px;">
		<div class="container">
			<div class="pull-right">
				<a class="logo"></a>
			</div>
			<!-- /.pull-right -->

		</div>
		<!-- /.container -->
	</div>
</header>
<!-- /.fixed-header -->



<div id="wrapper">
	<div class="main-content container">

		<div class="container">

			<center style="margin-top: -20px;">
			<img src="logo.png" alt="logo" width="220"/>
			<h3>نظام التصويت الإلكتروني</h3>
			
			<br  />
			<hr />
			<br />

			<div class="row">
				<div class="col-lg-4 col-md-4"></div>
				<a class="col-xs-12 col-lg-4 col-md-4 box-contact" href="/morsh7een">
					<h3 class="margin-bottom-50 margin-top-10"><i class="ico mdi mdi-account"></i> المرشحين</h3>
				</a>
				<div class="col-lg-4 col-md-4"></div>
			</div>
			<div class="row">
				<a class="col-xs-12 col-lg-5 col-md-5 box-contact" href="/vlogin">
					<h3 class="margin-bottom-50 margin-top-10"><i class="ico fa fa-pencil"></i> صفحة التسجيل</h3>
				</a>
				<div class="col-xs-12 col-lg-2 col-md-2"></div>
				<a class="col-xs-12 col-lg-5 col-md-5 box-contact" href="/vote-screen">
					<h3 class="margin-bottom-50 margin-top-10"><i class="ico mdi mdi-monitor-multiple"></i> شاشة التصويت</h3>
				</a>
			</div>
		
		</div>

	</div>
	<!-- /.main-content -->
</div><!--/#wrapper -->
	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
		<script src="assets/script/html5shiv.min.js"></script>
		<script src="assets/script/respond.min.js"></script>
	<![endif]-->
	<!--
	================================================== -->
	<!-- Placed at the end of the document so the pages load faster -->
	<script src="assets/scripts/jquery.min.js"></script>
	<script src="assets/scripts/modernizr.min.js"></script>
	<script src="assets/plugin/bootstrap/js/bootstrap.min.js"></script>
	<script src="assets/plugin/mCustomScrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
	<script src="assets/plugin/nprogress/nprogress.js"></script>
	<script src="assets/plugin/sweet-alert/sweetalert.min.js"></script>
	<script src="assets/plugin/waves/waves.min.js"></script>

	<script>
	$(function(){
	  $("input[name='mobile']").on('input', function (e) {
	    $(this).val($(this).val().replace(/[^0-9]/g, ''));
	  });
	  $("input[name='cpr']").on('input', function (e) {
	    $(this).val($(this).val().replace(/[^0-9]/g, ''));
	  });
	});
	</script>
	<script src="assets/scripts/main.min.js"></script>
</body>
</html>
