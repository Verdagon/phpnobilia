<?php
define("ROOT", ".");
require_once(ROOT . "/data/errorReport.php");
require_once(ROOT . "/nobiliaPage.php");

$env = DirectEnvironment::get();

try {
	$errorID = $env->intFromGET("errorID");
	
	$error = Activity::table()->recall($errorID);
	$error = $error->summary;
	
	/*if ($env->accessLevel() >= Environment::ADMIN)
		$text = $error->text;*/
}
catch (Exception $e) {
	$error = $e->getMessage();
}

$page = new Page();
$page->printTo($page->headInsertion());
	
	?>
	<style type="text/css">
		#error {
			text-align: center;
			color: red;
		}
		
		#details {
			font-size: 80%;
		}
	</style>
	<?php
	
$page->printTo($page->insertion());
	
	echo build('<p id="error">{$1}</p>', $error);
	
	if (isset($text)) {
		echo build('<p id="details">{$1}</p>', nl2br($text));
	}
	else {
		?>
		<p>
			If you don't know why you got this error, please hit
			<a href="sendErrorReport.php?errorReportID=<?php echo $errorReportID; ?>">
			this link</a> and we'll investigate the bug.
		</p>
		<?php
	}
	
$page->printToEnd();

?>
