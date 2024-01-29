<?php
include 'config.php';

// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["admin"]) || $_SESSION["admin"] !== true){
    header("location: vlogin.php");
    exit;
}

$logged_user = $_SESSION['username'];
$current_time = date("h:i:s A");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if(isset($_POST['locationId']) && (isset($_POST['close-location']) || isset($_POST['open-location']))) {
		$stmt = $con->prepare("UPDATE locations SET open = ".(isset($_POST['close-location']) ? 'false' : 'true')." WHERE id = ?");
        $stmt->bind_param('s', $_POST['locationId']);
        $stmt->execute();
        $stmt->close();
		
		$action = isset($_POST['close-location']) ? 'غلق' : 'فتح';

		$stmt = $con->prepare("SELECT name FROM locations WHERE id = ?");
        $stmt->bind_param('s', $_POST['locationId']);
        $stmt->execute();
		$stmt->bind_result($location_name);
		$stmt->fetch();
		$stmt->close();

		$reason = isset($_POST['reason']) && !empty($_POST['reason']) ? ", السبب: ".$_POST['reason'] : "";
		
		$con->query("INSERT INTO system_log (title, username, created_at) VALUES ('تم $action المركز ($location_name)$reason','$logged_user','$current_time')" );
		header("location: vadmin");
		exit;
	}
}

$select_voters = mysqli_query($con,"SELECT * FROM voters");
$total_voters = mysqli_num_rows($select_voters);

$done_voters = mysqli_query($con,"SELECT * FROM voters WHERE status = '3'");
$total_done_voters = mysqli_num_rows($done_voters);

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

	<style>
	img:hover {
		-webkit-transform:scale(3.8) !important; /* or some other value */
    transform:scale(2.8) !important;
	}
	@media (max-width: 1024px) {
		.nav-container.container {
			width: unset;
		}
	}
	</style>
  <script>
  function checklength(i) {
    'use strict';
    if (i < 10) {
        i = "0" + i;
    }
    return i;
}

var minutes, seconds, count, counter, timer;
count = 301; //seconds
counter = setInterval(timer, 1000);

function timer() {
    'use strict';
    count = count - 1;
    minutes = checklength(Math.floor(count / 60));
    seconds = checklength(count - minutes * 60);
    if (count < 0) {
        clearInterval(counter);
        return;
    }
    document.getElementById("timer").innerHTML = 'سيتم تحديث الصفحة تلقائيا خلال ' + minutes + ':' + seconds + ' ';
    if (count === 0) {
        location.reload();
    }
}
  </script>
</head>

