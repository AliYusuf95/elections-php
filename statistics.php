<?php
include 'config.php';
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["admin"]) || $_SESSION["admin"] !== true){
    header("location: vlogin.php");
    exit;
}

$isAllLocationsClosed = mysqli_num_rows(mysqli_query($con,"SELECT id FROM locations WHERE open = true")) == 0;

if($isAllLocationsClosed) {

	$select_voters = mysqli_query($con,"SELECT * FROM voters");
	$total_voters = mysqli_num_rows($select_voters);

	$pending_voters = mysqli_query($con,"SELECT * FROM voters WHERE status = '0'");
	$total_pending_voters = mysqli_num_rows($pending_voters);

	// $rejected_voters = mysqli_query($con,"SELECT * FROM voters WHERE status = '2'");
	// $total_rejected_voters = mysqli_num_rows($rejected_voters);

	$select_total_votes = mysqli_query($con,'SELECT sum(votes) FROM candidates');
	$total_votes = mysqli_fetch_row($select_total_votes);
	$total_votes_result = $total_votes[0];

	$done_voters = mysqli_query($con,"SELECT * FROM voters WHERE status = '3'");
	$total_done_voters = mysqli_num_rows($done_voters);

	$total_candidates = mysqli_num_rows(mysqli_query($con,"SELECT id FROM candidates"));

// 	$matam_emamali = mysqli_query($con,"SELECT * FROM voters WHERE fromwhere = 'مأتم الامام علي عليه السلام'");
// 	$total_matam_emamali = mysqli_num_rows($matam_emamali);

// 	$matam_baqer = mysqli_query($con,"SELECT * FROM voters WHERE fromwhere = 'مأتم الامام الباقر عليه السلام'");
// 	$total_matam_baqer = mysqli_num_rows($matam_baqer);

// 	$matam_sadiq = mysqli_query($con,"SELECT * FROM voters WHERE fromwhere = 'مأتم الامام الصادق عليه السلام'");
// 	$total_matam_sadiq = mysqli_num_rows($matam_sadiq);

// 	$matam_redha = mysqli_query($con,"SELECT * FROM voters WHERE fromwhere = 'مأتم الامام الرضا عليه السلام'");
// 	$total_matam_redha = mysqli_num_rows($matam_redha);

// 	$matam_taweela = mysqli_query($con,"SELECT * FROM voters WHERE fromwhere = 'مأتم الطويلة'");
// 	$total_matam_taweela = mysqli_num_rows($matam_taweela);

// 	$matam_general = mysqli_query($con,"SELECT * FROM voters WHERE fromwhere = 'قاطني قرية بوري'");
// 	$total_matam_general = mysqli_num_rows($matam_general);

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
	<div class="header-top">
		<div class="container">
			<div class="pull-right">
				<a class="logo" style="font-weight: bold;">نظام التصويت الإلكتروني</a>
			</div>
			<!-- /.pull-right -->

		</div>
		<!-- /.container -->
	</div>
	<!-- /.header-top -->
	<nav class="nav-horizontal">
		<button type="button" class="menu-close hidden-on-desktop js__close_menu"><i class="fa fa-times"></i><span>CLOSE</span></button>
		<div class="container">

			<ul class="menu">
					<li>
						<a href="vadmin"><i class="ico mdi mdi-home"></i><span style="font-weight: bold;">المراكز</span></a>
					</li>
					<li>
						<a href="vusers"><i class="ico mdi mdi-account"></i><span style="font-weight: bold;">الأعضاء</span></a>
					</li>
					<li class="current">
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



<div id="wrapper">
	<div class="main-content container">
		<?php 
		if(!$isAllLocationsClosed):
		?>
		<br />
		<div class="alert alert-danger">
			<strong>خطأ</strong> لا يمكن عرض النتائج إلا في حال إغلاق جميع المراكز
		</div>
		<?php 
		else:
		?>
		<div class="row small-spacing">

			<div class="col-md-6 col-xs-12">
				<div class="box-content bg-success text-white" style="background-color: #AC2941!important;">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-users"></i>
						<p class="text text-white" style="font-weight:bold;">عدد الناخبين</p>
						<h2 class="counter"><?php echo $total_voters; ?></h2>
					</div>
				</div>
			</div>

      		<!-- <div class="col-lg-3 col-md-6 col-xs-12">
				<div class="box-content bg-danger text-white" style="background-color: #AC2941!important;">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-times"></i>
						<p class="text text-white" style="font-weight:bold;">الناخبين المرفوضين</p>
						<h2 class="counter"><-?php echo $total_rejected_voters; ?></h2>
					</div>
				</div>
			</div>

			<div class="col-lg-3 col-md-6 col-xs-12">
				<div class="box-content bg-warning text-white" style="background-color: #AC2941!important;">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-check"></i>
						<p class="text text-white" style="font-weight:bold;">عدد الأصوات</p>
						<h2 class="counter"></h2>
					</div>
				</div>
			</div> -->


			<div class="col-md-6 col-xs-12">
				<div class="box-content bg-danger text-white" style="background-color: #AC2941!important;">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-users"></i>
						<p class="text text-white" style="font-weight:bold;">عدد المرشحين</p>
						<h2 class="counter"><?php echo $total_candidates; ?></h2>
					</div>
				</div>
			</div>



		</div>
		<!-- .row -->

		<div class="row small-spacing">
			<div class="col-xs-12">
				<div class="box-content">
					<h4 class="box-title">النتائج</h4>
					<!--<h5>سيتم عرض النتائج بعد إنتهاء المدة الزمنية للتصويت</h5>-->
					<br />
					<!-- /.box-title -->

					<table id="main" class="table table-striped table-bordered display" style="width:100%">
						<thead>
							<tr>
								<th></th>
								<th>الإسم</th>
								<th>عدد الأصوات</th>
							</tr>
						</thead>
						<tbody>
							<?php
							
							$select_candidates = mysqli_query($con, "SELECT * FROM candidates ORDER BY name");
                            
							while($fetch_candidates = mysqli_fetch_assoc($select_candidates)){

							echo '<tr>
									<td></td>
									<td>'.$fetch_candidates['name'].'</td>
									<td>'.$fetch_candidates['votes'].'</td>
									</tr>';
							}
							?>
						</tbody>
					</table>


				</div>
        <!-- div class="box-content">
					<h4 class="box-title">إجمالي الناخبين حسب المأتم</h4>

          <div class="col-lg-4 col-md-6">
  				<div class="box-contact">
  					<h3 class="name margin-top-10">حسينية الإمام الباقر</h3>
  					<h4 class="job"><?php //echo $total_matam_baqer;?></h4>
  				</div>
			    </div>

          <div class="col-lg-4 col-md-6">
  				<div class="box-contact">
  					<h3 class="name margin-top-10">مأتم الامام علي</h3>
  					<h4 class="job"><?php //echo $total_matam_emamali;?></h4>
  				</div>
			    </div>

          <div class="col-lg-4 col-md-6">
  				<div class="box-contact">
  					<h3 class="name margin-top-10">مأتم الامام الصادق</h3>
  					<h4 class="job"><?php //echo $total_matam_sadiq;?></h4>
  				</div>
			    </div>

          <div class="col-lg-4 col-md-6">
  				<div class="box-contact">
  					<h3 class="name margin-top-10">مأتم الامام الرضا</h3>
  					<h4 class="job"><?php //echo $total_matam_redha;?></h4>
  				</div>
			    </div>

          <div class="col-lg-4 col-md-6">
  				<div class="box-contact">
  					<h3 class="name margin-top-10">مأتم الطويلة</h3>
  					<h4 class="job"><?php // echo $total_matam_taweela;?></h4>
  				</div>
			    </div>

          <div class="col-lg-4 col-md-6">
  				<div class="box-contact">
  					<h3 class="name margin-top-10">قاطني قرية بوري</h3>
  					<h4 class="job"><?php //echo $total_matam_general;?></h4>
  				</div>
			    </div>

				</div>
			</div -->
		</div>
		<?php 
		endif;
		?>
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
	var t = $('#main').DataTable( {
		pageLength: 25,
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
    t.on('order.dt search.dt', function () {
        let i = 1;
 
        t.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
            this.data(i++);
        });
    }).draw();
	</script>

	<script src="assets/scripts/main.min.js"></script>
	<script src="assets/scripts/horizontal-menu.min.js"></script>
</body>
</html>
