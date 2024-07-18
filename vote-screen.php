<?php
global $con;
include 'config.php';
include 'php-csrf.php';

session_start();
// Initialize an instance
$csrf = new CSRF('vote-screen', 'key-awesome', 0);

$show_success = false;
$show_error = false;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['action']) && $_POST['action'] === 'get-csrf-token') {
        $token = $csrf->string('vote-form');
        header('Content-Type: application/json');
        echo json_encode(['token' => $token]);
        exit;
    }
    if(!isset($_POST['is_submited']) || !isset($_POST['sessionId']) || !$csrf->validate('vote-form')) {
        $show_error = true;
        $error_message = "حدث خطأ في حفظ البيانات، يرجى إعادة تحميل الصفحة";
    } else {
        $candidates = isset($_POST['selected_candidates']) ? $_POST['selected_candidates'] : [];
        $sessionId = $_POST['sessionId'];
        $con->begin_transaction();
        try {
            if (count($candidates) > 0) {
                // select candidates positions, then check if number of candidates selected is less than position max votes
                $clause = implode(',', array_fill(0, count($candidates), '?'));
                $bindString = str_repeat('s', count($candidates));
                $stmt = $con->prepare("SELECT c.id, p.id, p.maxVotes FROM positions p JOIN candidates c ON p.id = c.positionId WHERE c.id IN ($clause)");
                $stmt->bind_param($bindString, ...$candidates);
                $stmt->execute();
                $stmt->bind_result($candidate, $position, $maxVotes);
                $candidates_by_position = [];
                while ($stmt->fetch()) {
                    if ($candidates_by_position[$position] === null) {
                        $candidates_by_position[$position] = array(
                            'candidates' => array(),
                            'maxVotes' => $maxVotes
                        );
                    }
                    if (!in_array($candidate, $candidates_by_position[$position]['candidates'])) {
                        $candidates_by_position[$position]['candidates'][] = $candidate;
                    }
                }
                $stmt->close();

                foreach ($candidates_by_position as $position => $item) {
                    if (count($item['candidates']) > $item['maxVotes']) {
                        $error_message = 'يمكن التصويت إلى ' . $item['maxVotes'] . ' مرشحين كحد أقصى، يرجى المحاولة مجدداً';
                        throw new Exception($error_message);
                    }
                }
            }

            $stmt = $con->prepare("UPDATE voters_data v JOIN screens s ON s.voterId = v.voterId SET v.status = 3, s.voterId = null WHERE v.status = 2 AND s.sessionId = ?");
            $stmt->bind_param('s', $sessionId);
            $stmt->execute();

            if ($con->affected_rows < 1) {
                $error_message = 'حدث خطأ في حفظ البيانات، الناخب صوت مسبقًا';
                throw new Exception($error_message);
            }
            
            $stmt->close();

            // use inter and update on duplicate
            $stmt = $con->prepare("INSERT INTO voting_results (candidateId, votes, createdAt, updatedAt) VALUES (?, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00') ON DUPLICATE KEY UPDATE votes = votes + 1");
            $stmt->bind_param('s', $candidate);
            foreach ($candidates as $index => $value) {
                $candidate = $value;
                $stmt->execute();
            }
            $stmt->close();

            // insert voting submission JSON record [{positionId: 1, positionName: '...', candidates: [{id: 1, name: '...'}, ...]}, ...]
            if (count($candidates) < 1) {
                $stmt = $con->prepare("INSERT INTO voting_submissions (id, submission) VALUES (uuid(), JSON_ARRAY())");
            } else {
                $stmt = $con->prepare("INSERT INTO voting_submissions (id, submission) 
                SELECT uuid() id, JSON_ARRAYAGG(item) submission
                FROM (
                    SELECT JSON_OBJECT('positionId', p.id, 'positionName', p.name, 'candidates', JSON_ARRAYAGG(JSON_OBJECT('id', c.id, 'name', c.name))) item 
                    FROM positions p JOIN candidates c ON c.positionId = p.id 
                    WHERE c.id IN ($clause) 
                    GROUP BY p.id
                ) items");
                $stmt->bind_param(str_repeat('s', count($candidates)), ...$candidates);
            }
            $stmt->execute();
            $stmt->close();

            $con->commit();
            $csrf->clearHashes('vote-form');
            $show_success = true;
            header("refresh:5; url=vote-screen");
        } catch (Exception $e) {
            $con->rollback();
            if (empty($error_message)) {
                $error_message = 'حدث خطأ في حفظ البيانات، يرجى التواصل مع أعضاء التسجيل';
            }
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
    <link rel="stylesheet" href="assets/plugin/sweetalert2/sweetalert2.min.css">

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
        .candidate .badge.select-count {
            top: 10px;
            left: 13px;
            position: absolute;
            padding-top: 8px;
            font-size: 18px;
        }
        .box-contact .avatar {
            top: -94px;
            width: 150px;
            height: 150px;
        }
        .box-contact .name {
            font-size: 23px;
        }
        div.swal2-popup,
        div.swal2-container {
            font-size: 1.7rem;
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
                    <?php echo $error_message; ?>
                    <h1 class="text-orange" style="font-size: 10em;"><i class="ico fa fa-times"></i></h1>
                    <br />
                    <br />
                    <button type="button" class="btn btn-lg btn-icon btn-icon-left btn-primary waves-effect waves-light" onclick="location.reload();">
                        <i class="ico fa fa-refresh"></i>إعادة تحميل
                    </button>
                    <a href="vote-screen" type="button" class="btn btn-lg btn-icon btn-icon-right btn-primary waves-effect waves-light">
                        <i class="ico fa fa-arrow-left"></i>رجوع
                    </a>
                </h1>
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
            <img src="logo.png" alt="logo" width="185" style="filter: drop-shadow(0px 0px 2px #aaa);" />
            <h4><span class="screen-name">صفحة التصويت</span><span class="location-name"></span></h4>
            <hr />

            <div class="main-content container">
                <div class=”float-button”></div>

                <div class="row small-spacing" style="margin-top: 40px;">
                    <form id="voting-form" name="submitted_votes" action="vote-screen" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="is_submited" value="yes" />
                        <?=$csrf->input('vote-form', 0, 100);?>

                        <div id="candidates" class="row"></div>
                        <div id="actions" class="row">
                            <div class="col-sm-12 col-lg-12 col-md-12">
                                <div id="page-submit" style="margin-top: 40px">
                                    <button type="button"
                                            class="btn btn-lg btn-icon btn-icon-left btn-success waves-effect waves-light">
                                        <i class="ico fa fa-check"></i>إنتهاء وتسليم
                                    </button>
                                </div>
                            </div>
                            <div class="col-sm-12 col-lg-12 col-md-12" style="margin-top: 40px">
                                <div class="pull-left" id="page-next">
                                    <button type="button"
                                            class="btn btn-lg btn-icon btn-icon-left btn-info btn-icon-right waves-effect waves-light">
                                        <i class="ico fa fa-arrow-left"></i>التالي
                                    </button>
                                </div>
                                <div class="pull-right" id="page-prev">
                                    <button type="button"
                                            class="btn btn-lg btn-icon btn-icon-left btn-primary waves-effect waves-light">
                                        السابق<i class="ico fa fa-arrow-right"></i></button>
                                </div>
                            </div>
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
    <script src="assets/plugin/sweetalert2/sweetalert2.all.js"></script>
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
    <script src="<?php echo WS_URL; ?>/socket.io/socket.io.js"></script>
    <script>
        const groupBy = (x, f) => x.reduce((a, b, i) => ((a[f(b, i, x)] ||= []).push(b), a), {});
        $(function() {
            var socket = io("<?php echo WS_URL; ?>/screens", {
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
            const reloadAlert = function () {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'حدث خطأ أثناء تحميل الصفحة، يرجى المحاولة مجدداً',
                    confirmButtonText: 'إعادة تحميل',
                    confirmButtonColor: '#f57c00',
                    showCancelButton: false,
                    allowEscapeKey: false,
                    allowOutsideClick: false,
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            }
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
                $.ajax({
                    url: 'vote-screen.php',
                    type: 'POST',
                    data: {
                        action: 'get-csrf-token'
                    },
                    success: function(data) {
                        if (data && data.token) {
                            $('input[name="key-awesome"]').val(data.token);
                            return;
                        }
                        reloadAlert();
                    },
                    error: function() {
                        reloadAlert();
                    }
                });

                if(Array.isArray(data)) {

                    const candidatesByPosition = Object.entries(groupBy(data, c => c.position.id)).map(function([, candidates]) {
                        if (Array.isArray(candidates) && candidates.length) {
                            const position = candidates[0].position;
                            return {
                                position,
                                candidates
                            }
                        }
                        return null;
                    }).filter(Boolean).sort(function(a, b) { return a.position.order - b.position.order });

                    const getPositionPageHtml = function (position, candidates) {
                        const pronounce = position.maxVotes > 1 ? `${position.maxVotes} أشخاص` : 'شخص';
                        const selectedCounter = `<h2 style="color: #af5656;">عدد اختياراتك الحالي:<span id="count-checked-checkboxes-${position.id}">0</span></h2>`;
                        const title = `<div class="row position" id="position-${position.id}" data-position-id="${position.id}"><div class="col-lg-12" style="margin-bottom: 40px;"><h1 style="font-weight: 700;">${position.name}</h1>${selectedCounter}<h4>يمكنك إختيار ${pronounce} او أقل</h4></div>`;
                        return title + candidates.map(function (c) {
                            return `<div class="col-lg-4 col-md-6">
                                <div class="candidate box-contact">
                                    <spin class="badge bg-danger select-count"></spin>
                                    <img src="${c.img}" alt="" class="avatar">
                                    <h3 class="name margin-top-10">${c.name}</h3>
                                    <div class="text-muted">
                                        <input type="checkbox" name="selected_candidates[]" value="${c.id}" style="width: 25px; height: 25px;"><br>
                                        <label for="chk-1">اضغط على المربع للإختيار</label>
                                    </div>
                                </div>
                            </div>`;
                        }).join('') + '</div>';
                    }

                    const html = candidatesByPosition.map(function (item) {
                        return getPositionPageHtml(item.position, item.candidates);
                    }).join('');

                    $('#candidates').html('<input type="hidden" name="sessionId" value="'+ socket.auth.sessionId +'" />' + html);

                    function showPositionPage(positionId) {
                        $('.position').hide();
                        $(`#position-${positionId}`).show();
                        // show/hide next,prev submit buttons
                        if (candidatesByPosition.length === 1) {
                            $('#page-submit').show();
                            $('#page-next').hide();
                            $('#page-prev').hide();
                        } else {
                            const positionIndex = candidatesByPosition.findIndex(p => p.position.id === positionId);
                            if (positionIndex === 0) {
                                $('#page-prev').hide();
                                $('#page-next').show();
                                $('#page-submit').hide();
                            } else if (positionIndex === candidatesByPosition.length - 1) {
                                $('#page-submit').show();
                                $('#page-next').hide();
                                $('#page-prev').show();
                            } else {
                                $('#page-submit').hide();
                                $('#page-next').show();
                                $('#page-prev').show();
                            }
                        }
                    }

                    // hide all positions pages then show the first one
                    $('.position').hide();
                    if (candidatesByPosition.length > 0) {
                        showPositionPage(candidatesByPosition[0].position.id);
                    } else {
                        $('#candidates').html(`<div>
                            <h1 class="text-orange">
                                لا يوجد مرشحين للتصويت عليهم, يرجى التواصل مع أعضاء التسجيل
                            </h1>
                            <h1 class="text-orange" style="font-size: 10em;"><i class="ico fa fa-times"></i></h1>
                        </div>`);
                        $('#page-next').hide();
                        $('#page-prev').hide();
                        $('#page-submit').hide();
                    }


                    $('#page-next').on('click', function() {
                        const current = $('.position:visible').data('position-id');
                        const currentIndex = candidatesByPosition.findIndex(function (item) {
                            return String(item.position.id) === String(current);
                        });
                        const currentPosition = candidatesByPosition[currentIndex].position;
                        const nextPosition = candidatesByPosition[currentIndex + 1].position;
                        // check checked count
                        const checked = $('.position:visible').find('input[type="checkbox"]:checked').length;
                        if (checked < currentPosition.maxVotes) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'تنبيه',
                                text: 'لقد اخترت أقل من العدد المطلوب، هل تريد المتابعة؟',
                                confirmButtonText: 'متابعة',
                                confirmButtonColor: '#f57c00',
                                cancelButtonText: 'إلغاء',
                                cancelButtonColor: '#3085d6',
                                showCancelButton: true,
                                focusCancel: true,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    showPositionPage(nextPosition.id);
                                }
                            });
                        } else {
                            showPositionPage(nextPosition.id);
                        }
                    });

                    $('#page-submit button').on('click', function() {
                        const current = $('.position:visible').data('position-id');
                        const currentIndex = candidatesByPosition.findIndex(function (item) {
                            return String(item.position.id) === String(current);
                        });
                        const currentPosition = candidatesByPosition[currentIndex].position;
                        // check checked count
                        const checked = $('.position:visible').find('input[type="checkbox"]:checked').length;
                        if (checked < currentPosition.maxVotes) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'تنبيه',
                                text: 'لقد اخترت أقل من العدد المطلوب، هل تريد المتابعة؟',
                                confirmButtonText: 'متابعة',
                                confirmButtonColor: '#f57c00',
                                cancelButtonText: 'إلغاء',
                                cancelButtonColor: '#3085d6',
                                showCancelButton: true,
                                focusCancel: true,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $('#voting-form').submit();
                                }
                            });
                        } else {
                            $('#voting-form').submit();
                        }
                    });

                    $('#page-prev').on('click', function() {
                        const current = $('.position:visible').data('position-id');
                        const currentIndex = candidatesByPosition.findIndex(function (item) {
                            return String(item.position.id) === String(current);
                        });
                        showPositionPage(candidatesByPosition[currentIndex - 1].position.id);
                    });

                    var checkboxes = $('input[type="checkbox"]');
                    checkboxes.change(function() {
                        // checked count in position page
                        const $position = $(this).closest('.position');
                        const position = candidatesByPosition.find(p => String(p.position.id) === String($position.data('position-id'))).position;
                        const $positionCheckboxes = $position.find('input[type="checkbox"]');
                        const checked = $positionCheckboxes.filter(':checked').length;
                        $positionCheckboxes.filter(':not(:checked)').prop('disabled', checked >= position.maxVotes);
                        $('#count-checked-checkboxes-' + position.id).text(checked);
                        if (!this.checked) {
                            $(this).closest('.select-count').text('');
                        }
                        $positionCheckboxes.closest('.candidate').find('.select-count').text('');
                        $positionCheckboxes.filter(':checked').closest('.candidate').find('.select-count').text(checked);
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
                    $.ajax({
                        url: 'vote-screen.php',
                        type: 'POST',
                        data: {
                            action: 'get-csrf-token'
                        },
                        success: function(data) {
                            if (data && data.token) {
                                $('input[name="key-awesome"]').val(data.token);
                                $('#voting-form').submit();
                                return;
                            }
                            reloadAlert();
                        },
                        error: function() {
                            reloadAlert();
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>