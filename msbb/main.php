<?php
define('LOG_PATH','/var/log/');

define('DISPLAY_REVERSE',true); // true = displays log entries starting with the most recent
define('DIRECTORY_SEPARATOR','/');

function get_log_files($dir, &$results = array()) {

	$files = scandir($dir);

	foreach($files as $key => $value){
		$path = realpath($dir.DIRECTORY_SEPARATOR.$value);

		if(!is_dir($path)) {
			$files_list[] = $path;
		}
		elseif ($value != "." && $value != "..") {
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
}


/* Files that you want to have access to, inside the LOG_PATH directory */
$files = get_log_files(LOG_PATH);

// Set a Smart default to 1:
ksort($files);
foreach ($files as $dir_name => $file_array) {
	ksort($file_array);
	foreach ($file_array as $key => $val) {
		$default = $key;
		$log_files[$key] = $val;
	}
}


// separate files from dirs:
foreach ($files as $key => $val) {
	foreach ($val as $log_name => $log_array) {
		$log_files[$log_name] = $log_array;
	}
}


$log =(!isset($_GET['p'])) ? $default : urldecode($_GET['p']);
$lines =(!isset($_GET['lines'])) ? '50': $_GET['lines'];

//$file = $log_files[$log]['path'];
$file = $log;
$title = substr($log, (strrpos($log, '/')+1));

function tail($filename, $lines = 50, $buffer = 4096)
{
	// Open the file
	$f = fopen($filename, "rb");

	// Jump to last character
	fseek($f, -1, SEEK_END);

	// Read it and adjust line number if necessary
	// (Otherwise the result would be wrong if file doesn't end with a blank line)
	if(fread($f, 1) != "\n") $lines -= 1;

	// Start reading
	$output = '';
	$chunk = '';

	// While we would like more
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
	// (Because of buffer size we might have read too many)
	while($lines++ < 0)
	{
		// Find first newline and remove all text before that
		$output = substr($output, strpos($output, "\n") + 1);
	}

	// Close file and return
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
// Generate a menu
foreach ($files as $dir => $files_array) {
	echo '<li>'.$dir.'</li>';
	echo '<ul>';
	foreach($files_array as $k=>$f){
		if(!is_file($f['path'])){
			// File does not exist, remove it from the array, so it does not appear in the menu.
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
<p>How many lines to display? <form action="" method="get">
	<input type="hidden" name="p" value="<?php echo $log ?>">
	<select name="lines" onchange="this.form.submit()">
		<option value="10" <?php echo ($lines=='10') ? 'selected':'' ?>>10</option>
		<option value="50" <?php echo ($lines=='50') ? 'selected':'' ?>>50</option>
		<option value="100" <?php echo ($lines=='100') ? 'selected':'' ?>>100</option>
		<option value="500" <?php echo ($lines=='500') ? 'selected':'' ?>>500</option>
		<option value="1000" <?php echo ($lines=='1000') ? 'selected':'' ?>>1000</option>
</select>
</form></p>
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
