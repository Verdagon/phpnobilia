<?php

define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/player.php");

$env = DirectEnvironment::get();
Logger::log(3);
$env->setGuestsOnly();
Logger::log(4);

$page = new Page();
$page->includeJS("home.js");
$page->printTo($page->headInsertion());

	?>
	<style type="text/css">
		div#intro {
			overflow: auto;
		}
		
		div.Section div#centurionShot {
			width: 143px;
		}
		
		div.ImageContainer {
			background-color: #C0D0C0;
			border: 1px solid #F4F4F4;
			padding: 5px;
			float: right;
			color: #200000;
		}

	</style>
	<?php

$page->printTo($page->leftColumnInsertion());

	//$section = Section::create("Login")->start();
		?>
		<p>
			Please sign in to your account. Remember, player names and passwords
			are case sensitive, so make sure you don't have caps lock on.
		</p>
		<form id="loginForm" class="SideForm">
			<div>Username: <input id="name" type="text" /></div>
			<div>Password: <input id="password" type="password" /></div>
			<div><input type="submit" value="Sign In" /></div>
		</form>
		<p>
			Don't have an account? Please <a class="Button" href="register.php">
			register</a>.
		</p>
		<?php
	//$section->finish();

$page->printTo($page->rightColumnInsertion());

	?>
	<p>
		On the main page, the right column will have a nice slideshow, sliding up.
		On other pages, this will be the chat room area.
	</p>
	<?php

$page->printTo($page->insertion());

	//$section = Section::create("Nobilia")->start();
		?>
		<div id="intro">
			<div class="ImageContainer" id="centurionShot">
				<img src="images/CenturionShot.jpg" />
			</div>
			Take the strategy of Risk, the graphics of a really old video game, the
			addictiveness of Warcraft, the comfort of Chess-by-mail, and throw in
			the boredom and mathematical prowess of an insanity-driven
			programmer... welcome to Nobilia. Players are thrown into a world full
			of enemies and allies, all vying for control over money, resources,
			and power. To become the strongest nation, one must master his nation's
			economy, military, and government. He must overcome the enemy forces
			and complete his mission: complete and total control of Nobilia.
		</div>
		
		<p class="Warning">
			Nobilia is currently in the alpha stage. That means it's incomplete,
			and in testing. Players may sign up, but don't expect the newer
			features to be stable.
		</p>
		
		<p>
			(Right here will be a nice seven-tile preview, with two guys fighting
			each other.)
		</p>
		<?php
	//$section->finish();

$page->printToEnd();

?>