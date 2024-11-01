<?php
/*
Plugin Name: WPVN - Thumbnailer
Plugin URI: http://link2caro.net/read/wpvn-thumbnailer/
Description: Usage : l2c_the_image($post_ID=false, $width=64, $height=64, $crop=false, $custom_thumb=true, $quality=70, $default_img=false, $default_title=false, $default_alt=false) | l2c_the_image_url($post_ID=false, $width=64, $height=64, $crop=false, $custom_thumb=true, $quality=70, $default_img=false, $default_title=false, $default_alt=false) | l2c_get_the_image($post_ID=false, $width=64, $height=64, $crop=false, $custom_thumb=true, $quality=70, $default_img=false, $default_title=false, $default_alt=false) | l2c_get_the_image_url($post_ID=false, $width=64, $height=64, $crop=false, $custom_thumb=true, $quality=70, $default_img=false, $default_title=false, $default_alt=false)
Version: 0.6.7
Author: Minh Quan TRAN (aka link2caro - A member of WordPressVN)
Author URI: http://link2caro.net/donate/
*/

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die('403 - Forbidden'); }
?>
<?php
/**
 * Copyright 2009 - link2caro
 * GNU General Public License
 * Some functions derived from WordPress's functions
 *
 * ---
 * Priority of image choosing:
 * - first image declared in custom field
 * - first image attached
 * - first image in img tag
 *
 * Custom fields use these sets
 * (img, img_title, img_alt, img_class) -> CSS: img-[img_class]
 * (image, image_title, image_alt, image_class) -> CSS: image-[image_class]
 * (thumb, thumb_title, thumb_alt, thumb_class) -> CSS: thumb-[image_class]
 * (thumbnail, thumbnail_title, thumbnail_alt, thumbnail_class) -> CSS: thumbnail-[image_class]
 * The value of img_class or image_class or thumbnail_class is case non-sensitve (Ex: CSSClass will be cssclass)
 * case sensitive and non-permutable
 */
$wpvn_thumbnailer_version = '0.6.7';

if(!defined('IMG_UNIQUE_NAME')) define('IMG_UNIQUE_NAME', true);
if(!defined('IMG_SCAN_TAG')) define('IMG_SCAN_TAG', false);
/* If you want to use a seperate folder for generated image, uncomment 2 following lines and adapt theme to your installation */
if(!defined('IMG_CACHE_PATH') && '' != IMG_CACHE_PATH) define('IMG_CACHE_PATH',get_template_directory().'/cache');
if(!defined('IMG_CACHE_URL') && '' != IMG_CACHE_URL) define('IMG_CACHE_URL',get_template_directory_uri().'/cache');

if( defined( 'IMG_CACHE_PATH' ) && '' != IMG_CACHE_PATH && !file_exists(IMG_CACHE_PATH) ) {
   mkdir(IMG_CACHE_PATH);
   chmod(IMG_CACHE_PATH, 0755);
}

/**
 *Display the image URL
 *@param mixed $post the post
 *@return boolean|string URL of the image | false on failure
 */
function l2c_the_image_url($post_ID=false, $width=64, $height=64, $crop=false, $custom_thumb=true, $quality=70, $default_img=false, $default_title=false, $default_alt=false) {
   echo l2c_get_the_image($post_ID, $width, $height, $crop, $custom_thumb, $quality, $default_img, $default_title, $default_alt);
}

/**
 *Get the image URL
 *@param mixed $post the post
 *@return boolean|string URL of the image | false on failure
 */
function l2c_get_the_image_url($post_ID=false, $width=64, $height=64, $crop=false, $custom_thumb=true, $quality=70, $default_img=false, $default_title=false, $default_alt=false) {
   return l2c_get_the_image($post_ID, $width, $height, $crop, $custom_thumb, $quality, $default_img, $default_title, $default_alt);
}

/**
 *Get the image URL, but preferred using l2c_get_the_image_url
 *@param mixed $post the post
 *@return boolean|string URL of the image | false on failure
 */
function l2c_get_the_image($post_ID=false, $width=64, $height=64, $crop=false, $custom_thumb=true, $quality=70, $default_img=false, $default_title=false, $default_alt=false) {
   $img = l2c_prepare_image($post_ID);

   if(empty($img[0])) :
      $image = $default_img;
   else :
      $image = $img[0];
   endif;

   if(empty($image))
      return false;

   if($custom_thumb) :
      $image = l2c_get_image_resize_url($image, $width, $height, $crop, $quality);
   endif;

   return $image;
}

/**
 *Display the thumbnail of a post
 *@param mixed $post the post
 *@param string $default_size
 *@param string $default_img
 *@return boolean|string IMG tag | false on failure
 */
