<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/chat.php");

$env = DirectEnvironment::get();
$env->setPlayersOnly();

if (!$env->player->inGame()
 || $env->player->getGame()->status != Game::OPEN)
	$env->redirectToCorrectArea();

try {
	$env->player->makeContact()->update();
	
	
	// maintain game table?
	
	if (empty($env->player->gameID))
		throw new vexception("Player is not currently in a game.");
	
	$game = $env->player->getGame();
	
	$gameName = $game->name;
	
	$hostPlayer = $game->getHostPlayer();
	$hostName = $hostPlayer->name;
}
catch (Exception $e) {
	$env->redirectToErrorPage($e);
}

$page = new Page();
$page->includeJS(ROOT . "/common/chat.js");
$page->includeJS("parley.js");
$page->enableChatHTML();
$page->printTo($page->headInsertion());

	?>
	<style type="text/css">
		.Player {
			margin-right: 0.5em;
		}
	</style>
	<?php

$page->printTo($page->leftColumnInsertion());

	if ($env->player->id == $hostPlayer->id) {
		?>
		<p><a class="SideButton" id="leave" href="#">Cancel Game</a></p>
		<p>
			This is the Parley screen, where you can wait for people to join your
			game. Once at least one more person is in the game, you can start the
			game by hitting the Start Game button below. You can cancel the game
			at any time before it starts.
		</p>
		<p><a class="SideButton" id="startGame" href="#">Start Game</a></p>
		<?php
	} else {
		?>
		<p><a class="SideButton" id="leave" href="#">Leave Game</a></p>
		<p>
			This is the Parley screen, where you can wait for and chat with other
			players before the game starts. The game will start when the host hits
			the "Start Game" button on their screen.
		</p>
		<?php
	}

$page->printTo($page->insertion());
	
	?>
	<fieldset>
		<legend>[ Players ]</legend>
		<div id="playerList"></div>
	</fieldset>
	<?php
	
$page->printToEnd();

?>
