<?php
define('LOG_PATH','/var/log/');
define('DISPLAY_REVERSE',true); // order by
define('DIRECTORY_SEPARATOR','/');

function get_log_files($dir, &$results = array()) {

	$files = scandir($dir);

	foreach($files as $key => $value){
		$path = realpath($dir.DIRECTORY_SEPARATOR.$value);

		if(!is_dir($path)) {
			$files_list[] = $path;
		}
		elseif ($value != "." && $value != ".." && $value = "_") {
			$dirs_list[] = $path;
		}
	}

	foreach ($files_list as $path) {
		preg_match("/^.*\/(\S+)$/", $path, $matches);
		$name = $matches[1];
		$results[$dir][$name] = array('name' => $name, 'path' => $path);
	}
	foreach ($dirs_list as $path) {
		get_log_files($path, $results);
	}

	return $results;
	print $results;
}


// Files to have access to -  LOG_PATH 
$files = get_log_files(LOG_PATH);

ksort($files);
foreach ($files as $dir_name => $file_array) {
	ksort($file_array);
	foreach ($file_array as $key => $val) {
		$default = $key;
		$log_files[$key] = $val;
	}
}


// Separating files...
foreach ($files as $key => $val) {
	foreach ($val as $log_name => $log_array) {
		$log_files[$log_name] = $log_array;
	}
}


$log =(!isset($_GET['p'])) ? $default : urldecode($_GET['p']);
$lines =(!isset($_GET['lines'])) ? '50': $_GET['lines'];
$file = $log;

if (($file) == ''){
$title = 'Statistics';
} else {
$title = substr($log, (strrpos($log, '/')));
}

function tail($filename, $lines = 50, $buffer = 4096)
{
	// Open 
	$f = fopen($filename, "rb");

	// Jump final
	fseek($f, -1, SEEK_END);

	// Read it and adjust line number 
	if(fread($f, 1) != "\n") $lines -= 1;

	// Reading
	$output = '';
	$chunk = '';

	// While 
	while(ftell($f) > 0 && $lines >= 0)
	{
		// Figure out how far back we should jump
		$seek = min(ftell($f), $buffer);

		// Do the jump (backwards, relative to where we are)
		fseek($f, -$seek, SEEK_CUR);

		// Read a chunk and prepend it to our output
		$output = ($chunk = fread($f, $seek)).$output;
		
		// Jump back to where we started reading
		fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

		// Decrease our line counter
		$lines -= substr_count($chunk, "\n");
	}

	// While we have too many lines
	while($lines++ < 0)
	{
		// Find first newline and remove all text before that
		$output = substr($output, strpos($output, "\n") + 1);
	}

	// Close 
	fclose($f);
	return $output;
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">

  <title>Logs viewer</title>
  <meta name="description" content="Gandi SimpleHosting Server Logs gives an easy access to the (sometimes very heavy) server's last logs, typically on a Gandi SimpleHosting server.">
  <meta name="author" content="pixeline">
   <link href = "css/menu.css" type="text/css" rel = "stylesheet">
</head>

<body>
<div id="layout">
    <!-- Menu toggle -->
    <a href="#menu" id="menuLink" class="pure-menu-heading">
        <!-- Hamburger icon -->
        <span></span>
    </a>

    <div id="menu">
        <div class="pure-menu pure-menu-open">
        	<a href="<?php echo $_SERVER['PHP_SELF']?>" class="pure-menu-heading">Server Logs</a>
        	<ul>
        	<?php
// Menu
foreach ($files as $dir => $files_array) {
	echo '<li>'.$dir.'</li>';
	echo '<ul>';
	foreach($files_array as $k=>$f){
		if(!is_file($f['path'])){
			// File not exist
			unset($files_array[$k]);
			continue;
		}
		$active = ($f['path'] == $log) ? 'class="pure-menu-selected"':'';
		echo '<li '.$active.'><a href="?p='.urlencode($f['path']).'&lines='.$lines.'">'.$f['name'].'</a></li>';
	}
	echo '</ul>';
}
?>
			</ul>
        </div>
    </div>

    <div id="main">
        <div class="header">
            <h1><?php echo $title;?></h1>
            <h2>The last <?php echo $lines ?> lines of <?php echo $file ?>.</h2>
         </form>
<p>How many lines to display? <form action="" method="get">
	<input type="hidden" name="p" value="<?php echo $log ?>">
	<select name="lines" onchange="this.form.submit()">
		<option value="10"   <?php echo ($lines=='10') ? 'selected':'' ?>>10</option>
		<option value="50"   <?php echo ($lines=='50') ? 'selected':'' ?>>50</option>
		<option value="100"  <?php echo ($lines=='100') ? 'selected':'' ?>>100</option>
		<option value="500"  <?php echo ($lines=='500') ? 'selected':'' ?>>500</option>
</select>
</form></p>

<form name="dyn" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>?p=<?php echo $log ?>&lines=<?php echo $lines ?>" method="post">
 <input name="buttondyn" type="submit" value="Insert Data into DynamoDB">
</form>
        </div>

        <div class="content">

<code><pre style="font-size:14px;font-family:monospace;color:black;"><ol reversed>

<?php
$output = tail($file, $lines);
$output = explode("\n", $output);
if(DISPLAY_REVERSE){
	// Latest first
	$output = array_reverse($output);
}
$output = implode('<li>',$output);

echo $output;

// If press insert into DynamoDB
if (isset($_POST['buttondyn'])) {
	
	include('config_dynamo.php');
    
    // Get variables
    $filename=$_GET['p'];
    $linesnumber=$_GET['lines'];
    
    // Open file
	$ln = 0;
	$start = 1;
	$end = $linesnumber;  

	$fd = fopen($filename, "rb"); // open the file

	while(true) {
	    $line = fgets($fd); // read next line
	    if(!$line ||  $ln === $end + 1) {
	        break;
 	   }
 	   if($ln >= $start && $ln <= $end) {
	
	$id = rand().'000'.$ln;
	$msg = $filename.' '.$line;

 	$dynamodb->batch($queue)->put_item(array(
    'TableName' => 'LogsTable',
    'Item' => array(
    'id'       => array( AmazonDynamoDB::TYPE_STRING => $id ), // Hash Key
    'msg'      => array( AmazonDynamoDB::TYPE_STRING => $msg ),
    )
 	));
  }
    $ln++;
}

fclose($fd); // close the file
    
	// Execute the batch of requests in parallel
	$responses = $dynamodb->batch($queue)->send();

	// Check for success...
	if ($responses->areOK())
	{
 	    echo "<h3>The data has been successfully added to the table.</h3>" . PHP_EOL;
	}
	    else
	{
	    echo "<h3>Error: Failed to load data.</h3>" . PHP_EOL ;
	    print_r($responses);
	}
}
?>
</ol></pre>
	</code>
</div>
    </div>
</div>
<script>
(function (window, document) {

    var layout   = document.getElementById('layout'),
        menu     = document.getElementById('menu'),
        menuLink = document.getElementById('menuLink');

    function toggleClass(element, className) {
        var classes = element.className.split(/\s+/),
            length = classes.length,
            i = 0;

        for(; i < length; i++) {
          if (classes[i] === className) {
            classes.splice(i, 1);
            break;
          }
        }
        // The className is not found
        if (length === classes.length) {
            classes.push(className);
        }

        element.className = classes.join(' ');
    }

    menuLink.onclick = function (e) {
        var active = 'active';

        e.preventDefault();
        toggleClass(layout, active);
        toggleClass(menu, active);
        toggleClass(menuLink, active);
    };

}(this, this.document));

</script>
	</body>
</html>