<?php

require_once "../config.php";

use \Tsugi\Core\LTIX;

// Retrieve the launch data if present
$LTI = LTIX::requireData();

$p = $CFG->dbprefix;

// Start of the output
$OUTPUT->header();
?>
    <!-- Our main css file that overrides default Tsugi styling -->
    <link rel="stylesheet" type="text/css" href="styles/main.css">
<?php
$OUTPUT->bodyStart();
?>
    <style>
        button:disabled {
            color: #aaa;
        }
    </style>
    <iframe id="wwvideo1" height="360" width="640" data-ww-id="apiTestFrame" src="https://udayton.warpwire.com/w/_6YAAA/?share=0&title=0&controls=0" frameborder="0" scrolling="0" allowfullscreen></iframe>
    <br />

    <script>
        var theInterval = null;
        // 2. This code loads the IFrame Player API code asynchronously.
        var tag = document.createElement('script');

        tag.src = "scripts/wwIframeApi.min.js";
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        // 3. This variable is a global that can be used to interact with Warpwire Player APIs
        var wwPlayerApi = null;

        // 4. This function listens for the player api to be registered, and then sets up the player processing
        function onWarpwirePlayerAPIReady() {
            var done = false;
            var q1Answered = false;


            // 5. This code creates a new instance of the Warpwire API on the current page
            wwPlayerApi = new wwIframeApi();
            theInterval = setInterval(function(){
                var currentTime = wwPlayerApi('apiTestFrame').getCurrentTime();
                console.log(Math.floor(currentTime));
                if (Math.floor(currentTime) === 5.0) {
                    q1Answered = true;
                    wwPlayerApi('apiTestFrame').pause();
                    document.getElementById('questionPopup').style.display="block";
                    document.getElementById('mask').style.display="block";
                }
            }, 1000);

            wwPlayerApi('apiTestFrame').onStateChange = function(event) {
                var playButton = document.getElementById('playButton');
                var pauseButton = document.getElementById('pauseButton');
                if (event.data == WWIRE.PLAYERSTATES.PLAYING && !done) {
                    pauseButton.removeAttribute('disabled');
                    playButton.setAttribute('disabled', 'disabled');
                }
                if ((event.data == WWIRE.PLAYERSTATES.PAUSED) || (event.data == WWIRE.PLAYERSTATES.ENDED)) {
                    pauseButton.setAttribute('disabled', 'disabled');
                    playButton.removeAttribute('disabled');
                    if (q1Answered) {
                        clearInterval(theInterval);
                    }
                }
            };

            // 6. This function waits until the player api is ready for the given iframe, then calls the play event
            wwPlayerApi('apiTestFrame').onReady = function(event) {
                var playButton = document.getElementById('playButton');
                playButton.removeAttribute('disabled');
            };
        }
    </script>

    <br />
    <button id="playButton" disabled="disabled" onclick="wwPlayerApi('apiTestFrame').play()">Play</button>
    <button id="pauseButton" disabled="disabled" onclick="wwPlayerApi('apiTestFrame').pause()">Pause</button>

    <h3 id="videoTitle"></h3>

    <div id="mask" style="display:none;position: absolute;width:100%;height:100%;z-index: 1;left:0;top:0;background:#111;opacity:0.6;"></div>
    <div id="questionPopup" style="display:none;position:absolute;padding:20px;left:50%;top:25%;width:500px;height:300px;margin:-150px -250px;background:#eee;border:1px solid #ccc;z-index:2;">
        <p id="theQuestion">Here is a question.</p>
        <h3 id="feedback" style="text-align:center;display:none;color:green;background:white;">Great Job!</h3>
        <button id="answer">Answer</button>
        <button id="resume" style="display:none;">Continue Video</button>
    </div>

    <script type="text/javascript">
        var qBut = document.getElementById('answer');
        qBut.addEventListener("click", function(){
            document.getElementById('theQuestion').style.display="none";
            document.getElementById('feedback').style.display="block";
            document.getElementById('answer').style.display="none";
            document.getElementById('resume').style.display="block";
        });
        var rBut = document.getElementById('resume');
        rBut.addEventListener("click", function(){
            document.getElementById('questionPopup').style.display="none";
            document.getElementById('mask').style.display="none";
            wwPlayerApi('apiTestFrame').play();
        });
    </script>
<?php
$OUTPUT->footerStart();
?>
    <!-- Our main javascript file for tool functions -->
    <script src="scripts/main.js" type="text/javascript"></script>
<?php
$OUTPUT->footerEnd();
