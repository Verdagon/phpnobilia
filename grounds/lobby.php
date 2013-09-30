<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/player.php");

$env = DirectEnvironment::get();
$env->setPlayersOnly();

if ($env->player->inGame())
	$env->redirectToCorrectArea();

$page = new Page();
$page->includeJS("lobby.js");
$page->includeJS(ROOT . "/data/notification.js");
$page->enableChatHTML();
$page->printTo($page->headInsertion());

	?>
	<style type="text/css">
		div#emptyList {
			padding: .5em;
		}
		
		a.GameListing {
			background-color: #E8E8E8;
			padding: .5em;
			display: block;
		}
		
		a.GameListing:hover {
			background-color: #F8F8F8;
		}
	</style>
	<?php

$page->printTo($page->leftColumnInsertion());

	?>
	<a class="SideButton" id="logout" href="#">Log Out</a>
	<p>
		This is the lobby, where you can view open games. Click on a game to
		view more details, and then click on Join to join it.
	</p>
	<p>
	<form class="SideForm" action="#" id="createGameForm">
		<div>New Game Name: <input type="text" id="gameName" /></div>
		<div><input type="submit" value="Create" /></div>
	</form>
	</p>
	<?php

$page->printTo($page->insertion());

	?>
	<fieldset>
		<legend>[ Open Games ]</legend>
		<div id="gamesList"></div>
		<div id="emptyList">
			There are no open games for you to join. Feel free to create one using
			the form to the left.
		</div>
	</fieldset>
	<?php

$page->printToEnd();
?>