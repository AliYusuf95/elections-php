<?php
include 'config.php';
include 'php-csrf.php';

session_start();
// Initialize an instance
$csrf = new CSRF();

$show_success = false;
$show_error = false;

$voters_table = 'voters';
$candidates_table = 'candidates';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!isset($_POST['is_submited']) || !isset($_POST['sessionId'])  || !isset($_POST['selected_candidates']) || !$csrf->validate('vote-form')) {
        $show_error = true;
    } else {
        $candidates = $_POST['selected_candidates'];
        $sessionId = $_POST['sessionId'];
        $con->begin_transaction();
        try {
            if(count($candidates) > 10) {
                throw new Exception('يمكن التصويت إلى 10 مرشحين كحد أقصى، يرجى المحاولة مجدداً');
            }

            $stmt = $con->prepare("UPDATE $voters_table v JOIN screens s ON s.voterId = v.id SET v.status = 3, v.updatedAt = CURRENT_TIMESTAMP(), s.voterId = null WHERE v.status = 2 AND s.sessionId = ?");
            $stmt->bind_param('s', $sessionId);
            $stmt->execute();

            if ($con->affected_rows < 1) {
                throw new Exception('حدث خطأ في حفظ البيانات، يرجى المحاولة مجدداً');
            }
            
            $stmt->close();

            $stmt = $con->prepare("UPDATE $candidates_table SET votes = votes + 1 WHERE id = ?");
            $stmt->bind_param('s', $candidate);
            foreach ($candidates as $index => $value) {
                $candidate = $value;
                $stmt->execute();
            }
            $stmt->close();

            $con->commit();
            $show_success = true;
            header("refresh:5; url=vote-screen");
        } catch (Exception $e) {
            $con->rollback();
            $show_error = true;
        }
    }
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

    <style> 
        .candidate.box-contact.selected {
            border-color: #00bf4f!important;
            background-color: #a1dbb9!important;
        }
        .candidate.box-contact.selected label {
            color: #ffffff!important;
        }
        .candidate.box-contact {
            cursor: pointer;
            margin-top: 100px;
        }
        .box-contact .avatar {
            top: -94px;
            width: 150px;
            height: 150px;
        }
        .box-contact .name {
            font-size: 23px;
        }
    </style>

</head>

