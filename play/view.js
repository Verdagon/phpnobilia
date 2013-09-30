


// Hex ////////////////////////////////////////////////////////////////////////////////////// Hex
// The Hex is analogous to the tile. Hex is the view type, tile is the controller type.
function Hex(view, tile, minUnitLeft, minUnitTop, index) {
	var unitLeft = tile.d * 3 / 4 - minUnitLeft;
	var unitTop = tile.d / 2 + tile.v - tile.elevation / 4 - minUnitTop;
	
	this.left = this.anchorLeft = unitLeft * View.HEX_WIDTH + 10;
	this.top = this.anchorTop = unitTop * View.HEX_HEIGHT + 50;
	//this.opacity = 1;
	
	this.tile = tile;
	tile.hex = this;
	
	this.unit = undefined;
	
	this.textDob = new ContentDob().
		center().
		setOpacity(.75).
		setPosition(this.anchorLeft, this.anchorTop).
		setArea(View.HEX_WIDTH, "auto").
		setLeftAdjustment(function(left) { return view.scaleOut(left + View.HEX_WIDTH / 16); }).
		setZIndex(index * 10 + 8).
		hide();
		
	this.tileDob = new ImageDob().
		setPosition(this.anchorLeft, this.anchorTop).
		setArea(View.HEX_WIDTH, View.HEX_HEIGHT).
		setImage(Map.TERRAIN_DATA[tile.t].imagePath + tile.elevation + ".png").
		setPositionAdjustments(
			function(left) { return view.scaleOut(left - View.HEX_WIDTH * Map.TERRAIN_DATA[tile.t].iX); },
			function(top) { return view.scaleOut(top - View.HEX_HEIGHT * Map.TERRAIN_DATA[tile.t].iY); }).
		setAreaAdjustments(
			function(width) { return view.scaleOut(width + View.HEX_WIDTH * Map.TERRAIN_DATA[tile.t].oW); },
			function(height) { return view.scaleOut(height + View.HEX_HEIGHT * (Map.TERRAIN_DATA[tile.t].oH + tile.elevation / 4)); }).
		setZIndex(index * 10);
		
	this.unitDob = new ImageDob().
		setPosition(this.anchorLeft, this.anchorTop).
		setArea(View.HEX_WIDTH, View.HEX_HEIGHT).
		setAreaAdjustments(callback(view, "scaleOut"), callback(view, "scaleOut")).
		setZIndex(index * 10 + 6).
		hide();
	
	this.overlayDob = new ImageDob().
		setPosition(this.anchorLeft, this.anchorTop).
		setArea(View.HEX_WIDTH, View.HEX_HEIGHT).
		setPositionAdjustments(
			function(left) { return view.scaleOut(left - View.HEX_WIDTH * 0.013889); },
			function(top) { return view.scaleOut(top - View.HEX_HEIGHT * 0.029412); }).
		setAreaAdjustments(
			function(width) { return view.scaleOut(width + View.HEX_WIDTH * 0.027778); },
			function(height) { return view.scaleOut(height + View.HEX_HEIGHT * 0.062500); }).
		setZIndex(index * 10 + 3).
		hide();
		
	this.dobs = [this.textDob, this.unitDob, this.tileDob, this.overlayDob];
}
Hex.prototype.setPosition = function(left, top) {
	this.left = left;
	this.top = top;
	for (var i = 0; i < this.dobs.length; i++)
		this.dobs[i].setPosition(left, top);
	return this;
}
/*Hex.prototype.setOpacity = function(opacity) {
	this.opacity = opacity;
	for (var i = 0; i < this.dobs.length; i++)
		this.dobs[i].setOpacity(opacity);
	return this;
}*/

Hex.prototype.update = function() {
	var dobs = [this.textDob, this.unitDob, this.tileDob, this.overlayDob];
	for (var i = 0; i < dobs.length; i++)
		dobs[i].update();
	return this;
}

Hex.prototype.startMoving = function(startTick, numTicks, targetLeft, targetTop) {
	if (this.left == targetLeft && this.top == targetTop)
		return;
	
	var positions = [];
	for (var i = 1; i <= numTicks; i++)
		positions[startTick + i] = {"left": this.left + (targetLeft - this.left) * i / numTicks, "top": this.top + (targetTop - this.top) * i / numTicks};
	scheduler.addSchedule(positions, this.move, this);
}




// View //////////////////////////////////////////////////////////////////////////////////// View
function View(game, coordinator) {
	this.game = game;
	this.coordinator = coordinator;
	coordinator.view = this;
	
	this.scaleChoice = "Auto";
	
	this.dextra = new Dextra(this);
	this.sinistra = new Sinistra(this);
	this.iris = new Iris(this);
	
	this.mapArea = $("#mapArea");
	this.tilesLayer = $("#tilesLayer");
	this.tileTextsLayer = $("#tileTextsLayer");
	this.unitsLayer = $("#unitsLayer");
	
	var mapWidth = ((game.map.r * 2 + 1) * 3/4 + 1/4) * View.HEX_WIDTH;
	this.currentScale = this.mapArea.width() / mapWidth;
	
	var minUnitLeft = game.map.leftTile.d * 3 / 4;
	var minUnitTop = game.map.highTile.d / 2 + game.map.highTile.v - game.map.highTile.elevation / 4;
	
	this.hexes = new Array();
	for (var i = 0; i < game.map.tiles.length; i++) {
		var hex = new Hex(this, game.map.tiles[i], minUnitLeft, minUnitTop, i);
		
		this.hexes.push(hex);
	}
	
	for (var i = 0; i < game.players.length; i++) {
		var player = game.players[i];
		for (var j = 0; j < player.units.length; j++) {
			var unit = player.units[j];
			this.connectUnit(unit.getTile().hex, unit);
		}
	}
	
	for (var i = 0; i < this.hexes.length; i++) {
		var hex = this.hexes[i];
		this.tilesLayer.append(hex.tileDob.update().jelly);
		this.tileTextsLayer.append(hex.textDob.update().jelly);
		this.unitsLayer.append(hex.unitDob.update().jelly);
		this.tilesLayer.append(hex.overlayDob.update().jelly);
	}
}
View.HEX_WIDTH = 360;
View.HEX_HEIGHT = 160;
View.UNIT_WIDTH = 225;
View.UNIT_HEIGHT = 300;

