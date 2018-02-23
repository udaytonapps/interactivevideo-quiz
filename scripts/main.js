/*Main Javascript File*/
var IntVideo = (function () {
    var intVideo = {};

    /* Matches VideoType.php */
    var typeEnum = Object.freeze({"Warpwire": 0, "YouTube": 1});

    /* Defaults to Warpwire */
    var _videoType = typeEnum.Warpwire;
    var _videoUrl = '';

    intVideo.initBuild = function (videoType, videoUrl) {
        if (videoType === typeEnum.YouTube) {
            _videoType = videoType;
        }
        _videoUrl = videoUrl;

        intVideo.getEmbedForBuild();

        _setupAddQuestionForm();
    };

    intVideo.getEmbedForBuild = function () {
        if (_videoType === typeEnum.Warpwire) {
            $("#buildVideo").html(
                '<iframe id="wwvideo" data-ww-id="wwvideo" src="'
                    + _videoUrl
                    + '" frameborder="0" scrolling="0" allowfullscreen></iframe>'
            );
        }
    };

    _setupAddQuestionForm = function () {
        var sess = $("input#sess").val();

        $("#addQuestionForm").on("submit", function(e) {
            e.preventDefault();

            $.ajax({
                type: "post",
                url: "actions/addquestion.php?PHPSESSID="+sess,
                data: $("#addQuestionForm").serialize(),
                success: function (response) {

                }
            });
        });
    };

    return intVideo;
})();