<body>
    <header class="fixed-header">
        <div class="header-top">
            <div class="container">
                    <div class="pull-right">
                        <a class="logo" style="font-weight: bold;">نظام التصويت الإلكتروني</a>
                    </div>
            </div>
        </div>
    </header>
    <!-- /.fixed-header -->

    <?php
    if($show_success):
    ?>

    <div id="connect-wrapper" style="top: 150px;position: relative;">
        <center style="margin-top: -20px;">
            <img src="logo.png" alt="logo" style="filter: drop-shadow(0px 0px 2px #aaa);" />
            <h4><span class="screen-name">صفحة التصويت</span><span class="location-name"></span></h4>
            <hr />
            <div>
                <h1 class="text-success">
                    تم إكمال التصويت بنجاح
                </h1>
                <h1 class="text-success" style="font-size: 10em;"><i class="ico fa fa-check"></i></h1>
            </div>
        </center>
    </div>

    <?php
    elseif($show_error):
    ?>

    <div id="connect-wrapper" style="top: 150px;position: relative;">
        <center style="margin-top: -20px;">
            <img src="logo.png" alt="logo" style="filter: drop-shadow(0px 0px 2px #aaa);" />
            <h4><span class="screen-name">صفحة التصويت</span><span class="location-name"></span></h4>
            <hr />

            <div>
                <h1 class="text-orange">
                    حدث خطأ في حفظ البيانات، يرجى التواصل مع أعضاء التسجيل
                </h1>
                <h1 class="text-orange" style="font-size: 10em;"><i class="ico fa fa-times"></i></h1>
            </div>
        </center>
    </div>

    <?php
    else:
    ?>

    <div id="connect-wrapper" style="top: 150px;position: relative;">
        <center style="margin-top: -20px;">
            <img src="logo.png" alt="logo" style="filter: drop-shadow(0px 0px 2px #aaa);" />
            <h4><span class="screen-name">صفحة التصويت</span><span class="location-name"></span></h4>
            <hr />

            <div id="connect-screen">
                <h1 id="screen-message">
                    جاري اللإتصال مع النظام الإنتخابي
                </h1>
                <h1 id="screen-code" style="font-size: 125px;"></h1>
            </div>
        </center>
    </div>



    <div id="wrapper" style="display: none;">
        <center style="margin-top: -20px; margin-bottom: 50px;">
            <img src="logo.png" alt="logo" width="90" style="filter: drop-shadow(0px 0px 2px #aaa); width: 24em;" />
            <h4><span class="screen-name">صفحة التصويت</span><span class="location-name"></span></h4>
            <hr />

            <h2 id="title">
                يمكنك إختيار 10 اشخاص او أقل
            </h2>

            <h2 style="color: #af5656;">
                عدد اختياراتك الحالي: <span id="count-checked-checkboxes">0</span>
            </h2>

            <div class="main-content container">
                <div class=”float-button”></div>

                <div class="row small-spacing" style="margin-top: 40px;">
                    <form id="voting-form" name="submitted_votes" action="vote-screen" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="is_submited" value="yes" />
                        <?=$csrf->input('vote-form', 0, 100);?>

                        <div id="candidates" class="row"></div>
                        <div class="col-sm-12 col-lg-12 col-md-12">
                            <hr>
                            </br>
                            <center>
                                <button type="submit" class="btn btn-icon btn-icon-left btn-success waves-effect waves-light" onclick="return submitChanges()"><i class="ico fa fa-check"></i>إنتهاء وتسليم</button>
                            </center>
                        </div>
                    </form>
                    <br />
                    <br />
                </div>
            </div>
            <!-- /.main-content -->
        </center>
    </div>

    <?php
    endif;
    ?>
    <!--/#wrapper -->
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
    <script src="https://elections-ws.memamali.com/socket.io/socket.io.js"></script>
    <script>
        function submitChanges(e) {
            var checked = $('input[type="checkbox"]').filter(':checked').length;
            if (checked < 10) {
                return confirm('لقد إخترت أقل من 10 مترشحين، هل انت متأكد من تسليم الطلب؟');
            }
        }
        $(function() {
            var socket = io("https://elections-ws.memamali.com/screens", {
                autoConnect: false
            });
            var isVoting = false;
            const sessionId = localStorage.getItem("sessionId");
            if (sessionId) {
                socket.auth = {
                    sessionId
                };
            }
            <?php
            if(!$show_success && !$show_error):
            ?>
            socket.connect();
            <?php
            endif;
            ?>
            socket.on("new-session", function(data) {
                // attach the session ID to the next reconnection attempts
                socket.auth = {
                    sessionId: data.sessionId
                };
                // store it in the localStorage
                localStorage.setItem("sessionId", data.sessionId);
                // save the ID of the user
                socket.screenId = data.screenId;
                socket.code = data.code;
                $('.location-name').text('');
                $('#screen-message').text('رمز صفحة التصويت');
                $('#screen-code').text(data.code);
                $('.screen-name').text('صفحة التصويت');
                $('#connect-screen').show();
            });
            socket.on("attached", function(data) {
                $('.screen-name').text(data.screenName || 'صفحة التصويت');
                $('.location-name').text(' - ' + data.locationName);
                $('#connect-screen').hide();
            });
            socket.on("show-vote", function(data) {
                if(Array.isArray(data)) {
                    var html = data.map(function(c) {
                        return '<div class="col-lg-4 col-md-6">'+
                            '<div class="candidate box-contact">'+
                                '<img src="' + c.img + '" alt="" class="avatar">'+
                                '<h3 class="name margin-top-10">' + c.name + '</h3>'+
                                '<div class="text-muted">'+
                                    '<input type="checkbox" name="selected_candidates[]" value="' + c.id + '" style="width: 25px; height: 25px;"><br>'+
                                    '<label for="chk-1">اضغط على المربع للإختيار</label>'+
                                '</div>'+
                            '</div>'+
                        '</div>';
                    }).join('');
                    $('#candidates').html('<input type="hidden" name="sessionId" value="'+ socket.auth.sessionId +'" />' + html);
                    var max = 10;
                    var checkboxes = $('input[type="checkbox"]');
                    checkboxes.change(function() {
                        var current = checkboxes.filter(':checked').length;
                        checkboxes.filter(':not(:checked)').prop('disabled', current >= max);
                        $('#count-checked-checkboxes').text(current);
                        $('#count-checked-checkboxes-balance').text(10 - current);

                    });
                    $('.candidate.box-contact').on('click', function(e) {
                        if (!$(e.target).is(':checkbox')) {
                            var checkbox = $(this).find('input[type="checkbox"]:not(:disabled)').get(0);
                            if(checkbox) {
                                $(checkbox).prop('checked', !checkbox.checked).trigger('change');
                            } else {
                                return;
                            }
                        }
                        $(this).toggleClass('selected');
                    });
                    $('#title').text();
                }
                $('#wrapper').show();
                $('#connect-wrapper').hide();
                isVoting = true;
            });
            socket.on("submit-vote", function(data) {
                if (isVoting) {
                    $('#voting-form').submit();
                }
            });
        });
    </script>
</body>

</html>