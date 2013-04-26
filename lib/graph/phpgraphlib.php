<?php 
///////////////////////////////////////////////////////////
//PHPGraphLib -  PHP Graphing Library v1.13
//Author: Elliott Brueggeman
//Please visit www.ebrueggeman.com for usage policy
//and documentation + examples
///////////////////////////////////////////////////////////
class PHPGraphLib
{
	//---------------USER CHANGEABLE DEFAULTS----------------/
	var $height=300;
	var $width=400;
	var $data_max_allowable=9999999999999999;
	var $data_min_allowable=-9999999999999999;
	//SET TO ACTUAL FONT HEIGHTS AND WIDTHS USED
	var $title_char_width=6;
	var $title_char_height=12;
	var $text_width=6;
	var $text_height=12;
	var $data_value_text_width=6;
	var $data_value_text_height=12;
	//PADDING BETWEEN AXIS AND VALUE DISPLAYED
	var $axis_value_padding=5;
	//SPACE B/T TOP OF BAR OR CENTER OF POINT AND DATA VALUE
	var $data_value_padding=5; 
	//DEFAULT MARGIN % OF WIDTH / HEIGHT
	var $x_axis_default_percent=12; 
	var $y_axis_default_percent=8;
	//DATA POINT DIAMETER IN PX
	var $data_point_width=6;
	//USER CHANGEABLE DEFAULT BOOLEANS (SHOW ELEMENT BY DEFAULT?)
	var $bool_bar_outline=true;
	var $bool_x_axis=true;
	var $bool_y_axis=true;
	var $bool_x_axis_values=true;
	var $bool_y_axis_values=true;
	var $bool_grid=true;
	var $bool_line=false;
	var $bool_data_values=false;
	var $bool_data_points=false;
	//----------INTERNAL VARIABLES (DO NOT CHANGE)------------/
	var $image;
	var $error;
	var $bool_x_axis_setup=false;
	var $bool_y_axis_setup=false;
	var $bool_data=false;
	var $bool_bars_generate=true;
	var $bool_bars=true;
	var $bool_background=false;
	var $bool_title=false;
	var $bool_all_negative=false;
	var $bool_all_positive=false;
	var $bool_gradient=false;
	var $bool_gradient_colors_found=false;
	//COLOR VARS
	var $background_color;
	var $grid_color;
	var $bar_color;
	var $outline_color;
	var $x_axis_text_color;
	var $y_axis_text_color;
	var $title_color;
	var $x_axis_color;
	var $y_axis_color;
	var $data_point_color;
	var $data_value_color;
	var $line_color;
	var $goal_line_color;
	//GRADIENT COLORS STORED AS ARRAYS, NOT ALLOCATED COLOR
	var $gradient_color_1;
	var $gradient_color_2;
	var $gradient_color_array;
	var $gradient_max=200;
	var $gradient_handicap=0;
	//DATA VARS
	var $data_array;
	var $data_count;
	var $data_min;
	var $data_max;
	//BAR VARS / SCALE
	var $bar_spaces;
	var $bar_width;
	var $space_width;
	var $unit_scale;
	var $goal_line_array;
	//TEXT / FONT
	var $title_x;
	var $title_y;
	//AXIS POINTS
	var $x_axis_x1;
	var $x_axis_y1;
	var $x_axis_x2;
	var $x_axis_y2;
	var $y_axis_x1;
	var $y_axis_y1;
	var $y_axis_x2;
	var $y_axis_y2;
	var $x_axis_margin; //AKA BOTTOM MARGIN
	var $y_axis_margin; //AKA LEFT MARGIN
	var $top_margin=0;
	var $right_margin=0;
	var $range_divisor_factor=25; //CONTROLS AUTO-ADJUSTING GRID INTERVAL
	var $data_point_array;
	//--------------------"PUBLIC" CONSTRUCTOR----------------------//
	function PHPGraphLib($width='', $height='')
	{
		if(!empty($width)&&!empty($height))
		{
			$this->width=$width;
			$this->height=$height;
		}
		$this->initialize();
		$this->allocateColors(); //SETS DEFAULT COLORS	
	}
	//----------------"PRIVATE" MAIN PROGRAM FUNCTIONS ----------------//
	function initialize()
	{
		header("Content-type: image/png");
		$this->image = @imagecreate($this->width, $this->height)
			or die("Cannot Initialize new GD image stream - Check your PHP setup");
		$this->data_point_array=array();
		$this->goal_line_array=array();
	}
	function createGraph() //MAIN CLASS METHOD - CALLED LAST
	{
		//SETUP AXIS IF NOT ALREADY SETUP BY USER
		if($this->bool_data){
			if(!$this->bool_x_axis_setup){ $this->setupXAxis(); }
			if(!$this->bool_y_axis_setup){ $this->setupYAxis(); }
			//CALCULATIONS
			$this->calcTopMargin();
			$this->calcRightMargin();
			$this->calcCoords();
			$this->setupData();
			//START CREATING ACTUAL IMAGE ELEMENTS
			if($this->bool_background){ $this->generateBackgound(); }
			//ALWAYS GEN GRID VALUES, EVEN IF NOT DISPLAYED
			$this->generateGrid();
			if($this->bool_bars_generate){$this->generateBars(); }
			if($this->bool_data_points){$this->generateDataPoints(); }
			if($this->bool_title) { $this->generateTitle(); }
			if($this->bool_x_axis){ $this->generateXAxis(); }
			if($this->bool_y_axis){ $this->generateYAxis(); }
		}
		else
		{
			$this->error[]="No valid data added to graph. Add data with the addData() function.";
		}
		//DISPLAY ERRORS
		$this->displayErrors();
		//OUTPUT TO BROWSER
		imagepng($this->image);
		imagedestroy($this->image);
	}
	function setupData()
	{
		$this->bar_spaces=($this->data_count*2)+1;
		$unit_width=($this->width-$this->y_axis_margin-$this->right_margin)/(($this->data_count*2)+($this->data_count));
		if($unit_width<1)
		{	
			//ERROR UNITS TOO SMALL, TOO MANY DATA POINTS OR NOT LARGE ENOUGH GRAPH
			$this->bool_bars_generate=false;
			$this->error[]="Graph too small or too many data points.";
		}
		else
		{
			//DEFAULT SPACE BETWEEN BARS IS 1/2 THE WIDTH OF THE BAR
			//FIND BAR AND SPACE WIDTHS. BAR = 2 UNITS, SPACE = 1 UNIT
			$this->bar_width=2*$unit_width;
			$this->space_width=$unit_width;		
			//NOW CALCULATE HEIGHT (SCALE) UNITS
			$availVertSpace=$this->height-$this->x_axis_margin-$this->top_margin;
			if($availVertSpace<1)
			{
				//ERROR SCALE UNITS TOO SMALL, X AXIS MARGIN TOO BIG OR GRAPH HEIGHT NOT TALL ENOUGH
			}
			else
			{
				//START AT Y VALUE 0 OR DATA MIN, WHICHEVER IS LESS
				$graphBottomScale=($this->data_min<0) ? $this->data_min : 0;
				$graphTopScale=($this->data_max<0) ? 0 : $this->data_max;
				$graphScaleRange=$graphTopScale-$graphBottomScale;
				$this->unit_scale=$availVertSpace/$graphScaleRange;			
				//NOW ADJUST X AXIS IN Y VALUE IF NEGATIVE VALUES
				if($this->data_min<0)
				{
					$this->x_axis_y1-=(int)($this->unit_scale*abs($this->data_min));
					$this->x_axis_y2-=(int)($this->unit_scale*abs($this->data_min));
				}
			}
			$this->bool_bars_generate=true;
		}
	}
	function generateBars()
	{
		$barCount=0;
		$xStart=$this->y_axis_x1+$this->space_width/2;
		foreach($this->data_array as $key => $item)
		{
			$x1=(int)$xStart;
			$y1=(int)($this->x_axis_y1-($item*$this->unit_scale));
			$x2=(int)($xStart+$this->bar_width);
			$y2=(int)$this->x_axis_y1;       
			
			//DRAW BAR 
			if($this->bool_bars)
			{
				if($this->bool_gradient)
				{
					//DRAW GRADIENT IF DESIRED
					$this->drawGradientBar($x1, $y1, $x2, $y2, $this->gradient_color_1, $this->gradient_color_2);
				}
				else
				{
					//IF/ELSE NECESSARY B/C OF BUG IN ARG ORDER OF imagefilledrectangle() FUNCTION
					if($y1<$y2){ imagefilledrectangle($this->image, $x1, $y1,$x2, $y2,  $this->bar_color); }
					else{ imagefilledrectangle($this->image, $x1, $y2,$x2, $y1,  $this->bar_color); }
				}
				//DRAW BAR OUTLINE	
				if($this->bool_bar_outline)
				{ 
					imagerectangle($this->image,  $x1, $y2, $x2, $y1, $this->outline_color); 
				}
			}
			// DRAW LINE
			if($this->bool_line)
			{
				$lineX1=$x1+($this->bar_width)/2;
				$lineY1=$y1;
				if(isset($lineX2))
				{
					imageline($this->image, $lineX2, $lineY2, $lineX1, $lineY1, $this->line_color);
					$lineX2=$lineX1;
					$lineY2=$lineY1;
				}
				else
				{
					$lineX2=$lineX1;
					$lineY2=$lineY1;
				}	
			}
			// DISPLAY DATA POINTS
			if($this->bool_data_points)
			{
				//DONT DRAW DATAPOINTS HERE OR WILL OVERLAP POORLY WITH LINE
				//INSTEAD COLLECT COORDINATES
				$pointX=$x1+($this->bar_width)/2;
				$this->data_point_array[]=array($pointX,  $y1);
			}
			// DISPLAY DATA VALUES
			if($this->bool_data_values)
			{
				$dataX=($x1+($this->bar_width)/2)-((strlen($item)*$this->data_value_text_width)/2);
				$dataY=($item>=0) ? $y1-$this->data_value_padding-$this->data_value_text_height : $y1+$this->data_value_padding;
				imagestring($this->image, 2, $dataX, $dataY, $item,  $this->data_value_color);
			}
			
			//WRITE X AXIS VALUE 
			if($this->bool_x_axis_values)
			{
				if($this->bool_all_negative)
				{
					//WE MUST PUT VALUES ABOVE 0 LINE
					$textVertPos=(int)($this->y_axis_y2-$this->axis_value_padding);
				}
				else
				{
					//MIX OF BOTH POS AND NEG NUMBERS
					//WRITE VALUE Y AXIS BOTTOM VALUE (WILL BE UNDER BOTTOM OF GRID EVEN IF X AXIS IS FLOATING DUE TO
					$textVertPos=(int)($this->y_axis_y1+strlen($key)*$this->text_width+$this->axis_value_padding);
				}
				$textHorizPos=(int)($xStart+($this->bar_width/2)-($this->text_height/2));
				imagestringup($this->image, 2, $textHorizPos, $textVertPos, $key,  $this->x_axis_text_color);
			}
			$xStart+=$this->bar_width+$this->space_width;
		}
	}
	function drawGradientBar($x1, $y1, $x2, $y2, $colorArr1, $colorArr2)
	{
		if($this->bool_gradient_colors_found==false)
		{
			$numLines=abs($x1-$x2)+1;
			while($numLines>$this->gradient_max)
			{
				//WE HAVE MORE LINES THAN ALLOWABLE COLORS
				//USE HANDICAP TO RECORD THIS
				$numLines/=2;
				$this->gradient_handicap++;
			}
			$color1R=$colorArr1[0];
			$color1G=$colorArr1[1];
			$color1B=$colorArr1[2];
			$color2R=$colorArr2[0];
			$color2G=$colorArr2[1];
			$color2B=$colorArr2[2];
			$rScale=($color1R-$color2R)/$numLines;
			$gScale=($color1G-$color2G)/$numLines;
			$bScale=($color1B-$color2B)/$numLines;
			$this->allocateGradientColors($color1R, $color1G, $color1B, $rScale, $gScale, $bScale, $numLines);
		}
		$numLines=abs($x1-$x2)+1;
		if($this->gradient_handicap>0)
		{
			//IF HANDICAP IS USED, IT WILL ALLOW US TO MOVE THROUGH THE ARRAY MORE SLOWLY, DEPENDING ON THE SET VALUE
			$interval=$this->gradient_handicap;
			for($i=0;$i<$numLines;$i++)
			{
				imageline($this->image, $x1+$i, $y1, $x1+$i, $y2, $this->gradient_color_array[ceil($i/pow(2,$interval))-1]);		
			}
		}
		else
		{
			//NORMAL GRADIENTS WITH COLORS < $this->gradient_max
			for($i=0;$i<$numLines;$i++)
			{
				imageline($this->image, $x1+$i, $y1, $x1+$i, $y2, $this->gradient_color_array[$i]);		
			}
		}
	}
	function generateGrid()
	{
		//DETERMINE HORIZONTAL GRID LINES
		$horizGridArray=array();
		$min=0;
		$horizGridArray[]=$min;
		//USE OUR FUNCTION TO DETERMINE IDEAL Y AXIS SCALE INTERVAL
		$intervalFromZero=$this->determineAxisMarkerScale($this->data_max, $this->data_min);
		//IF WE HAVE POSITIVE VALUES, ADD GRID VALUES TO ARRAY 
		//UNTIL WE REACH THE MAX NEEDED (WE WILL GO 1 OVER)
		$current=$min;
		while($current<($this->data_max))
		{
			$current+=$intervalFromZero;
			$horizGridArray[]=$current;
		}
		//IF WE HAVE NEGATIVE VALUES, ADD GRID VALUES TO ARRAY 
		//UNTIL WE REACH THE MIN NEEDED (WE WILL GO 1 OVER)
		$current=$min;
		while($current>($this->data_min))
		{
			$current-=$intervalFromZero;
			$horizGridArray[]=$current;
		}
		//SORT NEEDED B/C WE WILL USE LAST VALUE LATER (MAX)
		sort($horizGridArray);
		//DETERMINE VERTICAL GRID LINES
		$vertGridArray=array();
		$min=0;
		$vertGrids=$this->data_count+1;
		$interval=$this->bar_width+$this->space_width;
		//ASSEMBLE VERT GRIDLINE ARRAY
		for($i=1;$i<$vertGrids;$i++)
		{
			$vertGridArray[]=$this->y_axis_x1+($interval*$i);
		}
		//LOOP THROUGH EACH HORIZONTAL LINE
		foreach($horizGridArray as $value)
		{
			$yValue=(int)($this->x_axis_y1-($value*$this->unit_scale));	
			if($this->bool_grid)
			{
				imageline($this->image, $this->y_axis_x1, $yValue, $this->x_axis_x2 , $yValue, $this->grid_color);
			}
			//DISPLAY VALUE ON Y AXIS IF DESIRED USING CALC'D GRID VALUES
			if($this->bool_y_axis_values)
			{
				$adjustedYValue=$yValue-($this->text_height/2);
				$adjustedXValue=$this->y_axis_x1-(strlen($value)*$this->text_width)-$this->axis_value_padding;
				imagestring($this->image, 2, $adjustedXValue, $adjustedYValue, $value, $this->y_axis_text_color);
			}
		}
		if(!$this->bool_all_positive)
		{
			//RESET WITH BETTER VALUE BASED ON GRID MIN VALUE CALCULATIONS, NOT DATA MIN
			$this->y_axis_y1=$this->x_axis_y1-($horizGridArray[0]*$this->unit_scale);
		}
		//RESET WITH BETTER VALUE BASED ON GRID VALUE CALCULATIONS, NOT DATA MIN
		$this->y_axis_y2=$yValue;
		//LOOP THROUGH EACH VERTICAL LINE
		if($this->bool_grid)
		{
			foreach($vertGridArray as $value)
			{
				$xValue=$this->y_axis_y1;
				imageline($this->image, $value, $this->y_axis_y2, $value, $xValue , $this->grid_color);
			}
		}
		//DRAW GOAL LINES IF PRESENT (AFTER GRID) - DOESN'T GET EXECUTED IF ARRAY EMPTY
		foreach($this->goal_line_array as $yLocation)
		{
			$yLocation=(int)($this->x_axis_y1-($yLocation*$this->unit_scale));
			imageline($this->image, $this->y_axis_x1, $yLocation, $this->x_axis_x2 , $yLocation, $this->goal_line_color);
		}
	}
	function generateDataPoints()
	{
		foreach($this->data_point_array as $pointArray)
		{
			imagefilledellipse($this->image, $pointArray[0], $pointArray[1], $this->data_point_width, $this->data_point_width, $this->data_point_color);
		}		
	}
	function generateXAxis()
	{
		imageline($this->image, $this->x_axis_x1, $this->x_axis_y1, $this->x_axis_x2, $this->x_axis_y2, $this->x_axis_color);
	}
	function generateYAxis()
	{
		imageline($this->image, $this->y_axis_x1, $this->y_axis_y1, $this->y_axis_x2, $this->y_axis_y2, $this->y_axis_color);
	}
	function generateBackgound()
	{
		imagefilledrectangle($this->image, 0, 0, $this->width, $this->height, $this->background_color);
	}
	function generateTitle()
	{
		//SPACING MAY HAVE CHANGED SINCE EARLIER
		//USE TOP MARGIN OR GRID TOP Y, WHICHEVER LESS
		$highestElement=($this->top_margin<$this->y_axis_y2) ? $this->top_margin : $this->y_axis_y2;
		$textVertPos=($highestElement/2)-($this->title_char_height/2); //CENTERED
		$titleLength=strlen($this->title_text);
		$this->title_x=($this->width/2)-(($titleLength*$this->title_char_width)/2);
		$this->title_y=$textVertPos;
		imagestring($this->image, 2, $this->title_x , $this->title_y , $this->title_text,  $this->title_color);
	}
	function calcTopMargin()
	{
		if($this->bool_title)
		{
			//INCLUDE SPACE FOR TITLE, APPROX MARGIN + 3*TITLE HEIGHT
			$this->top_margin=$this->height*($this->x_axis_default_percent/100)+($this->title_char_height);
		}
		else
		{
			//JUST USE DEFAULT SPACING
			$this->top_margin=$this->height*($this->x_axis_default_percent/100);
		}	
	}
	function calcRightMargin()
	{
		//JUST USE DEFAULT SPACING
		$this->right_margin=$this->width*($this->y_axis_default_percent/100);
	}
	function calcCoords()
	{
		//CALCULATE AXIS POINTS, ALSO USED FOR OTHER CALCULATIONS
		$this->x_axis_x1=$this->y_axis_margin;
		$this->x_axis_y1=$this->height-$this->x_axis_margin;
		$this->x_axis_x2=$this->width-$this->right_margin;
		$this->x_axis_y2=$this->height-$this->x_axis_margin;
		$this->y_axis_x1=$this->y_axis_margin;
		$this->y_axis_y1=$this->height-$this->x_axis_margin;
		$this->y_axis_x2=$this->y_axis_margin;
		$this->y_axis_y2=$this->top_margin;
	}
	function determineAxisMarkerScale($max, $min)
	{
		//FOR CALCLATION, TAKE RANGE OR MAX-0
		$range=(abs($max-$min)>abs($max-0)) ? abs($max-$min) : abs($max-0);
		//MULTIPLY UP TO OVER 100, TO BETTER FIGURE INTERVAL
		$count=0;
		while(abs($range)<100)
		{
			$range*=10;
			$count++;
		}
		//DIVIDE INTO INTERVALS BASED ON HEIGHT / PRESET CONSTANT - AFTER ROUNDING WILL BE APPROX
		$divisor=round($this->height/$this->range_divisor_factor);
		$divided=round($range/$divisor);
		$result=$this->roundUpOneExtraDigit($divided);
		//IF ROUNDED UP W/ EXTRA DIGIT IS MORE THAN 200% OF DIVIDED VALUE,
		//ROUND UP TO NEXT SIG NUMBER WITH SAME NUM OF DIGITS
		if($result/$divided>=2)
		{
			$result=$this->roundUpSameDigits($divided);
		}
		//DIVIDE BACK DOWN, IF NEEDED
		for($i=0;$i<$count;$i++)
		{
			$result/=10;
		}
		return $result;	
	}
	function roundUpSameDigits($num)
	{           
		$len=strlen($num);  
		if(round($num, -1*($len-1))==$num) 
		{
			//WE ALREADY HAVE A SIG NUMBER
			return $num;
		}
		else
		{
			$firstDig=substr($num, 0,1);
			$secondDig=substr($num, 1,1);
			$rest=substr($num, 2);
			$secondDig=5;
			$altered=$firstDig.$secondDig.$rest;
			//AFTER REASSEMBLY, ROUND UP TO NEXT SIG NUMBER, SAME # OF DIGITS
			return round((int)$altered, -1*($len-1));
		}
	}
	function roundUpOneExtraDigit($num)
	{                     
		$len=strlen($num);  
		$firstDig=substr($num, 0,1);
		$rest=substr($num, 1);
		$firstDig=5;
		$altered=$firstDig.$rest;
		//AFTER REASSEMBLY, ROUND UP TO NEXT SIG NUMBER, ONE EXTRA # OF DIGITS
		return round((int)$altered, -1*($len)); 
	}
	function displayErrors()
	{
		if(count($this->error)>0)
		{
			$lineHeight=12;
			$errorColor = imagecolorallocate($this->image, 0, 0, 0);
			$errorBackColor = imagecolorallocate($this->image, 255, 204, 0);
			imagefilledrectangle($this->image, 0, 0, $this->width-1, 2*$lineHeight,  $errorBackColor);
			imagestring($this->image, 3, 2, 0, "!!----- PHPGraphLib Error -----!!",  $errorColor);
			
			foreach($this->error as $key => $errorText)
			{
				
				imagefilledrectangle($this->image, 0, ($key*$lineHeight)+$lineHeight, $this->width-1, ($key*$lineHeight)+2*$lineHeight,  $errorBackColor);	
				imagestring($this->image, 2, 2, ($key*$lineHeight)+$lineHeight, "[". ($key+1) . "] ". $errorText,  $errorColor);	
			}
			$errorOutlineColor = imagecolorallocate($this->image, 255, 0, 0);
			imagerectangle($this->image, 0, 0, $this->width-1,($key*$lineHeight)+2*$lineHeight,  $errorOutlineColor);		
		}
	}
	//---------------"PUBLIC" GRAPH CUSTOMIZING FUNCTIONS-------------------//
	function addData($data)
	{
		$this->data_array=$data;
		//ASSESS DATA
		$min=$this->data_max_allowable;
		$max=$this->data_min_allowable;
		$nonZero=false;
		//GET RID OF BAD DATA, FIND MAX, MIN
		foreach($this->data_array as $key => $item)
		{
			if(!is_numeric($item))
			{
				unset($this->data_array[$key]);
			}
			else
			{
				if($item>0||$item<0){ $nonZero=true; }
				if($item<$min){ $min=$item; }
				if($item>$max){ $max=$item; }
			}
			
		}
		if($nonZero)
		{
			$this->data_min=$min;
			$this->data_max=$max;
			$this->data_count=count($this->data_array);
			if($this->data_count>0)
			{
				$this->bool_data=true;
				if($this->data_min>=0)
				{
					$this->bool_all_positive=true;
				}
				else if($this->data_max<=0)
				{
					$this->bool_all_negative=true;
				}
			}
			else
			{
				$this->error[]="No valid values detected in data array.";
			}
		}
		else
		{
			$this->error[]="Dataset must have at least one non-zero value.";
		}
	}
	function setupXAxis($percent='', $color='')
	{
		if($percent===false)
		{
			$this->bool_x_axis=false;
		}
		else
		{
			$this->bool_x_axis=true;
		}
		$this->bool_x_axis_setup=true;
		if(!empty($color)&&$arr=$this->returnColorArray($color))
		{
			$this->x_axis_color = imagecolorallocate($this->image, $arr[0], $arr[1], $arr[2]);
		}
		if(is_numeric($percent)&&$percent>0)
		{ 
			$percent=$percent/100;
			$this->x_axis_margin=(int)($this->height*$percent);
		}
		else
		{
			$percent=$this->x_axis_default_percent/100;
			$this->x_axis_margin=(int)($this->height*$percent);
		}	
	}
	function setupYAxis($percent='', $color='')
	{
		if($percent===false)
		{
			$this->bool_y_axis=false;
		}
		else
		{
			$this->bool_y_axis=true;
		}
		$this->bool_y_axis_setup=true;
		if(!empty($color)&&$arr=$this->returnColorArray($color))
		{
			$this->y_axis_color = imagecolorallocate($this->image, $arr[0], $arr[1], $arr[2]);
		}
		if(is_numeric($percent)&&$percent>0)
		{ 
			$percent=$percent/100;
			$this->y_axis_margin=(int)($this->width*$percent);
		}
		else
		{
			$percent=$this->y_axis_default_percent/100;
			$this->y_axis_margin=(int)($this->width*$percent);
		}
	}
	function setTitle($title)
	{
		if(!empty($title))
		{
			$this->title_text=$title;
			$this->bool_title=true;
		}
		else{ $this->error[]="String arg for setTitle() not specified properly."; }	
	}
	function setBars($bool)
	{
		if(is_bool($bool)){ $this->bool_bars=$bool;}
		else{ $this->error[]="Boolean arg for setBars() not specified properly."; }
	}
	function setGrid($bool)
	{
		if(is_bool($bool)){ $this->bool_grid=$bool;}
		else{ $this->error[]="Boolean arg for setGrid() not specified properly."; }
	}
	function setXValues($bool)
	{
		if(is_bool($bool)){ $this->bool_x_axis_values=$bool;}
		else{ $this->error[]="Boolean arg for setXValues() not specified properly."; }
	}
	function setYValues($bool)
	{
		if(is_bool($bool)){ $this->bool_y_axis_values=$bool;}
		else{ $this->error[]="Boolean arg for setYValues() not specified properly."; }
	}
	function setBarOutline($bool)
	{
		if(is_bool($bool)){ $this->bool_bar_outline=$bool;}
		else{ $this->error[]="Boolean arg for setBarOutline() not specified properly."; }
	}
	function setDataPoints($bool)
	{
		if(is_bool($bool)){ $this->bool_data_points=$bool;}
		else{ $this->error[]="Boolean arg for setDataPoints() not specified properly."; }
	}
	function setDataPointSize($size)
	{
		if(is_numeric($size)){ $this->data_point_width=$size;}
		else{ $this->error[]="Data point size in setDataPointSize() not specified properly."; }
	}
	function setDataValues($bool)
	{
		if(is_bool($bool)){ $this->bool_data_values=$bool;}
		else{ $this->error[]="Boolean arg for setDataValues() not specified properly."; }
	}
	
