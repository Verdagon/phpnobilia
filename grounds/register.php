<?php

define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/player.php");

$env = DirectEnvironment::get();
$env->setGuestsOnly();


$page = new Page();
$page->includeJS("register.js");
$page->printTo($page->headInsertion());

	?>
	<style type="text/css">
		form#registerForm {
			display: block;
			background-color: #F8F8F8;
			border: 2px solid #FFFFFF;
			margin: 1em .5em;
			padding: 5px;
			text-align: right;
			float: right;
		}
	</style>
	<?php

$page->printTo($page->insertion());

	?>
	<form id="registerForm">
		<div>Name: <input id="name" type="text" /></div>
		<div>Password: <input id="password" type="password" /></div>
		<div>Email: <input id="email" type="text" /></div>
		<div><input type="submit" value="Create Account" /></div>
	</form>
	<p>
		Please use the form to the right to make your new account. Remember,
		player names and passwords are case sensitive, so make sure you don't
		have caps lock on. <span class="warning">Your password will be sent in
		the clear. This means it is possible that someone could intercept
		it. Do not use a password that you also use elsewhere.</span>
	</p>
	<?php
	
$page->printToEnd();

?>