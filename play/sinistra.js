function Sinistra(view) {
	this.view = view;
}

Sinistra.prototype.load = function() {
	var leftAdjustment = callback(this, function(left) { return this.view.scaleOut(left - view.hexW * 0.013889); });
	var topAdjustment = callback(this, function(top) { return this.view.scaleOut(top - view.hexH * 0.029412); });
	var widthAdjustment = callback(this, function(width) { return view.scaleOut(width + view.hexW * 0.027779); });
	var heightAdjustment = callback(this, function(height) { return height + view.hexH * 0.027779; });
	
	this.hoverDob = new ImageDob().
		setImage("src", ROOT + "images/Overlays/LightWhite.png").
		setPosition(0, 0).
		setArea(view.hexW, view.hexH).
		setPositionAdjustments(leftAdjustment, topAdjustment).
		setAreaAdjustments(widthAdjustment, heightAdjustment).
		hide().
		update();
	this.hoverDob.jelly.appendTo($("#tilesLayer"));
	
	this.selectionDob = new ImageDob().
		setImage("src", ROOT + "images/Overlays/DarkWhite.png").
		setPosition(0, 0).
		setArea(view.hexW, view.hexH).
		setPositionAdjustments(leftAdjustment, topAdjustment).
		setAreaAdjustments(widthAdjustment, heightAdjustment).
		updatePosition().
		hide().
		update();
	this.selectionDob.jelly.appendTo($("#tilesLayer"));
	
	this.currentHoveredHex = undefined;
	this.currentSelectedHex = undefined;
	
	
	
	
	this.normalMode = new Object();
	this.normalMode.selectHex = this.normalModeSelectHex;
	
	this.preMoveUnitMode = new Object();
	this.preMoveUnitMode.selectHex = this.preMoveUnitSelectHex;
	
	this.moveUnitMode = new Object();
	this.moveUnitMode.unit = undefined;
	this.moveUnitMode.selectHex = this.moveUnitSelectHex;
	
	this.attackUnitMode = new Object();
	this.attackUnitMode.unit = undefined;
	this.attackUnitMode.selectHex = this.attackUnitSelectHex;
	
	this.currentMode = this.normalMode;
}





// Selection / Modes

Sinistra.prototype.refreshSelection = function() {
	if (this.currentSelectedHex)
		this.selectionDob.
			setZIndex(this.currentSelectedHex.tileDob.zIndex + 2).
			goTo(this.currentSelectedHex).
			updatePosition().
			show();

	if (this.currentHoveredHex)
		this.hoverDob.
			setZIndex(this.currentHoveredHex.tileDob.zIndex + 4).
			goTo(this.currentHoveredHex).
			updatePosition().
			show();
}

Sinistra.prototype.startMoveUnit = function() {
	var unit = this.currentSelectedHex.unit;
	
	if (unit == undefined) {
		alert("Pressing 'm' will move a unit. Please select the unit you want to move.");
		return;
	}
	
	if (unit.playerID != controller.thisPlayerID) {
		alert("Pressing 'm' will move one of your own units. Please select a unit that you own.");
		return;
	}
	
	if (unit.status == "MOVED") {
		alert("This unit has already moved.");
		return;
	}
	
	this.normalizeMode();
	
	this.moveUnitMode.unit = unit;
	
	this.overlayUnitMovement(unit, "Dark");
	
	this.currentMode = this.moveUnitMode;
}

Sinistra.prototype.startAttackUnit = function() {
	var unit = this.currentSelectedHex.unit;
	
	if (unit == undefined) {
		alert("Pressing 'a' will tell a unit to attack. Please select the unit you want to attack with.");
		return;
	}
	
	if (unit.playerID != controller.thisPlayerID) {
		alert("Pressing 'a' will tell a unit to attack. Please select a unit that you own.");
		return;
	}
	
	if (unit.status == "MOVED") {
		alert("This unit has already moved.");
		return;
	}
	
	this.normalizeMode();
	
	this.attackUnitMode.unit = unit;
	
	this.overlayUnitAdjacents(unit, "Dark");
	
	this.currentMode = this.attackUnitMode;
}

Sinistra.prototype.normalizeMode = function() {
	this.clearOverlays();
	
	this.currentMode = this.normalMode;
}

Sinistra.prototype.selectHex = function(hex, refreshable) {
	this.currentMode.selectHex.apply(this, [hex, refreshable]);
}

Sinistra.prototype.normalModeSelectHex = function(hex, refreshable) {
	// refreshable means that if we select the tile that was already
	// selected, it just selects it again.
	refreshable = (refreshable == undefined ? false : refreshable);
	
	if (this.currentSelectedHex == hex && !refreshable) {
		this.currentSelectedHex = undefined;
		view.sinistra.selectionDob.visible = false;
		view.sinistra.selectionDob.updateElement();
		return;
	}
	
	this.selectionDob.x = hex.x;
	this.selectionDob.y = hex.y;
	this.selectionDob.zIndex = hex.tileDob.zIndex + 2;
	this.selectionDob.visible = true;
	this.currentSelectedHex = hex;
	this.selectionDob.updateElement();
	
	var unit = game.findUnitByPosition(this.currentSelectedHex.tile.v, this.currentSelectedHex.tile.d);
	if (unit != undefined) {
		this.overlayUnitMovement(unit, "Light");
		this.currentMode = this.preMoveUnitMode;
		
		view.dextra.unitSelected(unit);
	}
	else {
		view.dextra.tileSelected(hex.tile);
	}
}