	function setLine($bool)
	{
		if(is_bool($bool)){ $this->bool_line=$bool;}
		else{ $this->error[]="Boolean arg for setLine() not specified properly."; }
	}
	function setGoalLine($yValue)
	{
		if(is_numeric($yValue))
		{
			$this->goal_line_array[]=$yValue;
		}
		else
		{
			$this->error[]="Goal line Y axis value not specified properly.";
		}
	}
	//-------------"PRIVATE" COLOR HANDLING FUNCTIONS---------------//
	function allocateColors()
	{
		$this->background_color = imagecolorallocate($this->image, 255, 255, 255);
		$this->grid_color = imagecolorallocate($this->image, 220, 220, 220);
		$this->bar_color = imagecolorallocate($this->image, 200, 200, 200);
		$this->line_color = imagecolorallocate($this->image, 100, 100, 100);
		$this->x_axis_text_color = $this->line_color;
		$this->y_axis_text_color = $this->line_color;
		$this->data_value_color = $this->line_color;
		$this->title_color = imagecolorallocate($this->image, 0, 0, 0);
		$this->outline_color = $this->title_color;
		$this->data_point_color = $this->title_color;
		$this->x_axis_color = $this->title_color;
		$this->y_axis_color = $this->title_color;
		$this->goal_line_color = $this->title_color;
	}
	function returnColorArray($color)
	{
		//CHECK TO SEE IF NUMERIC COLOR PASSED THROUGH IN FORM '128,128,128'
		if(strpos($color,',')!==false)
		{
			return explode(',',$color);
		}
		switch(strtolower($color))
		{
			//NAMED COLORS BASED ON W3C's RECOMMENDED HTML COLORS
			case 'black': return array(0,0,0); break;
			case 'silver': return array(192,192,192); break;
			case 'gray': return array(128,128,128); break;
			case 'white': return array(255,255,255); break;
			case 'maroon': return array(128,0,0); break;
			case 'red': return array(255,0,0); break;
			case 'purple': return array(128,0,128); break;
			case 'fuscia': return array(255,0,255); break;
			case 'green': return array(0,128,0); break;
			case 'lime': return array(0,255,0); break;
			case 'olive': return array(128,128,0); break;
			case 'yellow': return array(255,255,0); break;
			case 'navy': return array(0,0,128); break;	
			case 'blue': return array(0,0,255); break;
			case 'teal': return array(0,128,128); break;
			case 'aqua': return array(0,255,255); break;	
		}
		$this->error[]="Color name \"$color\" not recogized.";
		return false;
	}
	function allocateGradientColors($color1R, $color1G, $color1B, $rScale, $gScale, $bScale, $num)
	{
		//CALUCLATE THE COLORS USED IN OUR GRADIENT AND STORE THEM IN ARRAY
		$this->gradient_color_array=array();
		for($i=0;$i<$num;$i++)
		{
			$this->gradient_color_array[] = imagecolorallocate($this->image, $color1R-($rScale*$i), $color1G-($gScale*$i), $color1B-($bScale*$i));
		}
		$this->bool_gradient_colors_found=true;
	}
	function setGenericColor($inputColor, $var, $errorMsg)
	{
		//CAN BE USED FOR MOST COLOR SETTING OPTIONS
		if(!empty($inputColor)&&$arr=$this->returnColorArray($inputColor))
		{
			eval($var . ' = imagecolorallocate($this->image, $arr[0], $arr[1], $arr[2]);');
			return true;	
		}
		else
		{
			$this->error[]=$errorMsg;
			return false;
		}
	}
	//-------------------"PUBLIC" COLOR FUNCTIONS----------------------//
	function setBackgroundColor($color)
	{
		if($this->setGenericColor($color, '$this->background_color', "Background color not specified properly."))
		{
			$this->bool_background=true;
		}
	}
	function setTitleColor($color)
	{
		$this->setGenericColor($color, '$this->title_color', "Title color not specified properly.");
	}
	function setTextColor($color)
	{
		$this->setGenericColor($color, '$this->x_axis_text_color', "X axis text color not specified properly.");
		$this->setGenericColor($color, '$this->y_axis_text_color', "Y axis Text color not specified properly.");
	}
	function setXAxisTextColor($color)
	{
		$this->setGenericColor($color, '$this->x_axis_text_color', "X axis text color not specified properly.");
	}
	function setYAxisTextColor($color)
	{
		$this->setGenericColor($color, '$this->y_axis_text_color', "Y axis Text color not specified properly.");
	}
	function setBarColor($color)
	{
		$this->setGenericColor($color, '$this->bar_color', "Bar color not specified properly.");
	}
	function setGridColor($color)
	{
		$this->setGenericColor($color, '$this->grid_color', "Grid color not specified properly.");
	}
	function setBarOutlineColor($color)
	{
		$this->setGenericColor($color, '$this->outline_color', "Bar outline color not specified properly.");
	}
	function setDataPointColor($color)
	{
		$this->setGenericColor($color, '$this->data_point_color', "Data point color not specified properly.");
	}
	function setDataValueColor($color)
	{
		$this->setGenericColor($color, '$this->data_value_color', "Data value color not specified properly.");	
	}
	function setLineColor($color)
	{
		$this->setGenericColor($color, '$this->line_color', "Line color not specified properly.");	
	}
	function setGoalLineColor($color)
	{
		$this->setGenericColor($color, '$this->goal_line_color', "Goal line color not specified properly.");
	}
	function setGradient($color1, $color2)
	{
		if(!empty($color1)&&!empty($color2)&&($arr1=$this->returnColorArray($color1))&&($arr2=$this->returnColorArray($color2)))
		{
			$this->bool_gradient=true;
			$this->gradient_color_1=$arr1;
			$this->gradient_color_2=$arr2;
		}
		else
		{
			$this->error[]="Gradient color(s) not specified properly.";
		}
	}
}
?>