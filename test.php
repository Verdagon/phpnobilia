<?php
define("ROOT", ".");
require_once(ROOT . "/data/db.php");
require_once(ROOT . "/data/errorReport.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/item.php");
require_once(ROOT . "/data/notification.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/stats.php");
require_once(ROOT . "/data/unit.php");

Chat::table()->resetTable();
?>