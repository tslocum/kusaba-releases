<?php
/*
* +------------------------------------------------------------------------------+
* Paint page for oekaki
* +------------------------------------------------------------------------------+
* This is the page displayed when a user clicks the "Paint" button on an oekaki
* board.  It displays the painter app, configured to send the finished image to
* paint_save.php, which will be processed for posting.
* +------------------------------------------------------------------------------+
*/
if (!isset($_POST['width'])||!isset($_POST['height'])||!isset($_POST['board'])) {
    die();
}
if ($_POST['width']<1||$_POST['height']<1) {
    die("Please enter a width/height greater than zero.");
}
if ($_POST['width']>750||$_POST['height']>750) {
    die("Please enter a width/height less than or equal to 750.");
}
require("config.php");
require(TC_ROOTDIR.'inc/OekakiApplet.php');
$results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_escape_string($_POST['board'])."'");
if (count($results)==0) {
    die();
} else {
    foreach($results AS $line) {
        $board_id = $line['id'];
        $board_dir = $line['name'];
        $board_type = $line['type'];
    }
}
if ($board_type!='2') {
    die("That is not a Oekaki compatible board!");
}
if (!isset($_POST['replyto'])) {
    $_POST['replyto'] = '0';
}
echo '<head>
<style type="text/css">
body{
margin: 0;
padding: 0
}
</style>
</head><body bgcolor="#AEAED9">';


$applet = $_POST['applet'];
    //$use_animation = $_GET['useanim'] ? TRUE : FALSE;
    
    /*if( $use_animation )
    {
        $anim_status = 'ENABLED (<a href="?applet=' . htmlspecialchars( $applet ) . '&useanim=0">disable animation</a>)';
    }
    else
    {
        $anim_status = 'DISABLED (<a href="?applet=' . htmlspecialchars( $applet ) . '&useanim=1">enable animation</a>)';
    }
    
    $use_animation_query = $_GET['useanim'] ? '1' : '0';*/

    /*$dir = 'drawings/';
    $drawings = array();
        
    if( $handle = @opendir( $dir ) ) {
        while( FALSE !== ( $file = readdir( $handle ) ) )
        { 
            if ( $file != '.' && $file != '..' )
            { 
                $filetype = @filetype( $dir . $file );
                if( $filetype == 'dir' ) $drawings[] = $file;
            } 
            echo( ' ' );
            flush();
        }
        closedir( $handle ); 
    }
    else
    {
        if( is_dir( $dir ) )
        {
            exit( '<p>The drawings directory cannot be read!</p></body></html>' );
        }
        else
        {
            exit( '<p>The drawings directory cannot be found or it is not a directory!</p></body></html>' );
        }
    }
    
    natsort( $drawings );*/
    
    //$drawings_html = '';
    
    /*foreach( $drawings as $d )
    {
        $drawing_applet = trim( file_get_contents( 'drawings/' . $d . '/appletinfo' ) );
        
        $drawings_html .= '<option value="' . htmlspecialchars( $d ) . '">' . htmlspecialchars( date( 'r', $d ) ) . ' (' . $drawing_applet . ')</option>';
    }*/
    
    /*echo <<<EOB
<form method="GET" action="?">
    <p>
        <b>Edit Existing Drawing:</b> <select size="1" name="edit">
            $drawings_html
        </select> <input type="submit" value="Edit" />
    </p>
</form>
EOB;*/
    
$OekakiApplet = new OekakiApplet;
    
    /*if( $_GET['edit'] && is_dir( 'drawings/' . basename( $_GET['edit'] ) ) )
    {
        $save_id = basename( $_GET['edit'] );
        
        $applet = trim( file_get_contents( 'drawings/' . $save_id . '/appletinfo' ) );
        
        if( $applet == 'oekakibbs' )
        {
            $animation_ext = 'oeb';
        }
        else
        {
            $animation_ext = 'pch';
        }*/
if (isset($_POST['replyimage'])) {
    if ($_POST['replyimage']!='0') {
        $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$board_dir."` WHERE `id` = '".mysql_escape_string($_POST['replyimage'])."' AND `IS_DELETED` = '0'");
        if (count($results)==0) {
            die("Invalid reply image.");
        } else {
            foreach($results AS $line) {
                $post_image = $line['image'].'.'.$line['imagetype'];
            }
            if (is_file(TC_BOARDSDIR.$board_dir.'/src/'.$post_image)) {
                $imageDim = getimagesize(TC_BOARDSDIR.$board_dir.'/src/'.$post_image);
                $imgWidth = $imageDim[0];
                $imgHeight = $imageDim[1];
                $_POST['width'] = $imgWidth;
                $_POST['height'] = $imgHeight;
                $OekakiApplet->load_image_url = $board_dir.'/src/'.$post_image;
            } else {
                die("Invalid reply image.");
            }
        }
    }
}
        /*$OekakiApplet->load_animation_url = file_exists( 'drawings/' . $save_id . '/animation.' . $animation_ext ) ? 'drawings/' . $save_id . '/animation.' . $animation_ext : '';
        if( $OekakiApplet->load_animation_url )
        {
            $OekakiApplet->animation = TRUE;
        }
        else
        {
            $OekakiApplet->animation = FALSE;
        }
    }
    else
    {*/
$save_id = time().rand(1,100);
$OekakiApplet->animation = $use_animation;
    /*}*/
    
// Important to applet!
$OekakiApplet->applet_id                        = 'oekaki';

// Applet display
$OekakiApplet->applet_width                     = "100%";
$OekakiApplet->applet_height                    = "100%";

// Image display
$OekakiApplet->canvas_width                     = $_POST['width'];
$OekakiApplet->canvas_height                    = $_POST['height'];

// Saving
$OekakiApplet->url_save                         = 'paint_save.php?applet='.$applet.'&saveid='.$save_id;
$OekakiApplet->url_finish                       = 'board.php?board='.$_POST['board'].'&postoek='.$save_id.'&replyto='.$_POST['replyto'].'';
$OekakiApplet->url_target                       = '_self';

// Format to save
$OekakiApplet->default_format                   = 'png';
    
echo '<table width="100%" height="100%"><tbody><tr><td width="100%">';
switch($applet) {
    case 'shipainter': {
        echo $OekakiApplet->shipainter( 'spainter_all.jar', '/', FALSE );
        break;
    }
    case 'shipainterpro': {
        echo $OekakiApplet->shipainter( 'spainter_all.jar', '/', TRUE );
        break;
    }
}
echo '</td></tr></tbody></table></body>';
    /*echo <<<EOB
</body>
</html>
EOB;*/
?>