<body>
<div id="wrapper">
	<header class="fixed-header">
		<div class="header-top">
			<div class="container">
				<div class="pull-right">
					<button type="button" aria-label="Close" class="btn btn-box-tool hidden-on-desktop js__menu_button"><i class="fa fa-bars padding-10"></i></button>
					<a class="logo" style="font-weight: bold;">نظام التصويت الإلكتروني</a>
				</div>

				<div class="pull-left">
					<a class="logo" style="font-size: 15px;">مرحبا <?php echo $logged_user; ?></a>
				</div>
				<!-- /.pull-right -->

			</div>
			<!-- /.container -->
		</div>
		<!-- /.header-top -->
		<nav class="nav nav-horizontal">
			<button type="button" class="menu-close hidden-on-desktop js__close_menu"><i class="fa fa-times"></i><span>إغلاق</span></button>
			<div class="container nav-container"> 
				<ul class="menu">
					<li class="current">
						<a href="vadmin"><i class="ico mdi mdi-home"></i><span style="font-weight: bold;">المراكز</span></a>
					</li>
					<li>
						<a href="vusers"><i class="ico mdi mdi-account"></i><span style="font-weight: bold;">الأعضاء</span></a>
					</li>
					<li>
						<a href="statistics"><i class="ico mdi mdi-chart-bar"></i><span style="font-weight: bold;">الإحصائيات</span></a>
					</li>
					<li>
						<a href="log"><i class="ico mdi mdi-menu"></i><span style="font-weight: bold;">سجل النظام</span></a>
					</li>
					<li>
						<a href="logout"><i class="ico mdi mdi-logout"></i><span style="font-weight: bold;">تسجيل خروج</span></a>
					</li>
				</ul>
				<!-- /.menu -->
			</div>
			<!-- /.container -->
		</nav>
		<!-- /.nav-horizontal -->
	</header>
	<!-- /.fixed-header -->
	<div class="main-content container">
		<div class="row small-spacing">
			<div class="col-md-6 col-xs-12">
				<div class="box-content bg-primary text-white">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-users"></i>
						<p class="text text-white" style="font-weight:bold;">إجمالي الناخبين</p>
						<h2 class="counter"><?php echo $total_voters; ?></h2>
					</div>
				</div>
				<!-- /.box-content -->
			</div>
			<!-- /.col-md-6 col-xs-12 -->
			<div class="col-md-6 col-xs-12">
				<div class="box-content bg-info text-white">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-check"></i>
						<p class="text text-white" style="font-weight:bold;">إجمالي الأصوات</p>
						<h2 class="counter"><?php echo $total_done_voters; ?></h2>
					</div>
				</div>
				<!-- /.box-content -->
			</div>
			<!-- /.col-md-6 col-xs-12 -->
		</div>
		<!-- .row -->

		<div class="row small-spacing">
			<div class="col-xs-12">
				<div class="box-content">
					<h4 class="box-title">المراكز</h4>
					<!-- /.box-title -->

					<table id="main" class="table table-striped table-bordered display" style="width:100%">
						<thead>
							<tr>
								<!--<th>#</th>-->
								<th>الإسم</th>
								<th>الحالة</th>
								<th>البيانات</th>
								<th>الأوامر</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$select_locations = mysqli_query($con, "SELECT id, name, open FROM locations ORDER BY name ASC");

							while($fetch_location = mysqli_fetch_assoc($select_locations)){

							echo '
							<tr>
									<td>'.$fetch_location['name'].'</td>
									<td>'.($fetch_location['open'] ? '<span class="label label-success">مفتوح</span>' : '<span class="label label-danger">مغلق</span>').'</td>
									<td>
										<a class="btn btn-default btn-icon btn-sm btn-icon-left" role="button" href="voters?l='.$fetch_location['id'].'"><i class="ico mdi mdi-account"></i>بيانات الناخبين</a>
										<a class="btn btn-default btn-icon btn-sm btn-icon-left" role="button" href="vscreens?l='.$fetch_location['id'].'"><i class="ico mdi mdi-monitor-multiple"></i>صفحات الإقتراع</a>
									</td>
									<td><form method="POST">
										<input type="hidden" name="locationId" value="'.$fetch_location['id'].'">
										<input type="hidden" name="reason" value="">'
									.(
										$fetch_location['open'] ? 
										'<button type="submit" data-confirmed="" name="close-location" class="btn btn-icon btn-icon-left btn-danger btn-xs waves-effect waves-light"><i class="ico fa fa-times"></i>إغلاق المركز</button>' 
										: '<button type="submit" name="open-location" class="btn btn-icon btn-icon-left btn-info btn-xs waves-effect waves-light"><i class="ico fa fa-check"></i>فتح المركز</button>' 
									).'
									</form></td>
							</tr>
							';
							}
							?>
						</tbody>
					</table>

				</div>
        <span id="timer"></span>
				<!-- /.box-content -->
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
	<!-- Full Screen Plugin -->
	<script src="assets/plugin/fullscreen/jquery.fullscreen-min.js"></script>

	<!-- Data Tables -->
	<script src="assets/plugin/datatables/media/js/jquery.dataTables.min.js"></script>
	<script src="assets/plugin/datatables/media/js/dataTables.bootstrap.min.js"></script>
	<script src="assets/plugin/datatables/extensions/Responsive/js/dataTables.responsive.min.js"></script>
	<script src="assets/plugin/datatables/extensions/Responsive/js/responsive.bootstrap.min.js"></script>
	<script src="assets/scripts/datatables.demo.min.js"></script>

	<script>

	$('#main').DataTable( {
    language: {
        search: "البحث ",
				sZeroRecords: "لايوجد نتائج",
				sInfoEmpty:      "",
    		sInfoFiltered:   "",
				sInfo:           "يتم عرض من _START_ الى _END_",
				sLengthMenu:     " _MENU_ ",
				paginate: {
            first:      "الأول",
            previous:   "السابق",
            next:       "التالي",
            last:       "الأخير"
        },

    }
  } );

  $('#btn-submit').on('click', function(e){
		e.preventDefault();
		swal({   
			title: "Are you sure?",
			text: "You will not be able to recover this lorem ipsum!",         type: "warning",   
			showCancelButton: true,   
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Yes, delete it!", 
			closeOnConfirm: false 
		}, 
			function(){   
			$("#form-loader").submit();
		});
	});

	$('button[name=close-location]').on('click', function(e){
		let $this = $(this);
		if ($this.data("confirmed") === true) {
			return;
		}
		e.preventDefault();
		swal({   
			title: "هل انت متأكد من غلق المركز؟",
			showCancelButton: true,   
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "نعم، أغلق المركز", 
			cancelButtonText: "رجوع",
			closeOnConfirm: false,
		}, 
		function(){
			swal({
			title: "سبب الإغلاق",
			type: 'input',
			showCancelButton: true,
			closeOnConfirm: true,
			confirmButtonColor: "#DD6B55",
			cancelButtonText: "رجوع",
			confirmButtonText: "أغلق المركز"
			}, function(inputValue){
				if(inputValue === false) {
					return;
				}
				let form = $this.closest('form');
				form.find('input[name=reason]').val(inputValue);
				$this.data("confirmed", true);
				$this.click();
			});

		});
	});

	</script>

	<script src="assets/scripts/main.min.js"></script>
	<script src="assets/scripts/horizontal-menu.min.js"></script>
</body>
</html>