function l2c_the_image($post_ID=false, $width=64, $height=64, $crop=false, $custom_thumb=true, $quality=70, $default_img=false, $default_title=false, $default_alt=false) {
   $img = l2c_prepare_image($post_ID);

   if(empty($img[0])) :
      $image = $default_img;
      $image_title = $default_title;
      $image_alt = $default_alt;
   else :
      $image = $img[0];
      $image_title = (empty($img[1])) ? $default_title : $img[1];
      $image_alt = (empty($img[2])) ? $default_alt : $img[2];
      $image_class = $img[3];
   endif;

   if(empty($image))
      return false;

   if($custom_thumb) :
      $image = l2c_get_image_resize_url($image, $width, $height, $crop, $quality);
   endif;

   if(!empty($image)) :
      $output = '<span class="the_image"><img src="'.$image.'" ';
      $output .= 'title="'.$image_title.'" ';
      $output .= 'alt="'.$image_alt.'" ';
      $output .= 'class="'.$image_class.'" ';
      $output .= 'width="'.$width.'px" height="'.$height.'px"/></span>';
   endif;

   echo $output;
}

/**
 *Prepare image thumbnail
 *@param
 *@return
 */
function l2c_prepare_image($post_ID=false) {
    global $post;

    if( FASLE === $post_ID || 1 > $post_ID )
        $post_ID = $post->ID;

   // Check for image declared in custom fields
   $custom_fields = array('thumb', 'thumbnail', 'image','img');
   if( isset($custom_fields) ) {
      $i = 0;
      foreach($custom_fields as $custom_field) {
      // Check custom field values for image, image title, image alt, and image class
         $image = get_post_meta($post_ID, $custom_field, true);
         $image_title = get_post_meta($post_ID, $custom_field.'_title', true);
         $image_alt = get_post_meta($post_ID, $custom_field.'_alt', true);
         $image_cls = get_post_meta($post_ID, $custom_field.'_class');
         if(!empty($image))
            break;
      }
      if(!empty($image_cls)) {
         foreach($image_cls as $class) {
            $image_class .= $class.' ';
         }
      }

      if($image)
         return array($image, $image_title, $image_alt, $image_class);
   }

   // If there is no image set through custom fields, check post attachments
   if( empty($image) ) {
      if($img = l2c_find_attachment_image($post_ID)) :
         $image = $img[0]; $image_title = $img[1]; $image_alt = $img[2];
      else :
         $image = false; $image_title = false; $image_alt = false;
      endif;
      $image_class = false;

      if($image)
         return array($image, $image_title, $image_alt, $image_class);
   }

	// Last search, search for the first img tag
   if( defined('IMG_SCAN_TAG') && TRUE === IMG_SCAN_TAG ) {
      $post_content = get_post_field('post_content',$post_ID);
      /*preg_match_all ("@img[\s]+[^>]*?src[\s]?=[\s\"\']+(.*?)[\"\']+.*?>@", $post_content, $urls);*/
      preg_match ("@img[\s]+[^>]*?src[\s]?=[\s\"\']+(.*?)[\"\']+.*?>@", $post_content, $url);
      preg_match ("@title[\s]?=[\s\"\']+(.*?)[\"\']+.*?@", $url[0], $title);
      preg_match ("@alt[\s]?=[\s\"\']+(.*?)[\"\']+.*?@", $url[0], $alt);
      $image = ( $url[1] ) ?  $url[1] : false;
      $image_title = ( $title[1] ) ? $title[1] : false;
      $image_alt = ( $alt[1] ) ? $alt[1] : false;
      $image_class = false;
      if($image)
         return array($image, $image_title, $image_alt, $image_class);
   }

   // Return false array if there is no image found
   return array(false,false,false,false);
}

/**
 * Find an attachment image
 * @param int $post_ID ID of the post
 * @return path to image
 */
