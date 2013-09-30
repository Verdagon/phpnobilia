function Coordinator(game, currentPlayerID) {
	this.game = game;
	this.currentPlayer = game.getPlayer(currentPlayerID);
	this.view = undefined;
	this.chat = new Chat($("#rightColumn"));
	
	$().ajaxError(function(event, xhr) {
		alert(xhr.responseText + " (Error " + xhr.status + ")");
	});
	
}

Coordinator.prototype.launch = function() {
	vtility.assert(this.view instanceof View);
	
	this.view.turnStarted(this.game.currentTurnPlayer);
	
	var requestor = callback(this, function() {
		$.getJSON("update.php", {lastChatLineID: this. chat.lastLineID}, callback(this, function(response) {
			this.updateHandler(response);
			
			setTimeout(requestor, 3000);
		}));
	});
	
	requestor();
}



// Move

Coordinator.prototype.moveUnit = function(unit, path) {
	var outVars = {"unitID": unitID};
	var numSteps = 0;
	for (var node = path; node != null; node = node.next) {
		outVars["step" + numSteps + "v"] = node.tile.v;
		outVars["step" + numSteps + "d"] = node.tile.d;
		numSteps++;
	}
	outVars["numSteps"] = numSteps;
	
	$.getJSON("nobilia/move.php", outVars, callback(this, "updateHandler"));
}



// Attack

Coordinator.prototype.attackUnit = function(unit, targetUnit) {
	$.getJSON("nobilia/attack.php", {
		unitID: unitID,
		targetUnitID: targetUnitID
	}, callback(this, "updateHandler"));
}

// End turn

Coordinator.prototype.endTurn = function() {
	$.getJSON("nobilia/endTurn.php", undefined, callback(this, "updateHandler"));
}


// Surrender

Coordinator.prototype.surrender = function() {
	$.getJSON("nobilia/surrender.php", undefined, callback(this, "updateHandler"));
}



// Turns, Watching


// Notifications

Coordinator.prototype.updateHandler = function(response) {
	this.chat.receive(response.chat);
	
	for (var i = 0; i < response.notifications.length; i++)
		response.notifications[i].display();
}
/*
controller.controllerHandler = function(responseText, responseArgs) {
	var reader = new DataReader(responseText);
	
	if (!reader.hasNext() || reader.nextNumber() == 0) {
		alert("Error with the server: " + (reader.hasNext() ? reader.nextString() : ""));
		errormeup();
		return;
	}
	
	var numNotifications = reader.nextNumber();
	
	for (var i = 0; i < numNotifications; i++) {
		this.notifications.push(Notification.read(reader));
	}
	
	this.runNotifications();
}*/
/*
controller.runNotifications = function() {
	if (this.notificationLocked)
		return;
	
	var nextNotification = this.notifications[this.nextNotificationIndex];
	if (nextNotification != undefined) {
		nextNotification.execute();
		this.nextNotificationIndex++;
		this.runNotifications();
	}
}

controller.lockNotifications = function() {
	controller.notificationLocked = true;
}

controller.unlockNotifications = function() {
	controller.notificationLocked = false;
	controller.runNotifications();
}

controller.getUnlockCallback = function() {
	return new Callback(this, this.unlockNotifications);
}
controller.updateUnit = function(newUnit) {
	var unit = game.getUnit(newUnit.id);
	var oldState = clone(unit);
	newUnit.copyAttributes(unit);
	unit.recalculate();
	
	view.updateUnit(oldState, unit);
	
	return unit;
}
*/