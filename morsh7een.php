<?php
global $con;
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
	<img src="logo.png" alt="logo" width="90" />
	<h3>نظام التصويت الإلكتروني</h3>
	<h4>عرض المرشحين</h4>
	<hr />

	<div class="main-content container">
		<div class=”float-button”></div>

        <?php

        // build candidates avatars with names grouped by their position
        $select_positions = mysqli_query($con, "SELECT p.name positionName , c.name name, c.img img FROM positions p JOIN candidates c ON p.id = c.positionId ORDER BY p.order, c.id");
        $positions = [];
        while ($fetch_positions = mysqli_fetch_assoc($select_positions)) {
            $positions[$fetch_positions['positionName']][] = ['name' => $fetch_positions['name'], 'img' => $fetch_positions['img']];
        }
        for ($i = 0; $i < count($positions); $i++):
            ?>
            <div class="row small-spacing">
                <div class="col-xs-12">
                    <div class="box-content"
                         style="background: initial; border: initial; -webkit-box-shadow: none; box-shadow: none;">
                        <h2 class="box-title" style="font-size: 30px;"><?php echo array_keys($positions)[$i]; ?></h2>
                        <div class="row">
                            <?php
                            $colSize = count($positions[array_keys($positions)[$i]]) % 4 === 0 ? 3 : 4;
                            for ($j = 0; $j < count($positions[array_keys($positions)[$i]]); $j++):
                                ?>
                                <div class="col-lg-<?php echo $colSize; ?> col-md-6">
                                    <div class="box-contact">
                                        <img src="<?php echo $positions[array_keys($positions)[$i]][$j]['img']; ?>"
                                             alt="" class="avatar">
                                        <h3 class="name margin-top-10">
                                            <?php echo $positions[array_keys($positions)[$i]][$j]['name']; ?>
                                        </h3>
                                    </div>
                                </div>
                            <?php
                            endfor;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            if ($i < count($positions) - 1) {
                echo '<hr />';
            }
        endfor;
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


	<script src="assets/scripts/main.min.js"></script>
	<script src="assets/scripts/horizontal-menu.min.js"></script>

</body>
</html>
