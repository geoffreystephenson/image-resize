<?php

/**
 * Image Resize
 *
 * LICENSE
 *
 * @author      Geoffrey Stephenson
 * @license     
 * @version     1.0.1
 */

class Image_Resize 
{		
	/**
	 * initialize
	 */	
	public function resize()
    {
    	return $this;
    }
	
	public function resizeExact($src,$newWidth,$newHeight,$quality=90)
	{		
		/*
		 * load the source image  
		 */ 
		$info = pathinfo($src);
        $extension = strtolower($info['extension']);
        if (in_array($extension, array('jpg', 'jpeg', 'png', 'gif'))) {
            switch ($extension) {
                case 'gif':
                    $sourceImage 		= imagecreatefromgif($src);
                    $doSharpen			= false;
                    $quality			= round(10 - ($quality / 10));
                    $output_type		= 'imagegif';
                    break;
                case 'png':
                    $sourceImage 		= imagecreatefrompng($src);
                    $doSharpen			= false;
                    $quality			= round(10 - ($quality / 10));
                    $output_type		= 'imagepng';
                    break;
                case 'jpeg':
                case 'jpg':
                    $sourceImage 		= imagecreatefromjpeg($src);
                    $doSharpen			= true;
                    $output_type		= 'imagejpeg';
                    break;
                default:
                	throw new Exception('Image extension is invalid or not supported.');
                	break;
            }
		} 
		
		/**
		 * get image size
		 */
		$width			= $this->_getWidth($sourceImage);		
		$height	 		= $this->_getHeight($sourceImage);
		
		/**
		 * create a new, virtual image
		 */
		$tmp = imagecreatetruecolor($newWidth, $newHeight);
		
		if ($extension == 'gif' || $extension == 'png')
			$this->_handleTransparentColor($tmp, $newWidth, $newHeight);

		/**
		 * copy source image at a resized size
		 */
		imagecopyresampled($tmp, $sourceImage,0,0,0,0, $newWidth, $newHeight, $width, $height);
			
		/**
		 * sharpen the image
		 */
		// Sharpen the image based on two things:
		//	(1) the difference between the original size and the final size
		//	(2) the final size
		$sharpness	= $this->_findSharp($width, $newWidth);
		$sharpenMatrix	= array(
			array(-1, -2, -1),
			array(-2, $sharpness + 12, -2),
			array(-1, -2, -1)
		);
		$divisor		= $sharpness;
		$offset			= 0;
		imageconvolution($tmp, $sharpenMatrix, $divisor, $offset);
		
		/**
		 * create the physical thumbnail image  
		 */
		ob_start();
		
		/**
		 * use the proper output type to create the image 
		 */
		switch($output_type)
		{
			case 'imagegif': // using imagepng instead of imagegif	
				imagepng($tmp, null, 6);
				break;
			case 'imagepng': 	
				imagepng($tmp, null, 6);
				break;
			case 'imagejpeg':
				imagejpeg($tmp, null, $quality);
				break;
			default :
				throw new Exception('Image cannot be created.');
				break;
				
		}				
		$final_image = ob_get_contents();

    	ob_end_clean();	
    	
    	// done and done!	
		return $final_image;
	}
	
