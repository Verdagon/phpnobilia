<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/db.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/unit.php");

$env = DirectEnvironment::get();
$env->setPlayersOnly();

Game::dropShadowsAndCleanAbandonedGames();

if (!$env->player->inGame()
 || $env->player->getGame()->status != Game::PLAYING)
	$env->redirectToCorrectArea();

$game = $env->player->getGame();
$gameJSON = json_encode($game->sendable());

$page = new Page();
$page->includeCSS("nobilia.css");
$page->includeJS(ROOT . "/common/vtility.js");
$page->includeJS(ROOT . "/common/animation.js");
$page->includeJS(ROOT . "/data/game.js");
$page->includeJS(ROOT . "/data/unit.js");
$page->includeJS(ROOT . "/data/player.js");
$page->includeJS(ROOT . "/data/notification.js");
$page->includeJS(ROOT . "/data/receiver.js");
$page->includeJS("map.js");
$page->includeJS("coordinator.js");
$page->includeJS("dob.js");
$page->includeJS("view.js");
$page->includeJS("iris.js");
$page->includeJS("dextra.js");
$page->includeJS("sinistra.js");
$page->includeJS("nobilia.js");
$page->enableChatHTML();
$page->springLoadLeftColumn();
$page->springLoadRightColumn();
	
$page->printTo($page->headInsertion());

	?>
	<!--Copyright (C) 2008 Evan Ovadia, all rights reserved.
	This is the client-side code for a given game on Nobilia.
	Users are free to look at this code, but any hacking or
	exploiting weaknesses in the code will result in the user
	being banned from the game.-->
	
	<script type="text/javascript">
		$(function() {
			var game = vtility.polymorph(<?php echo $gameJSON; ?>);
			var coordinator = new Coordinator(game, <?php echo $env->player->id; ?>);
			new View(game, coordinator);
			//coordinator.launch();
		});
	</script>
	<style type="text/css">
		#container,
		#mainColumnContainer,
		#mainColumn,
		#mapArea,
		#tilesLayer,
		#tileTextsLayer,
		#unitsLayer {
			height: 100%;
		}
		
		#container,
		#mainColumnContainer,
		#mainColumn,
		#mapArea {
			position: relative;
		}
		
		#mainColumnContainer {
			padding-top: 0;
		}
		
		#mainColumn {
			padding: 0;
		}
	</style>
	<?php
	
$page->printTo($page->leftColumnInsertion());

	?>
	<div id="gamePanel" class="Section">
		(You are <?php echo $env->player->name; ?>)
		<div id="turnIndicator">Loading...</div>
		<a id="surrenderButton" class="RightButton" href="#">Surrender</a>
		<a id="endTurnButton" class="RightButton" href="#">End Turn</a>
	</div>
	<div id="tilePanel" class="Section" style="display: none;">
		<span id="tilePanelTerrain"></span> 
		ID: <span id="tilePanelID"></span>
		<br />
		@(<span id="tilePanelV"></span>,	<span id="tilePanelD"></span>)
		<br />
		Elev: <span id="tilePanelElevation"></span>
	</div>
	<div id="unitPanel" class="Section" style="display: none;">
		<div id="unitPanelName"></div>
		<table>
			<tr>
				<th>Health:</th>
				<td id="unitPanelHealth"></td><td id="unitPanelMaxHealth"></td>
			</tr>
			<tr>
				<th>Energy:</th>
				<td id="unitPanelEnergy"></td><td id="unitPanelMaxEnergy"></td>
			</tr>
			<tr>
				<th>Magic:</th>
				<td id="unitPanelMagic"></td><td id="unitPanelMaxMagic"></td>
			</tr>
		</table>
		<a id="unitPanelDetails" href="#">[ Details ]</a>
	</div>
	<div id="viewMenu">
		<div id="title">[ View Controls ]</div>
		Scale:
		<select id="scaleSelector">
			<option selected>Auto</option>
			<option>0.1</option>
			<option>0.2</option>
			<option>0.3</option>
			<option>0.4</option>
			<option>0.5</option>
			<option>0.6</option>
			<option>0.7</option>
			<option>0.8</option>
			<option>0.9</option>
			<option>1.0</option>
		</select>
		<br />
		<div id="togglesContainer"></div>
		<a id="viewMenuBack" href="#">Back</a>
	</div>
	<?php
	
$page->printTo($page->insertion());

	?>
	<div id="mapArea">
		<div id="tilesLayer" style="display: block;"></div>
		<div id="tileTextsLayer" style="display: block;"></div>
		<div id="unitsLayer"></div>
	<?php

$page->printTo($page->templatesInsertion());

	?>
	<div class="TileContainer"></div>
	<?php

//$page->printToEnd();

?>