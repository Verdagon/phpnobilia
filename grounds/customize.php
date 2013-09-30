<?php

define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/unit.php");
require_once(ROOT . "/data/stats.php");
require_once(ROOT . "/data/chat.php");

$env = DirectEnvironment::get();
$env->setPlayersOnly();
if (!$env->player->inGame() || $env->player->getGame()->status != Game::PREBATTLE)
	$env->redirectToCorrectArea();

Game::dropShadowsAndCleanAbandonedGames();

$playerJSON = json_encode($env->player->makeContact()->update()->sendable());

$page = new Page();
$page->includeJS(ROOT . "/common/chat.js");
$page->includeJS(ROOT . "/data/player.js");
$page->includeJS(ROOT . "/data/unit.js");
$page->includeJS(ROOT . "/data/training.js");
$page->includeJS(ROOT . "/play/controller.js");
$page->includeJS("customize.js");
$page->includeCSS("customize.css");
$page->enableChatHTML();
$page->springLoadRightColumn();
$page->printTo($page->headInsertion());

	?>
	<script type="text/javascript">
		var player = new Player(<?php echo $playerJSON; ?>);
	</script>
	<style type="text/css">
		body {
			overflow-y: scroll;
		}
		
		#container {
			background-color: #000000;
		}
		
		#mainColumn {
			padding: 0;
			color: #FFFFFF;
		}
		
		#unitGrid {
			overflow: auto;
		}
		
		.GoodChangeAlert,
		.BadChangeAlert {
			color: #FFFFFF;
			font-weight: bold;
			border: 2px solid #00C0C0;
			padding: 0.5em;
			z-index: 5;
		}
		
		.BadChangeAlert {
			color: #FFFFFF;
			border-color: #C00000;
		}
	</style>
	<?php
	
$page->printTo($page->leftColumnInsertion());

	?>
	<p><a id="leave" class="SideButton" href="#">Leave</a></p>
	<p>You have five minutes to customize and hit Ready when you're done.</p>
	<p><a id="ready" href="#" class="SideButton">Ready</a></p>
	<p>Available funds: <span id="playerMoney"></span></p>
	<?php
	
$page->printTo($page->insertion());

	?>
	<div id="unitGrid"></div>
	<?php
	
$page->printTo($page->templatesInsertion());

	?>
	<div class="UnitThumbContainer Grid">
		<a class="UnitThumb" href="#">
			<div class="ImageContainer">
				<img class="UnitImage" />
				<img class="UnrecruitedLabel" src="images/Unrecruited.png" />
			</div>
			<div class="Cost HalfShadowed"></div>
			<div class="RightColumn">
				<div class="RightLabel Name"></div>
				<div class="RightLabel Str">Str <span class="Value"></span></div>
				<div class="RightLabel Sta">Sta <span class="Value"></span></div>
				<div class="RightLabel Dex">Dex <span class="Value"></span></div>
				<div class="RightLabel Agl">Agl <span class="Value"></span></div>
				<div class="RightLabel Intel">Intel <span class="Value"></span></div>
				<div class="RightLabel Spir">Spir <span class="Value"></span></div>
			</div>
		</a>
	</div>
	
	<div class="UnitView">
		<div class="RightColumn">
			<div class="RightLabel Name"></div>
			<div class="RightLabel Str">Str <span class="Value"></span></div>
			<div class="RightLabel Sta">Sta <span class="Value"></span></div>
			<div class="RightLabel Dex">Dex <span class="Value"></span></div>
			<div class="RightLabel Agl">Agl <span class="Value"></span></div>
			<div class="RightLabel Intel">Intel <span class="Value"></span></div>
			<div class="RightLabel Spir">Spir <span class="Value"></span></div>
			<a class="RightButton RightLabel TrainLink" href="#">[ Train ]</a>
			<a class="RightButton RightLabel ConfirmLink" href="#">[ Confirm ]</a>
		</div>
		<img class="UnitImage" />
		<div class="AlignmentWheel">
			<div>Alignment:</div>
			<img src="images/AlignmentHex.png" />
			<div class="AlignmentDot"></div>
		</div>
		<div class="ExtraText"></div>
	</div>
	
	<div class="GoodChangeAlert HalfShadowed"></div>
	<div class="BadChangeAlert HalfShadowed"></div>
	
	<div class="Modal HalfShadowed">
		<div class="ModalWindow">
			<a class="RightButton RightLabel CancelLink" href="#">[ Cancel ]</a>
		</div>
	</div>
	
	<a class="RightButton RightLabel ModalConfirmButton" href="#">[ Confirm ]</a>
	
	<div class="Trainer">
		<div class="Buttons"></div>
		<div class="Description">Hover over a training type to the left to see a description.</div>
	</div>
	<?php

$page->printToEnd();

?>