	public function resizeRatio($src,$max_width,$max_height,$quality=90)
	{		
		/**
		 * load the source image  
		 */ 
		$info = pathinfo($src);
        $extension = strtolower($info['extension']);
        if (in_array($extension, array('jpg', 'jpeg', 'png', 'gif'))) {
            switch ($extension) {
                case 'gif':
                    $sourceImage 		= imagecreatefromgif($src);
                    $doSharpen			= false;
                    $quality			= round(10 - ($quality / 10));
                    $output_type		= 'imagegif';
                    break;
                case 'png':
                    $sourceImage 		= imagecreatefrompng($src);
                    $doSharpen			= false;
                    $quality			= round(10 - ($quality / 10));
                    $output_type		= 'imagepng';
                    break;
                case 'jpeg':
                case 'jpg':
                    $sourceImage 		= imagecreatefromjpeg($src);
                    $doSharpen			= true;
                    $output_type		= 'imagejpeg';
                    break;
                default:
                	throw new Exception('Image extension is invalid or not supported.');
                	break;
            }
		} 
		
		/**
		 * get image size
		 */
		$width		= $this->_getWidth($sourceImage);		
		$height	 	= $this->_getHeight($sourceImage);
		
		/**
		 * get ratios
		 */
		$offset_x	= 0;
		$offset_y	= 0;
		
		if ($max_width===$max_height) // if square image
		{
			$ratioComputed		= $width / $height;
			$cropRatioComputed	= (float) $max_width / (float) $max_height;
			
			if ($ratioComputed < $cropRatioComputed)
			{ // Image is too tall so we will crop the top and bottom
				$origHeight	= $height;
				$height		= $width / $cropRatioComputed;
				$offset_y	= ($origHeight - $height) / 2;
			}
			else if ($ratioComputed > $cropRatioComputed)
			{ // Image is too wide so we will crop off the left and right sides
				$origWidth	= $width;
				$width		= $height * $cropRatioComputed;
				$offset_x	= ($origWidth - $width) / 2;
			}
		} 
	 
		$x_ratio = $max_width / $width;
	    $y_ratio = $max_height / $height;
	
	    if (($width <= $max_width) && ($height <= $max_height)) {
	        $tn_width = $width;
	        $tn_height = $height;
	        } elseif (($x_ratio * $height) < $max_height){
	            $tn_height = ceil($x_ratio * $height);
	            $tn_width = $max_width;
	        } else {
	            $tn_width = ceil($y_ratio * $width);
	            $tn_height = $max_height;
	    }		

		/**
		 * create a new, temporary image
		 */
		$tmp = imagecreatetruecolor($tn_width, $tn_height);
		
		if ($extension == 'gif' || $extension == 'png')
			$this->_handleTransparentColor($tmp, $tn_width, $tn_height);
		
		/**
		 * copy source image at a resized size
		 */
		imagecopyresampled($tmp, $sourceImage,0,0, $offset_x, $offset_y, $tn_width, $tn_height, $width, $height);
			
		/**
		 * sharpen the image
		 */
		// Sharpen the image based on two things:
		//	(1) the difference between the original size and the final size
		//	(2) the final size
		if ($doSharpen==true)
		{
			$sharpness	= $this->_findSharp($width, $tn_width);
			$sharpenMatrix	= array(
				array(-1, -2, -1),
				array(-2, $sharpness + 12, -2),
				array(-1, -2, -1)
			);
			$divisor		= $sharpness;
			$offset			= 0;
			imageconvolution($tmp, $sharpenMatrix, $divisor, $offset);
		}
		
		/**
		 * create the physical thumbnail image 
		 */
		ob_start();
	
		/**
		 * use the proper output type to create the image 
		 */
		switch($output_type)
		{
			case 'imagegif':  // using imagepng instead of imagegif	
				imagepng($tmp, null, 6);
				break;
			case 'imagepng': 	
				imagepng($tmp, null, 6);
				break;
			case 'imagejpeg':
				imagejpeg($tmp, null, $quality);
				break;
			default:
				throw new Exception('Image cannot be created.');
				break;
				
		}				
		$final_image = ob_get_contents();

    	ob_end_clean();	
    	
    	// done and done!	
		return $final_image;
	}
	
	protected function _getWidth($src)
	{
		return imagesx($src);
	}
	
	protected function _getHeight($src)
	{
		return imagesy($src);
	}
	
	protected function _handleTransparentColor($image, $width, $height)
	{
		$image = $image;
		
		// If this is a GIF or a PNG, we need to set up transparency	
		imagealphablending($image, false);
		imagesavealpha($image,true);
		$transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
		imagefilledrectangle($image, 0, 0, $width, $height, $transparent);

		return $image;
	}
	
	protected function _findSharp($orig, $final) 
	{
		$final	= $final * (750.0 / $orig);
		$a		= 52;
		$b		= -0.27810650887573124;
		$c		= .00047337278106508946;	
		$result = $a + $b * $final + $c * $final * $final;
		
		return max(round($result), 0);
	}	
} ?>
