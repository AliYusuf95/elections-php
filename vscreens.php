<?php
global $con;
include 'config.php';

// Initialize the session
session_start();

$location_id = 0;
$location_name = '';
$location_error = null;
$isAdmin = false;
$isUser = false;

$users_table = 'users';

// Check if the user is logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["user"]) && $_SESSION["user"] === true) {
    $select_user_Location = mysqli_query($con, "SELECT locationId FROM $users_table WHERE id = " . $_SESSION["id"]);
    $location_id = mysqli_fetch_row($select_user_Location)[0];
    $isUser = true;
    $logged_user = $_SESSION['username'];
} else if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["admin"]) && $_SESSION["admin"] === true && isset($_GET["l"])) {
    $isAdmin = true;
    $location_id = $_GET["l"];
    $isUser = true;
    $logged_user = $_SESSION['username'];
} else {
    $location_id = $_GET["l"];
}

function getLocationInfo() {
    global $con, $location_id, $location_name, $location_error;
    
    $sql = "SELECT name FROM locations WHERE id = ?";

    if (!($stmt = mysqli_prepare($con, $sql))) {
        $location_error = "Oops! Something went wrong. Please try again later.";
        return;
    }
    
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "i", $location_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        $location_error = "لم يتم إيجاد المركز، يرجى التواصل مع إدارة اللجنة";
        return;
    }
    // Store result
    mysqli_stmt_store_result($stmt);
    // Check if username exists, if yes then verify password
    if (mysqli_stmt_num_rows($stmt) < 1) {
        $location_error = "لم يتم إيجاد المركز، يرجى التواصل مع إدارة اللجنة";
        return;
    }
    mysqli_stmt_bind_result($stmt, $location_name);
    mysqli_stmt_fetch($stmt);
}

getLocationInfo();

$current_time = date("h:i:s A");

if(!isset($location_error)) {

	$select_total_screens = mysqli_num_rows(mysqli_query($con,"SELECT id FROM screens WHERE locationId = " . $location_id));

	$select_connected_screens = mysqli_num_rows(mysqli_query($con,"SELECT id FROM screens WHERE connected = true AND locationId = ". $location_id));
	$select_available_screens = mysqli_num_rows(mysqli_query($con,"SELECT id FROM screens WHERE voterId IS NULL AND locationId = ". $location_id));

	$select_screens = mysqli_query($con, "SELECT id, name, connected, IF(voterId IS NULL,true,false) as available, updatedAt FROM screens WHERE locationId = ".$location_id." ORDER BY id ASC");

}

$stmt = $con->prepare("SELECT COUNT(id) as total_voters FROM voters");
$stmt->execute();
$stmt->bind_result($total_voters);
$stmt->fetch();
$stmt->close();

$stmt = $con->prepare("SELECT COUNT(id) as total_voters_registered FROM voters_data");
$stmt->execute();
$stmt->bind_result($total_voters_registered);
$stmt->fetch();
$stmt->close();

$stmt = $con->prepare("SELECT COUNT(id) as total_done_voters FROM voters_data WHERE status = 3");
$stmt->execute();
$stmt->bind_result($total_done_voters);
$stmt->fetch();
$stmt->close();

$voters_percent = $total_voters_registered > 0 ? round(($total_done_voters / $total_voters) * 100, 2) : 0;

