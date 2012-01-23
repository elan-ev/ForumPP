/* An enhanced style for the forum, Web 2.0 - like*/

/* defaults */
#forumpp form {
    display: inline;
}

#forumpp img {
    border: none;
    max-width: 600px;
}

#forumpp img:onClick {
    max-width: 100%;
}

/* highlighted search-strings */
#forumpp span.highlight {
    background-color: yellow;
        border: 1px solid #FF9999;
        border-radius: 3px;
}

/* posting lists*/
#forumpp .forum_header {
    background-color: #899AB9;
    color: #FFFFFF;
    margin: 0pt;
    padding: 0pt;
}

#forumpp .listheader {
    background-color: #D9E1F2;
    padding: 0pt;
}

#forumpp .listheader strong {
    margin: 0pt 5px 0pt 5px;
}

#forumpp .heading {
    display: block;
    margin: 1px 4px 4px 6px;
    text-transform: uppercase;
}

#forumpp .areaentry {
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #000000;
    background-color: #DEE2E8;
}

#forumpp .areaentry2 {
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #000000;
    border-left: 1px solid #FFFFFF;
    margin: 0pt;
    padding: 0pt 5px;
    background-color: #DEE2E8;
}

#forumpp .areaborder {
    background-color: #899AB9;
    width: 0.5%;
    padding: 0pt;
    margin: 0pt;
}

#forumpp .area_title {
    padding: 0 5px;
    font-weight: bold;
    text-transform: uppercase;
}

#forumpp .area_input, #forumpp .add_area_form {
    padding: 0 5px;
}

#forumpp .add_area {
    font-weight: bold;
    font-size: 16pt;
    text-align: right;
    padding-right: 12px;
}

#forumpp .add_area:hover {
    color: white;
    cursor: pointer;
}

#forumpp .icon {
    padding: 8px 0 0 0;
    margin: 0pt;
    width: 20px;
    height: 50px;
}

#forumpp .icon_thread {
    padding: 0pt;
    margin: 0pt;
    width: 40px;
    height: 30px;
}

#forumpp span.areaname {
    font-weight: bold;
}

/* thread specififc stuff */
#forumpp span.threadauthor {
    float: left;
    width: 70%;
}

#forumpp span.pagechooser {
    float: right;
    min-width: 15%;
}

#forumpp span.pagechooser_thread {
    float: right;
    width: 160px;
    padding-bottom: 2px;
    white-space: nowrap;
}

/* page-chooser */
#forumpp span.page {
    border: 1px solid #AAAAAA;
    background-color: #DDDDDD;
    padding: 1px 3px 0px 3px;
    font-weight: bold;
}

#forumpp span.selected {
    background-color: #4d6b9d;
    color: #FFFFFF;
}

/* style definitions for one posting */
#forumpp .bg1 {
    background-color: #ECF3F7;
}

#forumpp .bg2 {
    background-color: #DEE2E8;
}

#forumpp .posting {
    height: 100%;
    margin: 0pt;
    padding: 0pt;
}

#forumpp .postbody {
    position: relative;
    padding: 0pt 5px;
    margin: 0pt;
    width: 78%;
    float: left;
    text-align: left;
}

#forumpp .buttons {
    clear: both;
    width: 100%;
    text-align: center;
    padding-top: 5px;
}

#forumpp img.button, #forumpp input[type=image] {
    vertical-align: middle;
}

#forumpp div.title {
    text-align: left;
    width: 80%;
    float: left;
}

 #forumpp .title {
    font-weight: bold;
 }

/* space for the icons */
#forumpp div.postbody span.icons {
    float: right;
    min-width: 3%;
}

#forumpp p.author {
    margin: 2px 0px 8px 0px;
}

#forumpp div.postbody p.content {
    overflow: hidden;
    clear: both;
}

#forumpp div.postbody p.content:hover {
    overflow: visible;
}

#forumpp .postprofile {
    border-left: 1px solid #FFFFFF;
    display: inline;
    float: right;
    min-height: 80px;
    width: 19%;
    margin: 0pt;
    padding: 0pt 0pt 0pt 8px;
}

#forumpp .postprofile dd, #forumpp .postprofile dt {
    padding: 0pt;
    margin: 0pt;
}

#forumpp span.buttons {
    display: block;
    clear: both;
    text-align: center;
    width: 78%;
}

/* Web 2.0 borders with rounded edges */
#forumpp span.corners-top, #forumpp span.corners-bottom,
#forumpp span.corners-top span, #forumpp span.corners-bottom span,
#forumpp span.corners-top-right {
    background-repeat: no-repeat;
    display: block;
    height: 5px;

    margin: 0pt;
    padding: 0pt;
}

#forumpp span.corners-top {
    background-image: url('<?= $picturepath ?>/corners_left.png');
    background-position: 0pt 0pt;
}

#forumpp span.corners-top-right, #forumpp span.corners-top span {
    background-image: url('<?= $picturepath ?>/corners_right.png');
    background-position: 100% 0pt;
}

#forumpp span.corners-bottom {
    background-image: url('<?= $picturepath ?>/corners_left.png');
    background-position: 0pt -7px;

    clear:both;
}

#forumpp span.corners-bottom span {
    background-image: url('<?= $picturepath ?>/corners_right.png');
    background-position: 100% -7px;
}

#forumpp span.no-corner {
    display: block;
    height: 5px;
}

#forumpp .action-icons {
    position: absolute;
    right: 10px;
    top: 0px; 
}

#forumpp .action-buttons {
    display: none;
}

#forumpp textarea {
    width: 100%;
    height: 20em;
}

#forumpp tr.movable {
    cursor: move;
}

#forumpp .editor_toolbar {
    width: 100%;
}

#forumpp div.marked {
    background-color: white;
    position: absolute;
    top: 0px;
    right: 0px;
    height: 0px;
    width: 0px;
    -moz-box-shadow: -5px 5px 5px #888;
    -webkit-box-shadow: -5px 5px 5px #888;
    box-shadow: -5px 5px 5px #888;
    border-left: solid 40px #DEE2E8;
    border-top: solid 40px transparent;
}