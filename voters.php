<?php
global $con;
include 'config.php';
// Initialize the session
session_start();

$location_id = 0;
$location_name = '';
$location_error = null;
$isAdmin = false;

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: vlogin.php");
    exit;
} else if(isset($_SESSION["user"]) && $_SESSION["user"] === true) {
    $select_user_Location = mysqli_query($con, "SELECT locationId FROM users WHERE id = " . $_SESSION["id"]);
    $location_id = mysqli_fetch_row($select_user_Location)[0];
} else if (isset($_SESSION["admin"]) && $_SESSION["admin"] === true && isset($_GET["l"])) {
    $isAdmin = true;
    $location_id = $_GET["l"];
} else {
    header("location: vadmin.php");
    exit;
}

function getLocationInfo() {
    global $con, $location_id, $location_name, $location_error;

    if ($location_id == 'all') {
        $location_name = 'بيانات الناخبين في جميع المراكز';
        return;
    }
    
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

if(!isset($location_error)) {
    if ($location_id == 'all') {
        $stmt = $con->prepare("SELECT v.id AS id, v.name AS name, v.fromwhere AS fromwhere, v.cpr AS cpr, v.status AS status, v.updatedAt AS updatedAt,
	 u.name AS user_name FROM voters_data v LEFT JOIN users u ON v.userId = u.id");
    } else {
        $stmt = $con->prepare("SELECT v.id AS id, v.name AS name, v.fromwhere AS fromwhere, v.cpr AS cpr, v.status AS status, v.updatedAt AS updatedAt,
	 u.name AS user_name FROM voters_data v LEFT JOIN users u ON v.userId = u.id WHERE v.locationId = ?");
        $stmt->bind_param("i", $location_id);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $votersData = [];
    while ($row = $res->fetch_assoc()) {
        $votersData[$row['cpr']] = $row;
    }
    $stmt->close();

    if ($location_id == 'all') {
        $stmt = $con->prepare("SELECT v.id AS id, v.name AS name, v.fromwhere AS fromwhere, v.cpr AS cpr, 0 AS status, v.updatedAt AS updatedAt, null AS user_name FROM voters v");
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if (!isset($votersData[$row['cpr']])) {
                $votersData[$row['cpr']] = $row;
            }
        }
        $stmt->close();
    }

    $total_voters = count($votersData);

    if ($location_id == 'all') {
        $stmt = $con->prepare("SELECT id FROM voters_data WHERE status = '2'");
    } else {
        $stmt = $con->prepare("SELECT id FROM voters_data WHERE status = '2' AND locationId = ?");
        $stmt->bind_param("i", $location_id);
    }
    $stmt->execute();
    $stmt->store_result();
    $pending_voters = $stmt->num_rows;
    $stmt->close();

    if ($location_id == 'all') {
        $stmt = $con->prepare("SELECT id FROM voters_data WHERE status = '3'");
    } else {
        $stmt = $con->prepare("SELECT id FROM voters_data WHERE status = '3' AND locationId = ?");
        $stmt->bind_param("i", $location_id);
    }
    $stmt->execute();
    $stmt->store_result();
    $done_voters = $stmt->num_rows;
    $stmt->close();
}

$logged_user = $_SESSION['username'];
$current_time = date("h:i:s A");


?>
<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<meta name="description" content="">
	<meta name="author" content="">

	<title>بيانات الناخبين | نظام التصويت الإلكتروني</title>

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
		-webkit-transform:scale(2.5); /* or some other value */
    transform:scale(2.5);
	}
	@media (max-width: 1024px) {
		.nav-container.container {
			width: unset;
		}
	}
	</style>
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
					<?php 
						if($isAdmin == true):
						?>
							<li>
								<a <?php if($location_id != 'all'): ?> class="text-primary" <?php endif; ?> href="vadmin"><i class="ico mdi mdi-home"></i><span style="font-weight: bold;">المراكز</span></a>
							</li>
							<li>
								<a href="vusers"><i class="ico mdi mdi-account"></i><span style="font-weight: bold;">الأعضاء</span></a>
							</li>
                            <li>
                                <a href="vcandidates"><i class="ico mdi mdi-account-multiple"></i><span style="font-weight: bold;">المرشحون</span></a>
                            </li>
                            <li>
                                <a <?php if($location_id == 'all'): ?> class="text-primary" <?php endif; ?> href="voters?l=all"><i class="ico mdi mdi mdi-account-card-details"></i><span style="font-weight: bold;">الناخبين</span></a>
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
							<li class="current">
								<a href="vregistration"><i class="ico mdi mdi-home"></i><span style="font-weight: bold;">التسجيل</span></a>
							</li>
							<li>
								<a href="vscreens"><i class="ico mdi mdi-monitor-multiple"></i><span style="font-weight: bold;">صفحات الإقتراع</span></a>
							</li>
							<li>
								<a class="text-primary" href="voters"><i class="ico mdi mdi-account"></i><span style="font-weight: bold;">بيانات الناخبين</span></a>
							</li>
						<?php
						endif;
						?>
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
		</br>

		<div class="row small-spacing">
            <div class="col-lg-12 col-md-12 col-xs-12">
                <div class="box-content bg-info text-white">
                    <div class="statistics-box with-icon">
                        <i class="ico small fa fa-users"></i>
                        <p class="text text-white" style="font-weight:bold;">إجمالي الناخبين</p>
                        <h2 class="counter"><?php echo $total_voters ?: 0; ?></h2>
                    </div>
                </div>
                <!-- /.box-content -->
            </div>
				<!-- /.col-md-6 col-xs-12 -->
				<div class="col-md-6 col-xs-12">
					<div class="box-content bg-danger text-white" style="background-color: #333333!important;">
						<div class="statistics-box with-icon">
							<i class="ico small fa fa-times"></i>
							<p class="text text-white" style="font-weight:bold;">بإنتظار التصويت</p>
							<h2 class="counter"><?php echo $pending_voters ?: 0; ?></h2>
						</div>
					</div>
					<!-- /.box-content -->
				</div>
				<!-- /.col-md-6 col-xs-12 -->
				<div class="col-md-6 col-xs-12">
					<div class="box-content bg-success text-white">
						<div class="statistics-box with-icon">
							<i class="ico small fa fa-check"></i>
							<p class="text text-white" style="font-weight:bold;">تم التصويت</p>
							<h2 class="counter"><?php echo $done_voters ?: 0; ?></h2>
						</div>
					</div>
					<!-- /.box-content -->
				</div>
			</div>
			<!-- .row -->

			<div class="row small-spacing">
				<div class="col-xs-12">
					<div class="box-content">
						<h4 class="box-title">
                            <?php if ($location_id != 'all'): ?>
                            الناخبين المسجلين في هذا المركز
                            <?php else: ?>
                            قائمة الناخبين
                            <?php endif; ?>
                        </h4>
						<br />
						<!-- /.box-title -->
						<table id="main" class="table table-striped table-bordered display" style="width:100%">
							<thead>
								<tr>
									<th>#</th>
									<th>إسم الناخب</th>
									<th>الرقم الشخصي</th>
									<th>المأتم</th>
									<th>حالة التصويت</th>
									<th>المستخدم</th>
									<th>آخر تحديث</th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ($votersData as $voter):
								?>
                                <tr id="row-<?php echo $voter['id']; ?>">
                                    <td><?php echo $voter['id']; ?></td>
                                    <td><?php echo $voter['name']; ?></td>
                                    <td><?php echo $voter['cpr']; ?></td>
                                    <td><?php echo $voter['fromwhere']; ?></td>
                                    <?php if($voter['status'] == 3): ?>
                                    <td class="text-success">تم التصويت</td>
                                    <?php elseif($voter['status'] == 2): ?>
                                    <td class="text-warning">بإنتظار التصويت</td>
                                    <?php else: ?>
                                    <td>لم يصوت</td>
                                    <?php endif; ?>
                                    <td><?php echo $voter['user_name']; ?></td>
                                    <td><?php echo $voter['updatedAt']; ?></td>
                                </tr>
                                <?php
								endforeach;
								?>
							</tbody>
						</table>

					</div>
					<!-- /.box-content -->
				</div>
			</div>

		</div>
		<!-- /.main-content -->
	<?php
	endif; // end location error else
	?>
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
	$('#main').DataTable({
		pageLength: 25,
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
		columns: [
				{ data:"id", visible: false},
				{ data:"name", title: "إسم الناخب" },
				{ data:"cpr", title: "الرقم الشخصي" },
				{ data:"fromwhere", title: "المأتم" },
				{ data:"status", title: "حالة التصويت" },
				{ data:"user_name", title: "المستخدم" },
				{ 
					data:"updatedAt", 
					title: "آخر تحديث",
					render: function ( data, type, row, meta ) {
                        if (data === '0000-00-00 00:00:00') {
                            return '-';
                        }
						return new Date(data + 'Z').toISOString().replace('T', ' ').substring(0, 16);
					}
				},
			]
	});
	</script>



	<script src="assets/scripts/main.min.js"></script>
	<script src="assets/scripts/horizontal-menu.min.js"></script>
</body>
</html>