// $done_voters = mysqli_query($con,"SELECT * FROM screens WHERE status = '3'");
// $total_done_voters = mysqli_num_rows($done_voters);
?>
<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

	<title>صفحات الإقتراع | نظام التصويت الإلكتروني</title>

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
    <?php if(!$isUser): ?>
  <script>
  function checklength(i) {
		'use strict';
		if (i < 10) {
			i = "0" + i;
		}
		return i;
	}

	var minutes, seconds, count, counter, timer;
	count = 10; //seconds
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
    <?php endif; ?>
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

                <?php if($isUser): ?>
				<div class="pull-left">
					<a class="logo" style="font-size: 15px;">مرحبا <?php echo $logged_user; ?></a>
				</div>
                <?php endif; ?>
				<!-- /.pull-right -->

			</div>
			<!-- /.container -->
		</div>
		<!-- /.header-top -->
        <?php if($isUser): ?>
		<nav class="nav nav-horizontal">
			<button type="button" class="menu-close hidden-on-desktop js__close_menu"><i class="fa fa-times"></i><span>إغلاق</span></button>
			<div class="container nav-container"> 
				<ul class="menu">
					<?php 
						if($isAdmin == true):
						?>
							<li>
								<a href="vadmin"><i class="ico mdi mdi-home"></i><span style="font-weight: bold;">المراكز</span></a>
							</li>
							<li>
								<a href="vusers"><i class="ico mdi mdi-account"></i><span style="font-weight: bold;">الأعضاء</span></a>
							</li>
                            <li>
                                <a href="vcandidates"><i class="ico mdi mdi-account-multiple"></i><span style="font-weight: bold;">المرشحون</span></a>
                            </li>
                            <li>
                                <a href="voters?l=all"><i class="ico mdi mdi mdi-account-card-details"></i><span style="font-weight: bold;">الناخبين</span></a>
                            </li>
							<li>
								<a href="statistics"><i class="ico mdi mdi-chart-bar"></i><span style="font-weight: bold;">الإحصائيات</span></a>
							</li>
							<li>
								<a href="log"><i class="ico mdi mdi-menu"></i><span style="font-weight: bold;">سجل النظام</span></a>
							</li>
						<?php
						else:
						?>
							<li>
								<a href="vregistration"><i class="ico mdi mdi-home"></i><span style="font-weight: bold;">التسجيل</span></a>
							</li>
							<li class="current">
								<a class="text-primary" href="vscreens"><i class="ico mdi mdi-monitor-multiple"></i><span style="font-weight: bold;">صفحات الإقتراع</span></a>
							</li>
							<li>
								<a href="voters"><i class="ico mdi mdi-account"></i><span style="font-weight: bold;">بيانات الناخبين</span></a>
							</li>
						<?php
						endif;
						?>
						<li>
							<a href="logout"><i class="ico mdi mdi-logout"></i><span style="font-weight: bold;">تسجيل خروج</span></a>
						</li>
					</ul>
				</div>
				<!-- /.menu -->
			<!-- /.container -->
		</nav>
		<!-- /.nav-horizontal -->
	</header>
    <?php endif; ?>
	<!-- /.fixed-header -->

	<?php	
	if(isset($location_error)):
	?>
	<br />
	<div class="main-content container">
		<div class="alert alert-danger">
			<strong>خطأ!</strong> <?=$location_error?>
		</div>
		<span id="timer"></span>
	</div>
	<?php
	else:
	?>
	<div class="main-content container">
		<h1><?=$location_name?></h1>
		<br/>
		<div class="row small-spacing">
			<div class="col-lg-3 col-md-6 col-xs-12">
				<div class="box-content bg-primary text-white">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-clock-o"></i>
						<p class="text text-white" style="font-weight:bold;">الصفحات المضافة</p>
						<h2 class="counter" id="screens-total"><?php echo $select_total_screens; ?></h2>
					</div>
				</div>
				<!-- /.box-content -->
			</div>
			<!-- /.col-lg-3 col-md-6 col-xs-12 -->
			<div class="col-lg-3 col-md-6 col-xs-12">
				<div class="box-content bg-info text-white">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-users"></i>
						<p class="text text-white" style="font-weight:bold;">الصفحات المتصلة</p>
						<h2 class="counter" id="screens-connected"><?php echo $select_connected_screens; ?></h2>
					</div>
				</div>
				<!-- /.box-content -->
			</div>
			<!-- /.col-lg-3 col-md-6 col-xs-12 -->
			<!-- /.col-lg-3 col-md-6 col-xs-12 -->
			<div class="col-lg-3 col-md-6 col-xs-12">
				<div class="box-content bg-danger text-white" style="background-color: #AC2941!important;">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-check"></i>
						<p class="text text-white" style="font-weight:bold;">الصفحات المشغولة</p>
						<h2 class="counter" id="screens-busy"><?php echo ($select_total_screens - $select_available_screens); ?></h2>
					</div>
				</div>
				<!-- /.box-content -->
			</div>
			<!-- /.col-lg-3 col-md-6 col-xs-12 -->
			<!-- /.col-lg-3 col-md-6 col-xs-12 -->
			<div class="col-lg-3 col-md-6 col-xs-12">
				<div class="box-content bg-success text-white">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-check"></i>
						<p class="text text-white" style="font-weight:bold;">الصفحات الشاغرة</p>
						<h2 class="counter" id="screens-available"><?php echo $select_available_screens; ?></h2>
					</div>
				</div>
				<!-- /.box-content -->
			</div>

            <div class="col-md-4 col-xs-12">
                <div class="box-content bg-info text-white">
                    <div class="statistics-box with-icon">
                        <i class="ico small fa fa-users"></i>
                        <p class="text text-white" style="font-weight:bold;">عدد الناخبين في السجل</p>
                        <h2 class="counter"><?php echo $total_voters; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-xs-12">
                <div class="box-content text-white <?php echo $total_done_voters != $total_voters_registered ? 'bg-danger' : 'bg-success'; ?>">
                    <div class="statistics-box with-icon">
                        <i class="ico small fa fa-check"></i>
                        <p class="text text-white" style="font-weight:bold;">الناخبين الذين قاموا بالتصويت</p>
                        <h2 class="counter"><?php echo $total_done_voters; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-xs-12">
                <div class="box-content text-white <?php echo $voters_percent >= 50 ? 'bg-success' : 'bg-danger'; ?>">
                    <div class="statistics-box with-icon">
                        <i class="ico small fa fa-percent"></i>
                        <p class="text text-white" style="font-weight:bold;">نسبة الناخبين الذين قاموا بالتصويت</p>
                        <h2 class="counter"><?php echo $voters_percent; ?>%</h2>
                    </div>
                </div>
            </div>
			<!-- /.col-lg-3 col-md-6 col-xs-12 -->
		</div>
		<!-- .row -->

		<div class="row small-spacing">
			<div class="col-xs-12">
				<div class="box-content">
					<h4 class="box-title">صفحات الإقتراع المضافة للمركز</h4>
                    <?php if($isUser): ?>
					<p>
						* 	عند الضغط على زر إزالة سيتم حذف الصفحة من المركز وإيقاف عملية الإقتراع فيها إن وجد
					</p>
					<br />
                    <?php endif; ?>
					<!-- /.box-title -->

					<table id="main" class="table table-striped table-bordered display" style="width:100%">
						<thead>
							<tr>
								<th>#</th>
								<th>إسم الشاشة</th>
								<th>حالة الإتصال</th>
								<th>حالة الإشغال</th>
								<th>آخر تحديث</th>
                                <?php if($isUser): ?>
								<th>الأوامر</th>
                                <?php endif; ?>
							</tr>
						</thead>
						<tbody>
							<?php
                            while($fetch_screens = mysqli_fetch_assoc($select_screens)):
                            ?>
                            <tr id="row-<?php echo $fetch_screens['id']; ?>">
                                <td><?php echo $fetch_screens['id']; ?></td>
                                <td><?php echo $fetch_screens['name']; ?></td>
                                <td><?php echo $fetch_screens['connected']; ?></td>
                                <td><?php echo $fetch_screens['available']; ?></td>
                                <td><?php echo $fetch_screens['updatedAt']; ?></td>
                                <?php if($isUser): ?>
                                <td>
                                    <div class="col-12"><button type="button" class="btn btn-icon btn-icon-left btn-danger btn-xs waves-effect waves-light" style="width: 100px;" data-toggle="modal" data-target="#deleteModal" data-name="<?php echo $fetch_screens['name']; ?>" data-id="<?php echo $fetch_screens['id']; ?>"><i class="ico fa fa-times"></i>حذف</button></div>
                                    <?php
                                    if ($isAdmin):
                                    ?>
                                    <div class="col-12"><button type="button" class="btn btn-icon btn-icon-left btn-warning btn-xs waves-effect waves-light" style="width: 144px;" data-toggle="modal" data-target="#submitScreenModal" data-name="<?php echo $fetch_screens['name']; ?>" data-id="<?php echo $fetch_screens['id']; ?>"><i class="ico fa fa-paper-plane"></i>تسليم الإستمارة</button></div>
                                    <?php
                                    endif;
                                    ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php
                            endwhile;
							?>
						</tbody>
					</table>

				</div>
				<!-- /.box-content -->
			</div>
		</div>
		<span id="timer"></span>
	</div>
	<!-- /.main-content -->
	<?php
	endif; // end location error else
	?>
