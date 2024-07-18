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
$isAllLocationsClosed = mysqli_num_rows(mysqli_query($con,"SELECT id FROM locations WHERE open = true")) == 0;

if ($isAllLocationsClosed) {

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

    $stmt = $con->prepare("SELECT COUNT(id) as total_candidates FROM candidates");
    $stmt->execute();
    $stmt->bind_result($total_candidates);
    $stmt->fetch();
    $stmt->close();

    $voters_percent = $total_voters_registered > 0 ? round(($total_done_voters / $total_voters) * 100, 2) : 0;

    $localTime = new DateTime('now');
    $utcOffset = $localTime->getOffset() / 3600;

    $stmt = $con->prepare("SELECT COUNT(id) as total_voters_done, HOUR(updatedAt) as hour FROM voters_data WHERE status = 3 GROUP BY HOUR(updatedAt)");
    $stmt->execute();
    $stmt->bind_result($total_voters_done, $hour);
    $voters_done_volume_per_hour = [];
    while ($stmt->fetch()) {
        $hour += $utcOffset;
        $hour = $hour < 0 ? 24 + $hour : $hour;
        $voters_done_volume_per_hour[$hour] = $total_voters_done;
    }
    $stmt->close();

}
if ($isAllLocationsClosed && isset($_POST['export-csv'])) {
    // get all positions and candidates
    $positions = [];
    $stmt = $con->prepare("SELECT p.id as positionId, p.name as positionName, c.id as candidateId, c.name as candidateName FROM positions p LEFT JOIN candidates c ON p.id = c.positionId");
    $stmt->execute();
    $stmt->bind_result($positionId, $positionName, $candidateId, $candidateName);
    while ($stmt->fetch()) {
        if (empty($positions[$positionId])) {
            $positions[$positionId] = [
                'positionId' => $positionId,
                'positionName' => $positionName,
                'candidatesIds' => [],
                'candidates' => []
            ];
        }
        $positions[$positionId]['candidatesIds'][] = $candidateId;
        $positions[$positionId]['candidates'][$candidateId] = [
            'candidateId' => $candidateId,
            'candidateName' => $candidateName
        ];
        $positions[$positionId]['submissions'][$candidateId] = [];
    }
    $stmt->close();
    $positionIds = array_keys($positions);

    // get all submissions
    $stmt = $con->prepare("SELECT id, submission from voting_submissions");
    $stmt->execute();
    $submissionCount = 0;
    $stmt->bind_result($submissionId, $submission);
    while ($stmt->fetch()) {
        $submission = json_decode($submission, true);
        $submissionPositions = [];
        $submissionCount++;
        foreach ($submission as $submissionPosition) {
            $submissionPositions[] = $submissionPosition['positionId'];
            foreach ($submissionPosition['candidates'] as $candidate) {
                $positions[$submissionPosition['positionId']]['submissions'][$candidate['id']][] = 1;
            }
            $positionCandidates = $positions[$submissionPosition['positionId']]['candidatesIds'];
            $submissionCandidates = array_map(function ($candidate) {return $candidate['id'];}, $submissionPosition['candidates']);
            $emptyCandidates = array_diff($positionCandidates, $submissionCandidates);
            foreach ($emptyCandidates as $emptyCandidate) {
                $positions[$submissionPosition['positionId']]['submissions'][$emptyCandidate][] = 0;
            }
        }
        $emptyPositions = array_diff($positionIds, $submissionPositions);
        foreach ($emptyPositions as $emptyPosition) {
            foreach ($positions[$emptyPosition]['candidatesIds'] as $candidateId) {
                $positions[$emptyPosition]['submissions'][$candidateId][] = 0;
            }
        }
    }
    $stmt->close();

    // create zip file that contains csv for each position with data from submissions
    $options = new ZipStream\Option\Archive();
    $options->setSendHttpHeaders(true);
    $zip = new ZipStream\ZipStream('results.zip', $options);

    $fiveMBs = 5 * 1024 * 1024;
    foreach ($positions as $position) {
        $csv = fopen("php://temp/maxmemory:$fiveMBs", 'w+');
        $candidateNames = array_map(function ($candidate) {return $candidate['candidateName'];}, $position['candidates']);
        fputcsv($csv, $candidateNames);
        for ($i = 0; $i < $submissionCount; $i++) {
            $row = [];
            foreach ($position['submissions'] as $candidateId => $submissions) {
                $row[] = $submissions[$i];
            }
            fputcsv($csv, $row);
        }
        rewind($csv);
        $zip->addFileFromStream($position['positionName'].'.csv', $csv);
        fclose($csv);
    }

    // send zip file
    try {
        $zip->finish();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    exit;
}

if ($isAllLocationsClosed && isset($_POST['export-doc'])) {
    require_once 'ExportToWord.inc.php';

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

    $stmt = $con->prepare("SELECT COUNT(id) as total_submissions FROM voting_submissions");
    $stmt->execute();
    $stmt->bind_result($total_submissions);
    $stmt->fetch();
    $stmt->close();

    $stmt = $con->query("SELECT id, name, maxVotes FROM positions ORDER BY `order`");
    $positions = $stmt->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $con->query("SELECT p.id as positionId, p.name as positionName, c.name as candidateName, COALESCE(r.votes, 0) as votes FROM `positions` p
                                LEFT JOIN `candidates` c ON p.id = c.positionId 
                                LEFT JOIN `voting_results` r ON c.id = r.candidateId
    ORDER BY p.order, r.votes DESC, c.id");
    $results = array_reduce($stmt->fetch_all(MYSQLI_ASSOC), function (array $accumulator, array $element) {
        $accumulator[$element['positionId']][] = $element;
        return $accumulator;
    }, []);
    $stmt->close();

    $html = "
    <html>
    <body dir='rtl'>
        <h3>النتائج العامة:</h3>
        <ol>
            <li>عدد الناخبين الذين يحق لهم التصويت $total_voters ناخب</li>
            <li>عدد الناخبين الذين شاركوا في الانتخابات $total_voters_registered ناخب</li>".
            implode(array_map(function ($position) use ($total_submissions) {
                return "<li>عدد البطائق الصحيحة ل{$position['name']} $total_submissions بطاقة</li>".
                    "<li>عدد البطائق الملغية ل{$position['name']} 0 بطاقة</li>";
            }, $positions))
        . "
        </ol>
        <br/>
        ".
        implode(array_map(function ($position) use ($results) {
            return "<br/><h4 class='underline'> نتائج منصب {$position['name']}</h4>
                    وقد ترشح للمنصب:
                    <ol>"
                    .implode(array_map(function ($candidate) use ($position) {
                        return "<li>{$candidate['candidateName']}، وحصل على {$candidate['votes']} صوتًا</li>";
                    }, $results[$position['id']]))
                    ."</ol>
                    ";
        }, $positions))
        ."
        <br/>
        <h3>وعليه وفقاً للنظام الانتخابي للمأتم:</h3>
        <ol>
            <li>يشغل منصب رئيس المأتم و رئيس مجلس الإدارة المرشح الحائز على الغالبية المطلقة (50%+1) من الأصوات الصحيحة للمقترعين.</li>
            <li>يشغل مراكز عضوية مجلس الإدارة الثمانية المرشحون الحاصلون على أعلى عدد من الأصوات الصحيحة للمقترعين.</li>
        </ol>
        <br/>
        <h3>تكون النتائج الأولية لانتخابات الدورة الحادية عشر على النحو التالي:</h3>".
        implode(array_map(function ($position) use ($results) {
            $result = $position['maxVotes'] == 1 ? "<li>{$results[$position['id']][0]['candidateName']}</li>" : implode(array_map(function ($candidate) use ($position) {
                return "<li>{$candidate['candidateName']}</li>";
            }, array_slice($results[$position['id']], 0, $position['maxVotes'])));
            return "<br/>
                    <h3 class='underline'>{$position['name']}</h3>
                    <ol>$result</ol>
                    ";
        }, $positions))
        ."
    </body>
    </html>
    ";
    $css = '<style type = "text/css">html body * {direction: rtl;} .underline{ text-decoration: underline; }</style>';
    $fileName = 'php://output';
    header("Content-Type: application/vnd.ms-word");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("content-disposition: attachment;filename=Report.doc");
    ExportToWord::htmlToDoc($html, $css, $fileName);
    exit();
}


// result by submissions
//"
//SELECT id, positionId, positionName, candidateId, candidateName from voting_submissions
//CROSS JOIN JSON_TABLE(
//    submission,
//    '$[*]' COLUMNS(
//        positionId INT PATH '$.positionId',
//        positionName VARCHAR(255) PATH '$.positionName',
//        candidates JSON PATH '$.candidates'
//    )
//) AS positions
//CROSS JOIN JSON_TABLE(
//    positions.candidates,
//    '$[*]' COLUMNS(
//        candidateId INT PATH '$.id',
//        candidateName VARCHAR(255) PATH '$.name'
//    )
//) AS candidates
//GROUP BY id, positionId, positionName, candidateId, candidateName;
//"

// results from submissions
//"
//(SELECT CONVERT(positions.positionId USING utf8) as positionId, CONVERT(positions.positionName USING utf8) as positionName, CONVERT(candidates.candidateId USING utf8) as candidateId, CONVERT(candidates.candidateName USING utf8) as candidateName, count(*) as count from voting_submissions
//CROSS JOIN JSON_TABLE(
//              submission,
//              '$[*]' COLUMNS(
//                positionId INT PATH '$.positionId',
//                positionName VARCHAR(255) PATH '$.positionName',
//                candidates JSON PATH '$.candidates'
//              )
//           ) AS positions
//CROSS JOIN JSON_TABLE(
//    positions.candidates,
//              '$[*]' COLUMNS(
//                candidateId INT PATH '$.id',
//                candidateName VARCHAR(255) PATH '$.name'
//              )
//           ) AS candidates
//group BY positions.positionId, positions.positionName, candidates.candidateId, candidates.candidateName
//UNION
//SELECT p.id as positionId, p.name as positionName, c.id as candidateId, c.name as candidateName, 0 as count
//FROM candidates c
//JOIN positions p ON p.id = c.positionId
//group BY p.id, c.id)
//ORDER BY `positionId` + 0, `candidateId`+ 0 ASC
//";

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

    <!-- Data Tables -->
    <link rel="stylesheet" href="assets/plugin/datatables/media/css/dataTables.bootstrap.min.css">

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
        .ct-label.ct-horizontal.ct-start {
            justify-content: end;
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
                        <a href="voters?l=all"><i class="ico mdi mdi mdi-account-card-details"></i><span style="font-weight: bold;">الناخبين</span></a>
                    </li>
                    <li class="current">
                        <a class="text-primary" href="statistics"><i class="ico mdi mdi-chart-bar"></i><span style="font-weight: bold;">الإحصائيات</span></a>
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
                <div class="box-content bg-purple text-white">
                    <div class="statistics-box with-icon">
                        <i class="ico small fa fa-users"></i>
                        <p class="text text-white" style="font-weight:bold;">عدد المرشحين</p>
                        <h2 class="counter"><?php echo $total_candidates; ?></h2>
                    </div>
                </div>
            </div>

			<div class="col-md-6 col-xs-12">
				<div class="box-content bg-info text-white">
					<div class="statistics-box with-icon">
						<i class="ico small fa fa-users"></i>
						<p class="text text-white" style="font-weight:bold;">عدد الناخبين في السجل</p>
						<h2 class="counter"><?php echo $total_voters; ?></h2>
					</div>
				</div>
			</div>

            <div class="col-md-6 col-xs-12">
                <div class="box-content bg-primary text-white">
                    <div class="statistics-box with-icon">
                        <i class="ico small fa fa-file-text"></i>
                        <p class="text text-white" style="font-weight:bold;">الناخبين المسجلين للتصويت</p>
                        <h2 class="counter"><?php echo $total_voters_registered; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xs-12">
                <div class="box-content text-white <?php echo $total_done_voters != $total_voters_registered ? 'bg-danger' : 'bg-success'; ?>">
                    <div class="statistics-box with-icon">
                        <i class="ico small fa fa-check"></i>
                        <p class="text text-white" style="font-weight:bold;">الناخبين الذين قاموا بالتصويت</p>
                        <h2 class="counter"><?php echo $total_done_voters; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-xs-12">
                <div class="box-content text-white <?php echo $voters_percent >= 50 ? 'bg-success' : 'bg-danger'; ?>">
                    <div class="statistics-box with-icon">
                        <i class="ico small fa fa-percent"></i>
                        <p class="text text-white" style="font-weight:bold;">نسبة الناخبين الذين قاموا بالتصويت</p>
                        <h2 class="counter"><?php echo $voters_percent; ?>%</h2>
                    </div>
                </div>
            </div>

		</div>
		<!-- .row -->

        <div class="row small-spacing">
            <div class="col-xs-12">
                <div class="box-content">
                    <h4 class="box-title">استخراج البيانات</h4>
                    <div class="box-body">
                        <form action="statistics.php" method="post">
                            <div class="form-group">
                                <button type="submit" name="export-csv" class="btn btn-primary">بطاقات الإقتراع</button>
                                <button type="submit" name="export-doc" class="btn btn-primary btn-bordered">نتائج التصويت</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <!-- voters volume chart        -->
        <div class="row small-spacing">
            <div class="col-xs-12">
                <div class="box-content">
                    <h4 class="box-title">عدد الناخبين الذين قاموا بالتصويت حسب الساعة</h4>
                    <div class="chart-container">
                        <div class="chart has-fixed-height" id="voters-volume-chart"></div>
                    </div>
                </div>
            </div>
        </div>

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
								<th>المنصب</th>
								<th>اسم المنصب</th>
                                <th>ترتيب المنصب</th>
                            </tr>
						</thead>
						<tbody>
							<?php
                            $select_candidates_by_position = mysqli_query($con, "SELECT p.id positionId, p.order positionOrder, p.name positionName, c.name as name, IFNULL(v.votes, 0) as votes FROM candidates c JOIN positions p ON c.positionId = p.id LEFT JOIN voting_results v ON c.id = v.candidateId ORDER BY p.order, c.name");
							while($fetch_candidates = mysqli_fetch_assoc($select_candidates_by_position)):
							?>
                            <tr data-position-name="<?php echo $fetch_candidates['positionName']; ?>">
                                <td></td>
                                <td><?php echo $fetch_candidates['name']; ?></td>
                                <td><?php echo $fetch_candidates['votes']; ?></td>
                                <td><?php echo $fetch_candidates['positionId']; ?></td>
                                <td><?php echo $fetch_candidates['positionName']; ?></td>
                                <td><?php echo $fetch_candidates['positionOrder']; ?></td>
                            </tr>
                            <?php
                            endwhile;
							?>
						</tbody>
					</table>


				</div>
                <?php
                // select voters group by fromwhere
                $select_voters_group = mysqli_query($con, "SELECT fromwhere, count(*) as total FROM voters_data where fromwhere <> '' GROUP BY fromwhere");
                if (mysqli_num_rows($select_voters_group) > 0):
                    ?>
                    <div class="box-content">
                        <h4 class="box-title">إجمالي الناخبين حسب المنطقة</h4>
                        <?php
                        // select voters group by fromwhere
                        $select_voters_group = mysqli_query($con, "SELECT fromwhere, count(*) as total FROM voters_data where fromwhere <> '' GROUP BY fromwhere");
                        foreach ($select_voters_group as $key => $value):
                            ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="box-contact">
                                    <h3 class="name margin-top-10"><?php echo $value['fromwhere']; ?></h3>
                                    <h4 class="job"><?php echo $value['total']; ?></h4>
                                </div>
                            </div>
                        <?php
                        endforeach;
                        ?>
                    </div>
                <?php
                endif;
                ?>
			</div>
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
	<script src="assets/plugin/datatables/extensions/RowGroup/js/dataTables.rowGroup.min.js"></script>
	<script src="assets/scripts/datatables.demo.min.js"></script>

    <!-- Chartist -->
    <script src="assets/plugin/chart/chartist/chartist.min.js"></script>


	<script>
        var t = $('#main').DataTable({
            pageLength: 25,
            ordering: true,
            language: {
                search: "البحث ",
                sZeroRecords: "لايوجد نتائج",
                sInfoEmpty: "",
                sInfoFiltered: "",
                sInfo: "يتم عرض من _START_ الى _END_",
                sLengthMenu: " _MENU_ ",
                paginate: {
                    first: "الأول",
                    previous: "السابق",
                    next: "التالي",
                    last: "الأخير"
                },
            },
            order: [
                [2, 'desc'],
                [1, 'asc'],
            ],
            orderFixed: [5, 'asc'],
            columnDefs: [
                {targets: [3,4,5], visible: false},
                {orderable: false, targets: 0}
            ],
            rowGroup: {
                dataSrc: 3,
                startRender: function (rows, group) {
                    let data = rows.data();
                    if (data[0] && data[0][4]) {
                        return data[0][4];
                    }
                    return group;
                }
            }
        });
        t.on('order.dt search.dt', function () {
            let i = 1;

            t.cells(null, 0, {search: 'applied', order: 'applied'}).every(function (cell) {
                this.data(i++);
            });
        }).draw();

        <?php if ($isAllLocationsClosed): ?>
        // draw line chart with number of voters in that hour
        new Chartist.Line('#voters-volume-chart', {
            labels: ['6 AM', '7 AM', '8 AM', '9 AM', '10 AM', '11 AM', '12 PM', '1 PM', '2 PM', '3 PM', '4 PM', '5 PM', '6 PM', '7 PM', '8 PM', '9 PM'],
            series: [
                [<?php
                    for ($i = 0; $i < 16; $i++) {
                        echo ($voters_done_volume_per_hour[$i + 6] ?? 0);
                        echo $i < 15 ? ',': '';
                    }
                    ?>]
            ]
        }, {
            fullWidth: true,
            height: '300px',
            chartPadding: {
                right: 40
            },
            axisY: {
                onlyInteger: true,
            },
            classNames: {
                start: 'ct-end',
                end: 'ct-start',
            }
        }, [
            ['screen and (min-width: 641px) and (max-width: 1024px)', {
                showPoint: false,
                axisX: {
                    labelInterpolationFnc: function (value, index) {
                        return index % 2 === 0 ? value : null;
                    }
                }
            }],
            ['screen and (max-width: 640px)', {
                showLine: false,
                axisX: {
                    labelInterpolationFnc: function (value, index) {
                        return index % 2 === 0 ? value : null;
                    }
                }
            }]
        ]);
        <?php endif; ?>
    </script>

	<script src="assets/scripts/main.min.js"></script>
	<script src="assets/scripts/horizontal-menu.min.js"></script>
</body>
</html>
