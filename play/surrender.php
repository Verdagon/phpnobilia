<?php
require("vfoundation.php");
require("../data/db.php");
require("../data/game.php");
require("../data/player.php");

session_start();

$player = Player::table()->fromSession();

$game = $player->getGame();
$game->victoryPlayerID = $player->nextPlayerID;
Game::table()->update($game);

$game->notifyAllPlayers(Notification::gameOver($player->nextPlayerID));

$writer = new DataWriter();
$writer->writeNumber(1);
$player->writeNotificationsStringAndClear($writer);
echo $writer->flush();
?>