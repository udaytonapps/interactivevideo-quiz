<?php
require_once "../config.php";
require_once "dao/IV_DAO.php";

use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Util\LTIConstants;
use \Tsugi\Core\LTIX;
use \IV\DAO\IV_DAO;

// Retrieve the launch data if present
$LTI = LTIX::requireData();
$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

$IV_DAO = new IV_DAO($PDOX, $p);

function postLaunchHTML($newparms, $endpoint) {

    if ( isset($newparms["ext_lti_form_id"]) ) {
        $form_id = $newparms["ext_lti_form_id"];
    } else {
        $form_id = "tsugi_form_id_".bin2Hex(openssl_random_pseudo_bytes(4));
    }

    $r = "<form action=\"".$endpoint."\" name=\"".$form_id."\" id=\"".$form_id."\" method=\"post\" target=\"dummyframe\" encType=\"application/x-www-form-urlencoded\">\n" ;
    $r .= "<iframe width=\"0\" height=\"0\" name=\"dummyframe\" id=\"dummyframe\"></iframe>";
    ksort($newparms);
    $submit_text = $newparms['ext_submit'];
    foreach($newparms as $key => $value ) {
        $key = htmlspec_utf8($key);
        $value = htmlspec_utf8($value);
        if ( $key == "ext_submit") {
            $r .= "<input type=\"submit\" name=\"";
        } else {
            $r .= "<input type=\"hidden\" name=\"";
        }
        $r .= $key;
        $r .= "\" class=\"btn btn-primary";
        $r .= "\" value=\"";
        $r .= $value;
        $r .= "\"/>\n";
    }
    $r .= "</form>\n";
    // Remove session_name (i.e. PHPSESSID) if it was added.
    $r .= " <script type=\"text/javascript\"> \n" .
        "  //<![CDATA[ \n" .
        "    var inputs = document.getElementById(\"".$form_id."\").childNodes;\n" .
        "    for (var i = 0; i < inputs.length; i++)\n" .
        "    {\n" .
        "        var thisinput = inputs[i];\n" .
        "        if ( thisinput.name != '".session_name()."' ) continue;\n" .
        "        thisinput.parentNode.removeChild(thisinput);\n" .
        "    }\n" .
        "  //]]> \n" .
        " </script> \n";

    $ext_submit = "ext_submit";
    $ext_submit_text = $submit_text;
    $r .= " <script type=\"text/javascript\"> \n" .
        "  //<![CDATA[ \n" .
        "    document.getElementById(\"".$form_id."\").style.display = \"none\";\n" .
        "    nei = document.createElement('input');\n" .
        "    nei.setAttribute('type', 'hidden');\n" .
        "    nei.setAttribute('name', '".$ext_submit."');\n" .
        "    nei.setAttribute('value', '".$ext_submit_text."');\n" .
        "    document.getElementById(\"".$form_id."\").appendChild(nei);\n" .
        "    document.".$form_id.".submit(); \n" .
        "    console.log('Autosubmitted ".$form_id."'); \n" .
        "  //]]> \n" .
        " </script> \n";
    return $r;
}

