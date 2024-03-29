<?php
include 'config.php';

header("location: vlogin.php");
exit;


$unique_key = $_GET['unique_key'];
$select_voters = mysqli_query($con, "SELECT * FROM voters WHERE unique_key = '$unique_key' AND status='1'");
if(mysqli_num_rows($select_voters) == 0){
	header("Location: https://mawkebboori.com");
	die();
}
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

	<!-- Percent Circle -->
	<link rel="stylesheet" href="assets/plugin/percircle/css/percircle.css">

	<!-- Chartist Chart -->
	<link rel="stylesheet" href="assets/plugin/chart/chartist/chartist.min.css">

	<!-- FullCalendar -->
	<link rel="stylesheet" href="assets/plugin/fullcalendar/fullcalendar.min.css">
	<link rel="stylesheet" href="assets/plugin/fullcalendar/fullcalendar.print.css" media='print'>

	<!-- RTL -->
	<link rel="stylesheet" href="assets/styles/style-rtl.min.css">

</head>

<body>
<header class="fixed-header">
	<div class="header-top" style="height: 20px;">
		<div class="container">
			<div class="pull-right">
				<a href="" class="logo"></a>
			</div>
			<!-- /.pull-right -->

		</div>
		<!-- /.container -->
	</div>
</header>
<!-- /.fixed-header -->



<div id="wrapper">

	<center style="margin-top: -20px;">
	<img src="logo.png" alt="logo" width="220"/>
	<h3>نظام التصويت الإلكتروني</h3>
	<h4>صفحة التصويت</h4>
	<hr />

	<div class="main-content container">
		<div class=”float-button”></div>

		<div class="row small-spacing">
			<?php
			if(isset($_GET['is_submited'])){
				$candidates = $_GET['selected_candidates'];
				mysqli_query($con,"UPDATE voters SET status ='3' WHERE unique_key = '$unique_key'");
				foreach ($candidates as $candidate=>$value) {
									 $update_voting = mysqli_query($con,"UPDATE candidates SET votes =votes + 1 WHERE id = '$value'");
			        }
			echo '<script type="text/javascript">location.href = "/success";</script>';
			}

			 ?>
			<h4>
				ملاحظة: مجموع المرشحين هو 15، يجب عليك إختيار 10 اشخاص او أقل.
			</h4>

			<h4 style="color: #af5656;">
				عدد اختياراتك الحالي: <span id="count-checked-checkboxes">0</span>
			</h4>

			<hr>
			<form name="submitted_votes" action="vote.php" method="get" enctype="multipart/form-data">
				<input type="hidden" name="unique_key" value="<?php echo $unique_key?>"/>
				<input type="hidden" name="is_submited" value="yes"/>

			<?php
			$select_candidates = mysqli_query($con, "SELECT * FROM candidates");
			while($fetch_candidates = mysqli_fetch_assoc($select_candidates)){

			echo '
			<div class="col-lg-4 col-md-6">
				<div class="box-contact">
					<img src="uploads/candidates/'.$fetch_candidates['img'].'" alt="" class="avatar">
					<h3 class="name margin-top-10">'.$fetch_candidates['name'].'</h3>
					<div class="text-muted">
						<input type="checkbox" name="selected_candidates[]" value="'.$fetch_candidates['id'].'" style="width: 25px; height: 25px;"><br>
						<label for="chk-1">اضغط على المربع للإختيار</label>
					</div>
				</div>
			</div>

			';
			}
			?>
			<!--
			<div class="col-lg-4 col-md-6">
				<div class="box-contact">
					<img src="http://placehold.it/450x450" alt="" class="avatar">
					<h3 class="name margin-top-10">خالد محمد</h3>
					<div class="text-muted">
						<input type="checkbox" id="chk-1" style="width: 25px; height: 25px;"><br>
						<label for="chk-1">اضغط على المربع للإختيار</label>
					</div>
				</div>
			</div>
-->
			<div class="col-sm-12 col-lg-12 col-md-12">
				<center>
					<button type="submit" class="btn btn-icon btn-icon-left btn-success waves-effect waves-light" onclick="return confirm('هل انت متأكد من تسليم الطلب؟ لن تتمكن من الدخول لهذة الصفحة مجدداً')"><i class="ico fa fa-check"></i>إنتهاء وتسليم</button>
				</center>
			</div>
		</form>


			<br />
			<br />
		</div>

	</div>
	<!-- /.main-content -->
</div><!--/#wrapper -->

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
	<!-- Full Screen Plugin -->
	<script src="assets/plugin/fullscreen/jquery.fullscreen-min.js"></script>

	<!-- Data Tables -->
	<script src="assets/plugin/datatables/media/js/jquery.dataTables.min.js"></script>
	<script src="assets/plugin/datatables/media/js/dataTables.bootstrap.min.js"></script>
	<script src="assets/plugin/datatables/extensions/Responsive/js/dataTables.responsive.min.js"></script>
	<script src="assets/plugin/datatables/extensions/Responsive/js/responsive.bootstrap.min.js"></script>
	<script src="assets/scripts/datatables.demo.min.js"></script>


	<script src="assets/scripts/main.min.js"></script>
	<script src="assets/scripts/horizontal-menu.min.js"></script>
	<script>
	jQuery(function(){
	    var max = 10;
	    var checkboxes = $('input[type="checkbox"]');

	    checkboxes.change(function(){
	        var current = checkboxes.filter(':checked').length;
	        checkboxes.filter(':not(:checked)').prop('disabled', current >= max);
					$('#count-checked-checkboxes').text(current);
					$('#count-checked-checkboxes-balance').text(10 - current);

	    });
	});
	</script>
</body>
</html>
