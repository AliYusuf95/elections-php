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
	if(isset($_POST['remove-from-location']) && isset($_POST['userId'])) {
		$stmt = $con->prepare("SELECT username, locations.name FROM users LEFT JOIN locations ON users.locationId = locations.id WHERE users.id = ?");
        $stmt->bind_param('s', $_POST['userId']);
        $stmt->execute();
		$stmt->bind_result($username, $location_name);
		$stmt->fetch();
		$stmt->close();

		$stmt = $con->prepare("UPDATE users SET locationId = NULL WHERE id = ?");
        $stmt->bind_param('s', $_POST['userId']);
        $stmt->execute();
        $stmt->close();

		$con->query("INSERT INTO system_log (title, username, created_at) VALUES ('تم حذف المستخدم ($username) من المركز ($location_name)','$logged_user','$current_time')" );

		header("location: vusers");
		exit;
	} else if (isset($_POST['add-to-location']) && isset($_POST['userId']) && isset($_POST['locationId'])) {
		$stmt = $con->prepare("UPDATE users SET locationId = ? WHERE id = ? AND locationId IS NULL");
        $stmt->bind_param('ii', $_POST['locationId'], $_POST['userId']);
        $stmt->execute();
        $stmt->close();

		$stmt = $con->prepare("SELECT username, locations.name FROM users LEFT JOIN locations ON users.locationId = locations.id WHERE users.id = ?");
        $stmt->bind_param('s', $_POST['userId']);
        $stmt->execute();
		$stmt->bind_result($username, $location_name);
		$stmt->fetch();
		$stmt->close();
		$con->query("INSERT INTO system_log (title, username, created_at) VALUES ('تم إضافة المستخدم ($username) إلى المركز ($location_name)','$logged_user','$current_time')" );

		header("location: vusers");
		exit;
	}
}

$total_users = mysqli_num_rows(mysqli_query($con,"SELECT * FROM users"));
$total_new_users = mysqli_num_rows(mysqli_query($con,"SELECT * FROM users WHERE locationId IS NULL"));
?>
<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

	<title>الأعضاء | نظام التصويت الإلكتروني</title>

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
					<li>
						<a href="vadmin"><i class="ico mdi mdi-home"></i><span style="font-weight: bold;">المراكز</span></a>
					</li>
					<li class="current">
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
						<p class="text text-white" style="font-weight:bold;">إجمالي أعضاء التسجيل</p>
						<h2 class="counter"><?php echo $total_users; ?></h2>
					</div>
				</div>
				<!-- /.box-content -->
			</div>
			<!-- /.col-md-6 col-xs-12 -->
			<div class="col-md-6 col-xs-12">
				<div class="box-content bg-info text-white">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-check"></i>
						<p class="text text-white" style="font-weight:bold;">أعضاء الجدد</p>
						<h2 class="counter"><?php echo $total_new_users; ?></h2>
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
					<h4 class="box-title">الأعضاء</h4>
					<!-- /.box-title -->

					<table id="main" class="table table-striped table-bordered display" style="width:100%">
						<thead>
							<tr>
								<!--<th>#</th>-->
								<th>الإسم</th>
								<th>إسم الدخول</th>
								<th>المركز</th>
								<th>الأوامر</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$select_users = mysqli_query($con, "SELECT u.id AS `u.id`, u.name AS `u.name`, u.username AS `u.username`, l.name AS `l.name`, l.id AS `l.id` FROM users u LEFT JOIN locations l ON u.locationId = l.id");

							while($fetch_users = mysqli_fetch_assoc($select_users)){

								$isNewUser = empty($fetch_users['l.id']) ? true : false;

							echo '
							<tr>
									<td>'.($fetch_users['u.name'] ? $fetch_users['u.name'] : '-').'</td>
									<td>'.$fetch_users['u.username'].'</td>
									<td>'.($isNewUser ? '-' : $fetch_users['l.name']).'</td>
									<td>
									'.(
										$isNewUser ? 
										'<button type="button" class="btn btn-icon btn-icon-left btn-info btn-xs waves-effect waves-light" data-toggle="modal" data-target="#addModal" data-name="'.$fetch_users['u.name'].'" data-username="'.$fetch_users['u.username'].'" data-id="'.$fetch_users['u.id'].'"><i class="ico fa fa-plus"></i>إضافة إلى مركز</button>' 
										: '<button type="button" class="btn btn-icon btn-icon-left btn-danger btn-xs waves-effect waves-light" data-toggle="modal" data-target="#deleteModal" data-name="'.($fetch_users['u.name'] ? $fetch_users['u.name'] : $fetch_users['u.username']).'" data-id="'.$fetch_users['u.id'].'"><i class="ico fa fa-times"></i>حذف من المركز</button>'
									).'
									</td>
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
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel">إزالة العضو من المركز</h4>
				</div>
				<form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
					<div class="modal-body">
						<h3>هل أنت متأكد من إزالة العضو من المركز</h3>
						<h1 class="name"></h1>
					</div>
					<div class="modal-footer">
						<input type="hidden" name="userId" class="user_id" value="">
						<button id="submit" name="remove-from-location" class="btn btn-icon btn-icon-left btn-danger btn-xs waves-effect waves-light"><i class="ico fa fa-times"></i>حذف</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel">إضافة العضو إلى مركز</h4>
				</div>
				<form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
					<div class="modal-body">
						<input type="hidden" name="userId" class="user_id" value="">
						<div class="form-group">
							<label class="sr-only" for="name">الإسم</label>
							<input type="text" class="form-control name" placeholder="الإسم" readonly>
						</div>
						<div class="form-group">
							<label class="sr-only" for="username">إسم المستخدم</label>
							<input type="text" class="form-control username" placeholder="إسم المستخدم" readonly>
						</div>
						<div class="form-group">
							<div class="input-group">
								<div class="input-group-addon"><span class="glyphicon glyphicon-map-marker"></span></div>
								<select class="form-control location" name="locationId" required>
								<option value="">اختر المركز</option>
								<?php
								$select_locations = mysqli_query($con, "SELECT id, name FROM locations");
								while($fetch_locations = mysqli_fetch_assoc($select_locations)) {
									echo '<option value="'.$fetch_locations['id'].'">'.$fetch_locations['name'].'</option>';
								}
								?>
							</select>
							</div>
						</div>
					</div>
					<div class="modal-footer" class="add-form">
						<button id="submit" name="add-to-location" class="btn btn-icon btn-icon-left btn-info btn-xs waves-effect waves-light"><i class="ico fa fa-plus"></i>إضافة</button>
					</div>
				</form>
			</div>
		</div>
	</div>
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
	$('#deleteModal').on('show.bs.modal', function (event) {
		var button = $(event.relatedTarget);
		var name = button.data('name');
		var userId = button.data('id');
		var modal = $(this);
		modal.find('.name').text('('+name+')');
		modal.find('input.user_id').val(userId);
	});

	$('#addModal').on('show.bs.modal', function (event) {
		var button = $(event.relatedTarget);
		var name = button.data('name');
		var username = button.data('username');
		var userId = button.data('id');
		var modal = $(this);
		modal.find('input.name').val(name);
		modal.find('input.username').val(username);
		modal.find('input.user_id').val(userId);
		modal.find('select.location').val('');
	});
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
  	});
	</script>

	<script src="assets/scripts/main.min.js"></script>
	<script src="assets/scripts/horizontal-menu.min.js"></script>
</body>
</html>