function l2c_find_attachment_image($post_ID=false) {
	 if( FALSE === $post_ID )
		  return false;

   $upload = wp_upload_dir('');

   $attachments = get_children(array('post_parent' => $post_ID, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID'));

   if( $attachments) :
      foreach($attachments as $id => $attachment) :
         //$attachment->guid : URL to image
         //str_replace($upload['basedir'], '', get_attached_file($id)): relative path to image
         return array(str_replace($upload['basedir'], '', get_attached_file($id)),$attachment->post_title,$attachment->post_excerpt);
      endforeach;
   else :
      return false;
   endif;
}

/**
 * Retrieve calculated resized dimensions for use in imagecopyresampled().
 *
 * Calculate dimensions and coordinates for a resized image that fits within a
 * specified width and height. If $crop is true, the largest matching central
 * portion of the image will be cropped out and resized to the required size.
 *
 * @param int $orig_w Original width.
 * @param int $orig_h Original height.
 * @param int $dest_w New width.
 * @param int $dest_h New height.
 * @param bool $crop Optional, default is false. Whether to crop image or resize.
 * @return bool|array False, on failure. Returned array matches parameters for imagecopyresampled() PHP function.
 */
function l2c_image_resize_dimensions($orig_w, $orig_h, $dest_w, $dest_h, $crop=false) {

   if (0 >= $orig_w || 0 >= $orig_h)
      return false;
   // at least one of dest_w or dest_h must be specific
   if (0 >= $dest_w && 0 >= $dest_h)
      return false;

   if ( $crop ) {
      // crop the largest possible portion of the original image that we can size to $dest_w x $dest_h
      $aspect_ratio = $orig_w / $orig_h;
      $new_w = min($dest_w, $orig_w);
      $new_h = min($dest_h, $orig_h);
      if ( !$new_w ) {
         $new_w = intval($new_h * $aspect_ratio);
      }
      if ( !$new_h ) {
         $new_h = intval($new_w / $aspect_ratio);
      }

      $size_ratio = max($new_w / $orig_w, $new_h / $orig_h);

      $crop_w = ceil($new_w / $size_ratio);
      $crop_h = ceil($new_h / $size_ratio);

      $s_x = floor(($orig_w - $crop_w)/2);
      $s_y = floor(($orig_h - $crop_h)/2);
   }
   else {
      // don't crop, just resize using $dest_w x $dest_h as a maximum bounding box
      $crop_w = $orig_w;
      $crop_h = $orig_h;

      $s_x = 0;
      $s_y = 0;

      list( $new_w, $new_h ) = wp_constrain_dimensions( $orig_w, $orig_h, $dest_w, $dest_h );
   }

   // if the resulting image would be the same size or larger we don't want to resize it
   if ( $new_w >= $orig_w && $new_h >= $orig_h )
      return array(0, 0, $s_x, $s_y, $orig_w, $orig_h, $crop_w, $crop_h);;

   // the return array matches the parameters to imagecopyresampled()
   // int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
   return array(0, 0, $s_x, $s_y, $new_w, $new_h, $crop_w, $crop_h);

}

/**
 * Load an image from a string, if PHP supports it.
 *
 * @param string $file Filename of the image to load.
 * @return resource The resulting image resource on success, Error string on failure.
 */
function l2c_load_image( $file ) {
   if ( is_numeric( $file ) )
      $file = get_attached_file( $file );

   if ( !file_exists( $file ) )
      return sprintf(__("File '%s' doesn't exist?"), $file);

   if ( !function_exists('imagecreatefromstring') )
      return __('The GD image library is not installed.');

   // Set artificially high because GD uses uncompressed images in memory
   @ini_set('memory_limit', '256M');
   $image = imagecreatefromstring( file_get_contents( $file ) );

   if ( !is_resource( $image ) )
      return sprintf(__("File '%s' is not an image."), $file);

   return $image;
}

/**
 * Get the URL of the resized image
 *
 * @param string $file Image file path.
 * @param int $max_w Maximum width to resize to.
 * @param int $max_h Maximum height to resize to.
 * @param bool $crop Optional. Whether to crop image or resize.
 * @param string $suffix Optional. File Suffix.
 * @param string $dest_path Optional. New image file path.
 * @param int $jpeg_quality Optional, default is 90. Image quality percentage.
 * @return mixed WP_Error on failure. String with new destination path.
 * Array of dimensions from {@link image_resize_dimensions()}
 */
function l2c_get_image_resize_url( $file, $max_w, $max_h, $crop=false, $quality=70, $suffix=null, $dest_path=null, $force=false ) {
   if ( defined( 'IMG_CACHE_URL' ) && '' != IMG_CACHE_URL ) :
      $url = IMG_CACHE_URL;
   else :
      $upload = wp_upload_dir();
      if ( FALSE === strpos( $file, 'http://' ) ) :
         //$files = '/2009/04/files.png'
         $info = pathinfo($file);
         $subdir = $info['dirname'];
         $url = $upload['baseurl'].$subdir;
      else :
         $url = $upload['url'];
      endif;
   endif;

   return $url.'/'.l2c_get_image_resize( $file, $max_w, $max_h, $crop, $quality, $suffix, $dest_path, $force );
}

/**
 * Resize an image from URL or PATH and put it in upload_path with option uploads_use_yearmonth_folders,
 * if UPLOADS is defined it will use this constant
 * relative PATH begins without '/', Ex: wp-content/uploads for default value
 * absolute PATH is accepted
 *
 * @param string $file Image file path.
 * @param int $max_w Maximum width to resize to.
 * @param int $max_h Maximum height to resize to.
 * @param bool $crop Optional. Whether to crop image or resize.
 * @param string $suffix Optional. File Suffix.
 * @param string $dest_path Optional. New image file path.
 * @param int $jpeg_quality Optional, default is 90. Image quality percentage.
 * @return mixed WP_Error on failure. string new filename
 * Array of dimensions from {@link image_resize_dimensions()}
 */
function l2c_get_image_resize( $file, $max_w, $max_h, $crop=false, $quality=70, $suffix=null, $dest_path=null, $force=false ) {

   if ( FALSE === strpos( $file, 'http://' ) ) {
   	$_url = false;
      $_dir = wp_upload_dir();
      $file = $_dir['basedir'].$file;
   } else {
   	$_url = true;
		if (!ini_get('allow_url_fopen')) return false;
   }

   // Get old and new dimension
   list($orig_w, $orig_h, $orig_type) = @getimagesize( $file );

   $dims = l2c_image_resize_dimensions($orig_w, $orig_h, $max_w, $max_h, $crop);
   if (!$dims) return $dims;
   list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $dims;

   // Get dir and ext in order to check if the image exists already
   if ( $_url ) {
      $info = pathinfo($file);
      $dir = $info['dirname'];
      $ext = strtolower($info['extension']);
   } else {
      $dir = wp_upload_dir();
      $dir = $dir['path'];
      switch ($orig_type) {
         case IMAGETYPE_GIF:
            $ext = 'gif';
         break;
         case IMAGETYPE_JPEG:
            $ext = 'jpg';
            break;
         case IMAGETYPE_PNG:
            $ext = 'png';
            break;
         default:
            return new WP_Error('error_loading_image', $image);
      }
   }

   // $suffix will be appended to the destination filename, just before the extension
   if ( !$suffix )
      $suffix = "{$dst_w}x{$dst_h}";

   $name = basename($file, ".{$ext}");
	if ( defined( 'IMG_UNIQUE_NAME' ) && TRUE === IMG_UNIQUE_NAME ) {
		$name = md5($name);
	}
   if ( defined( 'IMG_CACHE_PATH' ) && '' != IMG_CACHE_PATH )
      $dir = IMG_CACHE_PATH;
   elseif ( !is_null($dest_path) and $_dest_path = realpath($dest_path) )
      $dir = $_dest_path;
   $destfilename = "{$dir}/{$name}-{$suffix}.{$ext}";

   if( file_exists($destfilename) && !$force )
      return "{$name}-{$suffix}.{$ext}";
   
   if ( $_url ) {
      $image = caro_load_image( $file );
      if ( !is_resource( $image ) )
         return new WP_Error('error_loading_image', $image);
   } else {
      switch ($orig_type) {
         case IMAGETYPE_GIF:
            $image = imagecreatefromgif($file);
            break;
         case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($file);
            break;
         case IMAGETYPE_PNG:
            $image = imagecreatefrompng($file);
            break;
      }
   }

   $newimage = imagecreatetruecolor( $dst_w, $dst_h );

   // preserve PNG transparency
   if ( IMAGETYPE_PNG == $orig_type && function_exists( 'imagealphablending' ) && function_exists( 'imagesavealpha' ) ) {
      imagealphablending( $newimage, false);
      imagesavealpha( $newimage, true);
   }

   imagecopyresampled( $newimage, $image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

   // we don't need the original in memory anymore
   imagedestroy( $image );

   //Ensure that $quality does not out of range
   if (0 > $quality || 100 < $quality) $quality = 70;
   
   switch ($orig_type) {
   	case IMAGETYPE_GIF:
   		if ( !imagegif( $newimage, $destfilename ) ) return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
   		break;
   	case IMAGETYPE_PNG:
   		// PNG: 0-max | 9-max compression
		if ( 90 < $quality ) $quality = 90;
      	$quality = ( 90 - $quality ) / 10;
      	if (!imagepng( $newimage, $destfilename, $quality ) ) return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
      	break;
   	default:
   		// all other formats are converted to jpg
		$destfilename = "{$dir}/{$name}-{$suffix}.jpg";
      	if (!imagejpeg( $newimage, $destfilename, $quality ) ) return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
   		break;
   }
   
   imagedestroy( $newimage );

   // Set correct file permissions
   $stat = stat( dirname( $destfilename ) );
   $perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
   @chmod( $destfilename, $perms );

   return "{$name}-{$suffix}.{$ext}";
}
?>