$userInfoStmt = $PDOX->prepare("SELECT * FROM {$p}lti_user
        WHERE user_id = :userId");
$userInfoStmt->execute(array(":userId" => $USER->id));
$userInfo = $userInfoStmt->fetch(PDO::FETCH_ASSOC);

$url = "https://example.warpwire.com/api/lti";
$key = 'your_key_here';
$secret = 'your_secret_here';
$custom = "lis_person_sourcedid=" . $_SESSION["lti_post"][LTIConstants::LIS_PERSON_SOURCEDID];

// Start of the output
$OUTPUT->header();
?>
    <!-- Our main css file that overrides default Tsugi styling -->
    <link rel="stylesheet" type="text/css" href="styles/main.css">
<?php
$OUTPUT->bodyStart();
include("menu.php");
$OUTPUT->topNav($menu);
$OUTPUT->flashMessages();

if ( strlen($url) < 1 || strlen($key) < 1 || strlen($secret) < 1 ) {
    echo('<br><p>'.__("This LTI tool is not yet configured.").'</p>'."\n");
    $OUTPUT->footer();
    return;
}

$parms = $LAUNCH->newLaunch(true,true);

LTI::addCustom($parms, $custom, false);

$placementsecret = false;
$sourcedid = false;
$key_id = $LAUNCH->ltiParameter('key_id');
if ( $key_id && $CONTEXT->id && $LINK->id && $RESULT->id ) {
    $placementsecret = $LAUNCH->link->getPlacementSecret();
    $outcome = $CFG->wwwroot."/api/poxresult";
    $sourcebase = $key_id . '::' . $CONTEXT->id . '::' . $LINK->id . '::' . $RESULT->id . '::';
    $plain = $sourcebase . $placementsecret;
    $sig = U::lti_sha256($plain);
    $sourcedid = $sourcebase . $sig;
    $parms['lis_outcome_service_url'] = $outcome;
    $parms["lis_result_sourcedid"] = $sourcedid;
}

$form_id = "tsugi_form_id_".bin2Hex(openssl_random_pseudo_bytes(4));
$parms['ext_lti_form_id'] = $form_id;

$parms = LTI::signParameters($parms, $url, "POST", $key, $secret,
    __("Finish Launch"));

$content = postLaunchHTML($parms, $url);
echo($content);

$OUTPUT->topNav($menu);
$OUTPUT->flashMessages();

$videoId = $IV_DAO->getVideoId($CONTEXT->id, $LINK->id);

if (!$videoId) {
    // If no video, check if auto-initialization has already occurred
    $autoInitialized = $LAUNCH->link->settingsGet("auto-initialized", false);
    if (!$autoInitialized) {
        // If auto-initialization hasn't occurred, import all associated data for that link
        $previousIds = LTIX::getLatestHistoryIds();
        if ($previousIds) {
            $prevVideoId = $IV_DAO->getVideoIdByLinkId($previousIds["link_id"]);
            if ($prevVideoId) {
                // Manually set videoId for import action
                $IV_DAO->importVideo($prevVideoId, $CONTEXT->id, $LINK->id);
            }
            // Make sure to mark the settings as having been auto-initialized
            $LAUNCH->link->settingsSet("auto-initialized", true);
            header('Location: '.addSession('build-video.php'));
            // Since we're redirecting to the build page, we should stop execution here
            exit();
        }
    }
    // No video id in the database so ask for URL
    if ($USER->instructor) {
        // Show the enter URL form
        ?>
        <div class="row">
            <div class="col-sm-7" style="border-right: 1px solid #ccc;">
                <form method="post" action="actions/addvideo.php" id="addVideoForm">
                    <!-- TODO: This form uses two inputs for video url and can be simplified to a shared one.
                         TODO: Validate video url -->
                    <h3 class="text-center">Create a new interactive video</h3>
                    <p class="text-center">Add a Warpwire or YouTube video URL below to begin creating your interactive video.</p>

                    <div class="form-group">
                        <label for="videoTitle">Video Title</label>
                        <input type="text" class="form-control" id="videoTitle" name="videoTitle" placeholder="Interactive Video Quiz" required oninvalid="this.setCustomValidity('You must enter a title for this interactive video.');" oninput="setCustomValidity('');">
                    </div>

                    <ul class="nav nav-tabs nav-justified">
                        <li class="active">
                            <a data-toggle="tab" href="#warpwire">
                                <img src="images/icon-warpwire-circle-black.png" alt="Warpwire Logo" class="warpwire-logo"> Warpwire Video
                            </a>
                        </li>
                        <li>
                            <a data-toggle="tab" href="#youtube">
                                <span class="fa fa-youtube-play" style="color:#242424;font-size:20px;"></span> YouTube
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div id="warpwire" class="tab-pane fade in active">
                            <h3>Warpwire</h3>
                            <div style="margin-bottom: 10px">
                                <input type="button" class="btn btn-primary" value="Launch Video Picker" onclick="openWindow();" />
                                <span> -or- Paste URL Below</span>
                            </div>
                            <div class="form-group vid-img">
                                <div class="input-group">
                                    <input type="text" class="form-control url-input" id="wwUrl" name="wwUrl" placeholder="https://example.warpwire.com/w/...">
                                    <span class="input-group-addon"><img src="images/icon-warpwire-circle-black.png" alt="Warpwire Logo" class="warpwire-logo"></span>
                                </div>
                            </div>
                        </div>
                        <div id="youtube" class="tab-pane fade">
                            <h3>YouTube</h3>
                            <div class="form-group">
                                <label for="videoUrl">Video URL</label>
                                <div class="input-group">
                                    <input type="text" class="form-control url-input" id="ytUrl" name="ytUrl" placeholder="https://www.youtube.com/watch?v=...">
                                    <span class="input-group-addon"><span class="fa fa-youtube-play" style="color:#242424;font-size:20px;"></span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Create</button> <span class="text-danger" id="blankUrlAlert" style="display:none;"><span aria-hidden="true" class="fa fa-warning"></span> You must enter a video url to continue.</span>
                </form>
            </div>
            <div class="col-sm-5">
                <h3 class="text-center">Reuse an existing interactive video</h3>
                <p class="text-center">You can select a video from the list below to use a previously created video and questions.</p>
                <?php
                $previousVideos = $IV_DAO->findVideosForImport($USER->id);
                if (!$previousVideos) {
                    echo '<p class="text-center">No previous videos available for import.</p>';
                } else {
                    $videoMap = array();
                    foreach ($previousVideos as $video) {
                        if (!array_key_exists($video["sitetitle"], $videoMap)) {
                            $videoMap[$video["sitetitle"]] = array();
                        }
                        array_push($videoMap[$video["sitetitle"]], $video);
                    }
                    ?>
                    <form class="form" action="actions/import.php" method="post">
                        <div class="form-group">
                            <label for="importVid">Previous Interactive Video Quizzes</label>
                            <select class="form-control" id="importVid" name="import-video">
                                <?php
                                foreach($videoMap as $sitetitle => $videos_in_context) {
                                    echo '<optgroup label="'.$sitetitle,'">';
                                    foreach ($videos_in_context as $vid) {
                                        echo '<option value="'.$vid["video_id"].'">'.$vid["video_title"].'</option>';
                                    }
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><span class="fa fa-upload" aria-hidden="tre"></span> Import</button>
                    </form>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    } else {
        // Show the this tool is not yet configured message
        ?>
        <div class="text-center">
            <h3>Your instructor has not added an interactive video yet.</h3>
        </div>
        <?php
    }
} else {
    // Video has been set so go to video page.
    $_SESSION["videoId"] = $videoId;

    $finished = $IV_DAO->isStudentFinished($videoId, $USER->id);
    $_SESSION["finished"] = $finished;

    if ($USER->instructor) {
        header( 'Location: '.addSession('build-video.php') ) ;
        return;
    } else {
        if (!$finished) {
            header( 'Location: '.addSession('play-video.php') ) ;
            return;
        } else {
            header( 'Location: '.addSession('student-results.php') ) ;
            return;
        }
    }

}
$OUTPUT->footerStart();
?>
<script type="text/javascript">
    $(document).ready(function () {
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            // Clear all url inputs on video type switch
            $('input.url-input').val('');
        });
        $("#addVideoForm").on("submit", function (e) {
            if ($("#wwUrl").val() === '' && $("#ytUrl").val() === '') {
                e.preventDefault();
                $("#blankUrlAlert").fadeIn("slow");
            }
        });
        registerListener();
    });

    let oauthKey = 'your_key_here';
    let oauthDomain = '*';
    let warpwireSite = 'https://example.warpwire.com';
    let startPage = '/w/all/';

    let pluginUrl = warpwireSite + startPage + '?pl=1&showSelector=1&oauth_client_id=' + oauthKey+'&oauth_redirect_host=' + encodeURI(oauthDomain);
    let pluginId = '';

    for (let i = 0; i < 64; i++) {
        pluginId += Math.floor(Math.random() * 16).toString(16);
    }

    pluginUrl += '&pluginId=' + pluginId;

    function registerListener() {
        window.addEventListener("message", receiveMessage, true);
        document.getElementById('wwUrl').value = '';
    }

    function receiveMessage(event) {
        if (event.origin !== warpwireSite) {
            console.log('Debug: Wrong origin: ' + event.origin);
            return;
        }
        if (event.data.message !== 'deliverResult') {
            console.log('Debug: Wrong message name');
            return;
        }

        if (typeof event.data == 'undefined' || typeof event.data.result == 'undefined') {
            console.log('Debug: Payload is undefined');
            return;
        }

        let response = JSON.parse(event.data.result);
        let pretty = JSON.stringify(response, undefined, 4);

        console.log(response);
        document.getElementById('wwUrl').value = response[0].assetData.permaLink;

        let appDiv = document.getElementById('vid-img');
        if(document.getElementById('imgNode') != null) {
            document.getElementById('imgNode').remove();
        }
        let imgNode = document.createElement("img");
        imgNode.id = "imgNode";
        imgNode.src = response[0]._ww_img;
        imgNode.style.maxHeight = '150px';
        imgNode.style.maxWidth = '150px';

        document.getElementById('warpwire').appendChild(imgNode);
    }

    function openWindow() {

        let child = window.open(pluginUrl, '_wwPlugin', 'width=400, height=500');

        let leftDomain = false;
        let interval = setInterval(function() {
            console.log('checking');
            try {
                if (child.document.domain === document.domain) {
                    if (leftDomain && child.document.readyState === 'complete') {
                        // the child window returned to our domain
                        clearInterval(interval);
                        child.postMessage({ message: 'requestResult' }, '*');
                    }
                }
                else {
                    // older browser might not implement XSS prevention of domain framing
                    leftDomain = true;
                }
            }
            catch(e) {
                // the child window has been navigated away or closed
                if (child.closed) {
                    console.log('Debug: Failed postmessage' + e);
                    clearInterval(interval);
                    return;
                }
                // navigated to another domain
                leftDomain = true;
            }
        }, 500);

        return (true);
    }
</script>
<?php
$OUTPUT->footerEnd();