</div><!--/#wrapper -->
<?php if($isUser): ?>
	<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel">حذف صفحة إقتراع</h4>
				</div>
				<div class="modal-body">
					<h3>هل أنت متأكد من حذف صفحة الإقتراع</h3>
					<h1 class="screen_name"></h1>
				</div>
				<div class="modal-footer">
					<input type="hidden" name="screen-id" class="screen_id" value="">
					<button id="submit" data-dismiss="modal" class="btn btn-icon btn-icon-left btn-danger btn-xs waves-effect waves-light"><i class="ico fa fa-times"></i>حذف</button>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel">إضافة صفحة إقتراع</h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label class="sr-only" for="code">رمز الشاشة</label>
						<input type="text" class="form-control" id="code" placeholder="رمز الشاشة" maxlength="6">
					</div>
					<div class="form-group">
						<label class="sr-only" for="screen-name">إسم الشاشة</label>
						<input type="text" class="form-control" id="screen-name" placeholder="إسم الشاشة">
					</div>
				</div>
				<div class="modal-footer add-form">
					<button id="submit" data-dismiss="modal" class="btn btn-icon btn-icon-left btn-info btn-xs waves-effect waves-light"><i class="ico fa fa-plus"></i>إضافة</button>
				</div>
            </div>
        </div>
    </div>
    <?php if($isAdmin): ?>
	<div class="modal fade" id="submitScreenModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel">تسليم الإستمارة</h4>
				</div>
				<div class="modal-body">
					<h3>هل أنت متأكد من تسليم صفحة الإقتراع</h3>
					<h1 class="screen_name"></h1>
				</div>
				<div class="modal-footer add-form">
					<input type="hidden" name="screen-id" class="screen_id" value="">
					<button id="submit" data-dismiss="modal" class="btn btn-icon btn-icon-left btn-warning btn-xs waves-effect waves-light">
                        <i class="ico fa fa-paper-plane"></i>تسليم
                    </button>
				</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>
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
	<script src="assets/plugin/datatables/extensions/Buttons/js/dataTables.buttons.min.js"></script>
	<script src="assets/plugin/datatables/extensions/Buttons/js/buttons.bootstrap.min.js"></script>
	<script src="assets/scripts/datatables.demo.min.js"></script>
    <script src="<?php echo WS_URL ?>/socket.io/socket.io.js"></script>
	<script>
		var datatable = $('#main').DataTable( {
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

			},
            <?php if(!$isUser): ?>
            bFilter: false,
            <?php endif; ?>
			dom: 'Bfrtip',
			buttons: [
                <?php if($isUser): ?>
				{
					text: 'إضافة شاشة',
					className: 'btn btn-sm btn-success',
					attr: {
						'data-toggle': 'modal',
						'data-target': '#addModal'
					},
					action: function ( e, dt, node, config ) {
						$('#addModal').modal('toggle');
					}
				}
                <?php endif; ?>
			],	
			columns: [
				{ data:"id", visible: false},
				{ data:"name", title: "إسم الشاشة" },
				{ 
					data:"connected", 
					title: "حالة الإتصال" ,
					render: function ( data, type, row, meta ) {
						return type === 'display' ?
						 ( data === true || data === '1' ? '<span class="label label-success">متصل</span>' : '<span class="label label-danger">غير متصل</span>') : 
						 ( data === true || data === '1' ? 'متصل' : 'غير متصل');
					}
				},
				{ 
					data:"available", 
					title: "حالة الإشغال",
					render: function ( data, type, row, meta ) {
						return type === 'display' ?
						 ( data === true || data === '1' ? '<span class="label label-success">متاح</span>' : '<span class="label label-danger">غير متاح</span>') : 
						 ( data === true || data === '1' ? 'متاح' : 'غير متاح');
					}
				},
				{ 
					data:"updatedAt", 
					title: "آخر تحديث",
					render: function ( data, type, row, meta ) {
                        return new Date(data.endsWith('Z') ? data : data + 'Z').toLocaleString();
					}
				},
                <?php if($isUser): ?>
				{ 
					data: null,
					defaultContent: '',
					title: "الأوامر",
					render: function ( data, type, row, meta ) {
						var deleteButton = '<div class="col-12"><button type="button" class="btn btn-icon btn-icon-left btn-danger btn-xs waves-effect waves-light" style="width: 100px;" data-toggle="modal" data-target="#deleteModal" data-id="'+row.id+'" data-name="'+row.name+'"><i class="ico fa fa-times"></i>حذف</button></div>';
                        var submitButton = '<div class="col-12"><button type="button" class="btn btn-icon btn-icon-left btn-warning btn-xs waves-effect waves-light" style="width: 144px;" data-toggle="modal" data-target="#submitScreenModal" data-id="'+row.id+'" data-name="'+row.name+'"><i class="ico fa fa-paper-plane"></i>تسليم الإستمارة</button></div>';
                        var buttons = deleteButton <?php if($isAdmin): ?> + submitButton <?php endif; ?>;
						return type === 'display' ? buttons : '';
					}
				},
                <?php endif; ?>
			]
		});

        <?php if($isUser): ?>
		$('#deleteModal #submit').on('click', function(e){
			var screen =$("#deleteModal").find("input[name='screen-id']").val();
			$.ajax({
				method: "POST",
				url: "<?php echo WS_URL ?>/location/<?php echo $location_id ?>/remove-screen/"+screen,
				xhrFields: {
      				withCredentials: true
   				},
				success: function (response) {
					swal({title: 'تم حذف الشاشة بنجاح', type: 'success'});
				},
				error: function (xhr, ajaxOptions, thrownError) {
					swal({title: 'حدث خطأ اثناء حذف الشاشة، يرجى التأكد من كون الشاشة شاغرة والمحاولة مجدداً', type: 'error'});
				}
			}).done(function() {

			});
    	});

        <?php if($isAdmin): ?>
		$('#submitScreenModal #submit').on('click', function(e){
			var screen =$("#submitScreenModal").find("input[name='screen-id']").val();
			$.ajax({
				method: "POST",
				url: "<?php echo WS_URL ?>/location/<?php echo $location_id ?>/submit-screen/"+screen,
				xhrFields: {
      				withCredentials: true
   				},
				success: function (response) {
					swal({title: 'تم تسليم إستمارة الإقتراع بنجاح', type: 'success'});
				},
				error: function (xhr, ajaxOptions, thrownError) {
					swal({title: 'حدث خطأ اثناء تسليم الإستمارة، يرجى التأكد من كون الشاشة شاغرة والمحاولة مجدداً', type: 'error'});
				}
			}).done(function() {

			});
    	});
        <?php endif; ?>

		$('#addModal #submit').on('click', function(e){
			var code = $("#addModal #code").val();
			var name = $("#addModal #screen-name").val();
			$.ajax({
				method: "POST",
				url: "<?php echo WS_URL ?>/location/<?php echo $location_id ?>/add-screen/"+code,
				xhrFields: {
      				withCredentials: true
   				},
				data: {
					name
				},
				success: function (response) {
					swal({title: 'تم إضافة الشاشة بنجاح', type: 'success'});
				},
				error: function (xhr, ajaxOptions, thrownError) {
					swal({title: 'لم تتم إضافة الشاشة، يرجى التأكد من كون الشاشة متصلة بالشبكة والمحاولة مجدداً', type: 'error'});
				}
			}).done(function() {});
    	});

		$('#deleteModal').on('show.bs.modal', function (event) {
			var button = $(event.relatedTarget);
			var screenName = button.data('name');
			var screenId = button.data('id');
			var modal = $(this);
			modal.find('.screen_name').text('('+screenName+')');
			modal.find('input.screen_id').val(screenId);
		});

		$('#submitScreenModal').on('show.bs.modal', function (event) {
			var button = $(event.relatedTarget);
			var screenName = button.data('name');
			var screenId = button.data('id');
			var modal = $(this);
			modal.find('.screen_name').text('('+screenName+')');
			modal.find('input.screen_id').val(screenId);
		});

		$('#addModal').on('show.bs.modal', function (event) {
			var modal = $(this)
			modal.find('#code').val('');
			modal.find('#screen-name').val('');
		});
        <?php endif; ?>

		var socket = io("<?php echo WS_URL ?>/users", {
			withCredentials: true,
			auth: {
				locationId: <?php echo $location_id ?>
			}
		});
		socket.on("screens-list", function(data) {
			console.log(data);
			if(data.screens && Array.isArray(data.screens)) {
				try {
					datatable.clear();
          			datatable.rows.add(data.screens);
          			datatable.draw();
					$("#screens-total").text(data.screens.length);
					var connectedScreens = data.screens.filter(function(s, i) {
						return s.connected;
					});
					var availableScreens = data.screens.filter(function(s, i) {
						return s.available;
					});
					$("#screens-connected").text(connectedScreens.length);
					$("#screens-busy").text(data.screens.length - availableScreens.length);
					$("#screens-available").text(availableScreens.length);
				} catch(e) {
					console.log(e);
				}
			}
		});
	</script>

	<script src="assets/scripts/main.min.js"></script>
	<script src="assets/scripts/horizontal-menu.min.js"></script>
</body>
</html>