Sinistra.prototype.preMoveUnitSelectHex = function(hex) {
	this.normalizeMode();
	this.selectHex(hex);
}

Sinistra.prototype.moveUnitSelectHex = function(hex) {
	var unit = this.moveUnitMode.unit;
	var unitAtTarget = game.findUnitByPosition(hex.tile.v, hex.tile.d);
	
	var validMove = (hex != unit.hex);
	validMove = validMove && (unitAtTarget == undefined);
	
	var path = game.map.findPath(unit.hex.tile, hex.tile);
	var movementCost =0;
	for (var node = path; node.next != null; node = node.next)
		movementCost += node.tile.movementCost(node.next.tile);
	validMove = validMove && movementCost <= unit.movementDistance;
	validMove = validMove && movementCost <= unit.energyPoints;
	
	if (validMove) {
		controller.moveUnit(this.moveUnitMode.unit, path);
		this.normalizeMode();
	}
	else {
		this.normalizeMode();
		this.selectHex(hex);
	}
}

Sinistra.prototype.attackUnitSelectHex = function(hex) {
	var unit = this.attackUnitMode.unit;
	
	var tileInRange = (hex != unit.hex);
	tileInRange = (tileInRange && (unit.hex.tile.distance(hex.tile)) <= 1);
	
	if (tileInRange) {
		var targetUnit = game.findUnitByPosition(hex.tile.v, hex.tile.d);
		
		var unitAtTarget = (targetUnit != undefined);
		
		if (unitAtTarget) {
			controller.attackUnit(unit, targetUnit);
		}
	}
	
	this.normalizeMode();
	this.selectHex(this.currentSelectedHex);
}

Sinistra.prototype.overlayUnitMovement = function(unit, intensity) {
	var movementRange = unit.movementDistance - unit.traveledDistance;
	movementRange = Math.min(movementRange, unit.energyPoints);
	
	var reachables = new Array();
	var nextReachableIndex = 0;
	
	reachables.push({"tile": unit.hex.tile, "distance": 0});
	
	while (nextReachableIndex < reachables.length) {
		var reachable = reachables[nextReachableIndex++];
		
		var adjacentTiles = game.map.getAdjacentTiles(reachable.tile);
		
		for (var i = 0; i < adjacentTiles.length; i++) {
			var adjacentTile = adjacentTiles[i];
			var stepDistance = reachable.tile.movementCost(adjacentTile);
			var totalDistance = reachable.distance + stepDistance;
			
			if (totalDistance > movementRange)
				continue;
			
			var alreadyReachable = false;
			for (var j = 0; j < reachables.length; j++) {
				if (adjacentTile == reachables[j].tile) {
					alreadyReachable = true;
					break;
				}
			}
			
			if (alreadyReachable)
				continue;
			
			reachables.push({"tile": adjacentTile, "distance": totalDistance});
		}
	}
	
	
	
	
	this.normalizeMode();
	
	for (var key in reachables) {
		var hex = reachables[key].tile.hex;
		if (hex == unit.hex)
			continue;
		
		hex.overlayDob.visible = true;
		hex.overlayDob.contents = "images/Overlays/" + intensity + game.getPlayer(unit.playerID).color + ".png";
		hex.overlayDob.updateElement();
	}
}

Sinistra.prototype.overlayUnitAdjacents = function(unit, intensity) {
	var adjacentTiles = game.map.getAdjacentTiles(unit.hex.tile);
	
	for (var i = 0; i < adjacentTiles.length; i++) {
		var hex = adjacentTiles[i].hex;
		
		hex.overlayDob.visible = true;
		hex.overlayDob.contents = "images/Overlays/" + intensity + game.getPlayer(unit.playerID).color + ".png";
		hex.overlayDob.updateElement();
	}
}

Sinistra.prototype.clearOverlays = function() {
	if (this.currentMode == this.preMoveUnitMode ||
	    this.currentMode == this.moveUnitMode ||
	    this.currentMode == this.attackUnitMode) {
	    
		for (var hexKey in view.hexes) {
			var hex = view.hexes[hexKey];
			hex.overlayDob.visible = false;
			delete hex.overlayDob.contents;
			hex.overlayDob.updateElement();
		}
	}
}

Sinistra.prototype.mouseMoved = function(x, y) {
	var newHoveredHex = this.view.getHexAtCoords(x, y);
	
	if (newHoveredHex != this.currentHoveredHex) {
		if (this.currentHoveredHex != undefined) {
			this.currentHoveredHex.textDob.opacity = .75;
			this.currentHoveredHex.textDob.updateElement();
		}
		
		if (newHoveredHex == undefined) {
			this.hoverDob.visible = false;
			this.hoverDob.updateElement();
		}
		else {
			this.hoverDob.x = newHoveredHex.x;
			this.hoverDob.y = newHoveredHex.y;
			this.hoverDob.zIndex = newHoveredHex.tileDob.zIndex + 4;
			this.hoverDob.visible = true;
			this.hoverDob.updateElement();
			
			newHoveredHex.textDob.opacity = 1;
			newHoveredHex.textDob.updateElement();
		}
		
		this.currentHoveredHex = newHoveredHex;
	}
}