View.prototype.connectUnit = function(hex, unit) {
	if (unit.hex != undefined) {
		unit.hex.unit = undefined;
		this.refreshTextDob(unit.hex);
	}
	
	unit.hex = hex;
	hex.unit = unit;

	hex.unitDob.
		setImage(ROOT + "/images/classes/" + unit.phpClassName + ".png").
		setArea(View.UNIT_WIDTH, View.UNIT_HEIGHT).
		setPositionAdjustments(
			callback(this, function(left) { return this.scaleOut(left + View.HEX_WIDTH / 2 - View.UNIT_WIDTH / 2); }),
			callback(this, function(top) { return this.scaleOut(top + View.HEX_HEIGHT * .6 - View.UNIT_HEIGHT); })).
		show();
	
	this.refreshTextDob(hex);
}

View.prototype.refreshTextDob = function(hex) {
	if (hex.unit == undefined) {
		hex.textDob.hide();
	}
	else {
		hex.textDob.
			setContent(hex.unit.healthPoints + " / " + hex.unit.maxHealthPoints).
			addClass("PlayerText" + hex.unit.player.color);
	}
}


View.prototype.mouseMoved = function(x, y) {
	// This function is called by Dextra, so we don't send it back.
	//this.sinistra.mouseMoved(x, y);
}

// Display

View.prototype.scaleOut = function(value) {
	return value * this.currentScale;
}

View.prototype.scaleIn = function(value) {
	return value / this.currentScale;
}

View.prototype.getHexAtCoords = function(x, y) {
	var hitHex = undefined;
	var hitHexHighestZ = 0;
	
	for (var hexKey in this.hexes) {
		var hex = this.hexes[hexKey];
		if (this.hitTestHex(hex, x, y)) {
			if (hex.tileDob.zIndex > hitHexHighestZ || hitHex == undefined) {
				hitHex = hex;
				hitHexHighestZ = hex.tileDob.zIndex;
			}
		}
	}
	
	return hitHex;
}

View.prototype.hitTestHex = function(hex, x, y) {
	var xDistFromCenter = Math.abs(hex.x + this.hexW / 2 - x);
	var yDistFromCenter = Math.abs(hex.y + this.hexH / 2 - y);
	
	return yDistFromCenter < this.hexH / 2 &&
		yDistFromCenter < -2 * (this.hexH)/(this.hexW) * xDistFromCenter + this.hexH;
}

View.prototype.windowResized = function() {
	if (this.scaleChoice == "Auto") {
		var mapW = ((game.map.r * 2 + 1) * 3/4 + 1/4) * 360;
		var windowW = this.mapViewWidth();
		
		this.changeScale(windowW / mapW);
	}
}

View.prototype.changeScale = function(scaleChoice) {
	if (scaleChoice == "Auto") {
		var mapW = ((game.map.r * 2 + 1) * 3/4 + 1/4) * 360;
		var windowW = this.mapViewWidth();
		
		this.currentScale = windowW / mapW;
	}
	else {
		this.currentScale = scaleChoice;
	}
	
	for (var hexKey in this.hexes) {
		var hex = this.hexes[hexKey];
		hex.updateElements();
	}
	
	this.sinistra.hoverDob.updateElement();
	this.sinistra.selectionDob.updateElement();
}



// Element Control

View.prototype.renderElements = function() {
	this.tilesLayer.empty();
	this.tileTextsLayer.empty();
	this.unitsLayer.empty();
	
	for (var hexKey in this.hexes) {
		var hex = this.hexes[hexKey];
		
		this.tilesLayer.append(hex.tileDob);
		this.tileTextsLayer.append(hex.textDob);
		this.unitsLayer.append(hex.unitDob);
		this.tilesLayer.append(hex.overlayDob)
	}
}

View.prototype.updateElements = function() {
	for (var hexKey in this.hexes) {
		var hex = this.hexes[hexKey];
		hex.updateElements();
	}
}

View.prototype.toggleText = function(checked) {
	this.tileTextsLayer.style.display = (checked ? "block" : "none");
}


View.prototype.updateUnit = function(oldState, unit) {
	if (oldState.health != unit.health) {
		var unitHex = unit.hex;
		view.refreshTextDob(unitHex);
	}
}

View.prototype.deleteUnit = function(id) {
	var unit = game.getUnit(id);
	
	var hex = game.map.getTile(unit.v, unit.d).hex;
	hex.unit = undefined;
	delete hex.unitDob.contents;
	hex.unitDob.visible = false;
	
	// refreshes only the text dob.
	view.refreshTextDob(hex);
	
	hex.unitDob.updateElement();
}



View.prototype.endTurn = function() {
	this.sinistra.normalizeMode();
	
	controller.endTurn();
}

View.prototype.turnStarted = function(player) {
	this.dextra.turnStarted(player);
}


// z index ranges:
// 1000-4999: regular hexes (and their text)
// 5000-8999: focused tiles (and their text)
// 10000: mouse layer
// 10002: view controls
// 10003: panel
// 10004: alert
// 10005: moving unit
// 10006: messages

// hex subranges:
// 0: tile dob
// 2: selection dob
// 3: overlay dob
// 4: hover dob
// 8: text dob
// 6: unit dob
