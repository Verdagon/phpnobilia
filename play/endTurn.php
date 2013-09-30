<?php
require("../vfoundation.php");
require("../data/db.php");
require("../data/game.php");
require("../data/player.php");

session_start();

$player = Player::table()->fromSession();

$game = $player->getGame();
$game->nextTurn();
Game::table()->update($game);

vDB::affect(vDB::build('update Unit set status={$2} where gameID={$1}', $game->id, 'UNMOVED'));



$game->notifyAllPlayers(Notification::switchTurn($player->getNextPlayer()));

$writer = new DataWriter();
$writer->writeNumber(1);
$player->writeNotificationsStringAndClear($writer);
echo $writer->flush();
?>