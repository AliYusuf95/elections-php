<?php
global $con;
include 'config.php';

// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["admin"]) || $_SESSION["admin"] !== true){
    header("location: vlogin.php");
    exit;
}

$logged_user = $_SESSION['username'];

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
                <!-- /.pull-right -->
                <div class="pull-left">
                    <a class="logo" style="font-size: 15px;">مرحبا <?php echo $logged_user; ?></a>
                </div>

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
                    <li>
                        <a href="vusers"><i class="ico mdi mdi-account"></i><span style="font-weight: bold;">الأعضاء</span></a>
                    </li>
                    <li>
                        <a href="vcandidates"><i class="ico mdi mdi-account-multiple"></i><span style="font-weight: bold;">المرشحون</span></a>
                    </li>
                    <li>
                        <a href="statistics"><i class="ico mdi mdi-chart-bar"></i><span style="font-weight: bold;">الإحصائيات</span></a>
                    </li>
                    <li class="current">
                        <a class="text-primary" href="log"><i class="ico mdi mdi-menu"></i><span style="font-weight: bold;">سجل النظام</span></a>
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
		<!-- .row -->

		<div class="row small-spacing">
			<div class="col-xs-12">
				<div class="box-content">
					<h4 class="box-title">سجل النظام</h4>
					<br />

					<table id="main" class="table table-striped table-bordered display" style="width:100%">
						<thead>
							<tr>
								<th>#</th>
								<th>التفاصيل</th>
								<th>المستخدم</th>
                <th>الوقت</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$select_log = mysqli_query($con, "SELECT * FROM system_log");
							while($fetch_log = mysqli_fetch_assoc($select_log)){

							echo '<tr>
									<td>'.$fetch_log['id'].'</td>
									<td>'.$fetch_log['title'].'</td>
									<td>'.$fetch_log['username'].'</td>
									<td>'.$fetch_log['created_at'].'</td>
									</tr>';
							}
							?>
						</tbody>
					</table>

				</div>
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
		pageLength: 100,
		ordering: true,
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
	</script>

	<script src="assets/scripts/main.min.js"></script>
	<script src="assets/scripts/horizontal-menu.min.js"></script>
</body>
</html>
