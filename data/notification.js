
function Notification(raw) {
	for (var key in raw)
		this[key] = raw[key];
}

vtility.subclass(SystemNotification, Notification);
function SystemNotification(raw) {
	Notification.call(this, raw);
}
SystemNotification.prototype.display = function() {
	alert("System Notification:\n" + this.text);
}

vtility.subclass(UnitChangeIntNotification, Notification);
function UnitChangeIntNotification(raw) {
	Notification.call(this, raw);
}

vtility.subclass(UnitChangeHPNotification, UnitChangeIntNotification);
function UnitChangeHPNotification(raw) {
	UnitChangeIntNotification.call(this, raw);
}

vtility.subclass(UnitChangeEnergyNotification, UnitChangeIntNotification);
function UnitChangeEnergyNotification(raw) {
	UnitChangeIntNotification.call(this, raw);
}

vtility.subclass(UnitAttackedNotification, Notification);
function UnitAttackedNotification(raw) {
	Notification.call(this, raw);
}

vtility.subclass(UnitDestroyedNotification, UnitAttackedNotification);
function UnitDestroyedNotification(raw) {
	UnitAttackedNotification.call(this, raw);
}

vtility.subclass(UnitMovedNotification, Notification);
function UnitMovedNotification(raw) {
	Notification.call(this, raw);
}

vtility.subclass(SwitchTurnNotification, Notification);
function SwitchTurnNotification(raw) {
	Notification.call(this, raw);
}

vtility.subclass(PlayerWonNotification, Notification);
function PlayerWonNotification(raw) {
	Notification.call(this, raw);
}


/*
function Notification() { }
Notification.read = function(reader) {
	reader.expect("Notification");
	
	switch (reader.peek()) {
	case "AttackUnit": return new AttackUnitNotification(reader);
	case "UpdateUnit": return new UpdateUnitNotification(reader);
	case "DestroyUnit": return new DestroyUnitNotification(reader);
	case "SwitchTurn": return new SwitchTurnNotification(reader);
	case "MoveUnit": return new MoveUnitNotification(reader);
	case "GameOver": return new GameOverNotification(reader);
	}
	
	alert("Error, unknown notification type: " + reader.peek());
}



// ATTACK UNIT ///////////////////////////////////////////////////////////////////////////////////

function AttackUnitNotification(reader) {
	reader.expect("AttackUnit");
	this.newUnit = Unit.read(reader);
	this.newTargetUnit = Unit.read(reader);
}

AttackUnitNotification.prototype = new Notification();

AttackUnitNotification.prototype.execute = function() {
	controller.lockNotifications();
	
	var unit = controller.updateUnit(this.newUnit);
	var targetUnit = controller.updateUnit(this.newTargetUnit);
	
	view.iris.attackUnit(unit, targetUnit, controller.getUnlockCallback());
}



// UPDATE UNIT ///////////////////////////////////////////////////////////////////////////////////

function UpdateUnitNotification(reader) {
	reader.expect("UpdateUnit");
	this.newUnit = Unit.read(reader);
}

UpdateUnitNotification.prototype = new Notification();

UpdateUnitNotification.prototype.execute = function() {
	controller.updateUnit(this.newUnit);
}



// MOVE UNIT /////////////////////////////////////////////////////////////////////////////////////

function MoveUnitNotification(reader) {
	reader.expect("MoveUnit");
	this.unitID = reader.nextNumber();
	
	this.endV = reader.nextNumber();
	this.endD = reader.nextNumber();
	
	var numSteps = reader.nextNumber();
	this.steps = new Array();
	for (var i = 0; i < numSteps; i++) {
		var v = reader.nextNumber();
		var d = reader.nextNumber();
		this.steps.push(game.map.getTile(v, d));
	}
}
MoveUnitNotification.prototype = new Notification();

MoveUnitNotification.prototype.execute = function() {
	controller.lockNotifications();
	
	var unit = game.getUnit(this.unitID);
	
	var oldV = unit.v;
	var oldD = unit.d;
	unit.v = this.endV;
	unit.d = this.endD;
	
	view.iris.moveUnit(unit, this.steps, oldV, oldD, this.endV, this.endD, controller.getUnlockCallback());
}



// DESTROY UNIT //////////////////////////////////////////////////////////////////////////////////

function DestroyUnitNotification(reader) {
	reader.expect("DestroyUnit");
	this.unitID = reader.nextNumber();
}
DestroyUnitNotification.prototype = new Notification();

DestroyUnitNotification.prototype.execute = function() {
	var unitID = this.unitID;
	
	view.deleteUnit(unitID);
	game.deleteUnit(unitID);
}



// GAME OVER //////////////////////////////////////////////////////////////////////////////////

function GameOverNotification(reader) {
	reader.expect("GameOver");
	this.victoryPlayerID = reader.nextNumber();
}
GameOverNotification.prototype = new Notification();

GameOverNotification.prototype.execute = function() {
	game.victoryPlayerID = this.victoryPlayerID;
	
	if (game.victoryPlayerID == controller.thisPlayerID)
		alert("You win!");
	else
		alert("You lose!");
}



// SWITCH TURN ///////////////////////////////////////////////////////////////////////////////////

function SwitchTurnNotification(reader) {
	reader.expect("SwitchTurn");
	this.nextTurnPlayerID = reader.nextNumber();
}
SwitchTurnNotification.prototype = new Notification();

SwitchTurnNotification.prototype.execute = function() {
	var oldTurnPlayerID = game.currentTurnPlayerID;
	game.currentTurnPlayerID = this.nextTurnPlayerID;
	
	if (game.currentTurnPlayerID == controller.thisPlayerID) {
		// this player's turn
		controller.stopWatching();
		controller.startTurn();
		view.startTurn();
	}
	else {
		// enemy's turn.
		controller.startWatch();
		view.startWatch();
	}
}*/