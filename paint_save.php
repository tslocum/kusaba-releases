<?php

	require("config.php");
	require($chan_rootdir."/inc/OekakiInput.php");

    if( !function_exists( 'file_put_contents' ) )
    {
        function file_put_contents( $filename, $data )
        {
            $fp = @fopen( $filename, 'w' );
            fwrite( $fp, $data );
            fclose( $fp );
        }
    }
    
    $OekakiInput = new OekakiInput;
    
    $applet = $_GET['applet'];
    
    do
    {
        $data = $OekakiInput->autoprocess( $applet, $HTTP_RAW_POST_DATA, $anim_ext, $print_ok, $print_error_prefix, $response_mimetype, $error );
        
        if( $error )
        {
            break;
        }
        
        $save_id = basename( $_GET['saveid'] );
        $save_dir = "tcdrawings/";
        
        @mkdir( $save_dir, 0777 );
        
        if( !is_writable( $save_dir ) )
        {
            $error = 'CANNOT_WRITE';
            break;
        }
        
        file_put_contents( $save_dir . 'image', $data['IMAGE'] );
        
        $image_info = getimagesize( $save_dir . 'image' );
        
        if( $image_info == FALSE )
        {
            $error = 'NOT_IMAGE';
            @unlink( $save_dir . 'image' );
            break;
        }
        
        if( $image_info[2] != 2 && $image_info[2] != 3 )
        {
            $error = 'INVALID_FILETYPE';
            @unlink( $save_dir . 'image' );
            break;
        }
        
        if( $image_info[2] == 2 )
        {
            rename( $save_dir . 'image', $save_dir . 'image.jpg' );
        }
        elseif( $image_info[2] == 3 )
        {
            rename( $save_dir . 'image', $save_dir . $save_id.'.png' );
        }
        
        //file_put_contents( $save_dir . 'appletinfo', $applet );
        /*if( $data['ANIMATION'] )
        {
            file_put_contents( $save_dir . 'animation.' . $anim_ext, $data['ANIMATION'] );
        }*/
    }
    while( FALSE );
    
    header( "Content-type: {$response_mimetype}" );
    if( $error )
    {
        $errors = array(
            'INVALID_APPLET'    => 'An invalid applet was specified. Save a screenshot of your work in case of continued failure.',
            'NO_IMAGE_DATA'     => 'There was no image data sent. Please reattempt your save (and save a screenshot just in case of continued failure).',
            'INVALID_DATA'      => 'Invalid image data was sent. The error may be that the applet you are using is configured incorrectly (POO compatibility was be enabled). Save a screenshot of your work in case of continued failure.',
            // Following errors introduced by the script
            'CANNOT_WRITE'      => 'The server has encountered an error saving your image. Save a screenshot of your work in case of continued failure.',
            'NOT_IMAGE'         => 'The data sent was not an image. Please reattempt your save (and save a screenshot just in case of continued failure).',
            'INVALID_FILETYPE'  => 'The data sent was not a JPG or PNG file. Please reattempt your save (and save a screenshot just in case of continued failure).',
            );
            
        echo( ( $print_error_prefix ? "error\n" : '' ) . $errors[ $error ] );
    }
    elseif( $print_ok )
    {
        echo "ok";
    }
?>