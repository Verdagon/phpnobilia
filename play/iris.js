function Iris(view) {
	this.view = view;
}

// Messages

Iris.prototype.showMessage = function(message, centerPos) {
	if (centerPos == undefined) {
	}
	else {
		var div = $("<div></div>").
			css({
				position: "absolute",
				left: "0px",
				top: "0px",
				zIndex: 10006,
				visibility: "hidden",
				padding: "2px",
				backgroundColor: "#000000",
				border: "2px solid #A0A0A0",
				maxWidth: "250px"
			}).
			html(message);
		
		$("#mapArea").append(div);
		
		var startX = view.scaleOut(centerPos.x) - div.offsetWidth / 2;
		var startY = view.scaleOut(centerPos.y) - div.offsetHeight / 2;
		
		div.addstyle("left", startX).
			addstyle("top", startY + 30).
			addstyle("opacity", 0).
			addstyle("visibility", "visible");
		
		
		var values = new Array();
		
		for (var i = 0; i <= 1; i += 1/35.0) {
			var y = startY - 70 * i * i * i * i;
			
			var opacity = 1 - i * i * i * i;
			values.push({"y": y, "opacity": opacity});
		}
		
		var callback = function(value, first, last) {
			div.addstyle("top", value.y);
			div.addstyle("opacity", value.opacity);
			
			if (last)
				$("#mapArea").removeChild(div);
		};
		
		scheduler.addSchedule(values, callback, this);
	}
}

// Attacking

Iris.prototype.animateSword = function(unit, left, callback) {
	var swordDob = new Dob("image");
	
	swordDob.elementID = "swordEffect";
	
	swordDob.x = unit.hex.unitDob.x;
	swordDob.y = unit.hex.unitDob.y;
	swordDob.adjustX = unit.hex.unitDob.adjustX + (left ? 1 : -1) * view.UNIT_WIDTH / 3;
	swordDob.adjustY = unit.hex.unitDob.adjustY;
	swordDob.w = 200;
	swordDob.h = 200;
	swordDob.zIndex = 90000;
	swordDob.contents = "images/sword" + (left ? "Left" : "Right") + ".png";
	swordDob.visible = true;
	
	if (!left)
		swordDob.opacity = 0;
	
	view.tilesLayer.appendChild(swordDob.renderElement());
	
	
	
	var values = new Array();
	
	if (!left) {
		for (var i = 0; i < 7; i++)
			values.push(new Object());
	
		values.push({"opacity": 1});
	}
	
	var startX = unit.hex.unitDob.x;
	var startY = unit.hex.unitDob.y;
	
	for (var i = 0; i < 8; i++) {
		values.push({"x": startX + (left ? -1 : 1) * i * 24, "y": startY + i * 16});
	}
	
	for (var i = 0; i < 24; i++) {
		values.push({"opacity": 1 - i / 24.0});
	}
	
	scheduler.addSchedule(values,
	function(value, first, last) {
		if ("x" in value && "opacity" in value) {
			swordDob.x = value.x;
			swordDob.y = value.y;
			swordDob.opacity = value.opacity;
			swordDob.updateElement();
		}
		else if ("x" in value) {
			swordDob.x = value.x;
			swordDob.y = value.y;
		
			swordDob.forceUpdateElementXY();
		}
		else if ("opacity" in value) {
			swordDob.opacity = value.opacity;
			swordDob.updateElement();
		}
		
		if (last) {
			view.tilesLayer.removeChild(swordDob.getElement());
			
			if (callback != undefined)
				callback.execute();
		}
	},
	this);
}

Iris.prototype.attackUnit = function(unit, targetUnit, callback)  {
	this.animateSword(targetUnit, true);
	this.animateSword(targetUnit, false, callback);
}

// Moving

Iris.prototype.moveUnit = function(unit, steps, oldV, oldD, newV, newD, callback) {
	var pos = new Pos(unit.hex.x + view.hexW / 2, unit.hex.y + view.hexH / 2);
	this.showMessage("this is my message, hear it roar!", pos);
	
	var sourceHex = unit.hex;
	var targetHex = game.map.getTile(newV, newD).hex;
	
	var sourceDob = sourceHex.unitDob;
	var targetDob = targetHex.unitDob;
	
	var unitX = sourceDob.x;
	var unitY = sourceDob.y;
	
	var offsetX = unitX - sourceHex.x;
	var offsetY = unitY - sourceHex.y;
	
	targetDob.contents = sourceDob.contents;
	targetDob.x = sourceDob.x;
	targetDob.y = sourceDob.y;
	targetDob.adjustX = sourceDob.adjustX;
	targetDob.adjustY = sourceDob.adjustY;
	targetDob.w = sourceDob.w;
	targetDob.h = sourceDob.h;
	targetDob.visible = true;
	var oldZIndex = targetDob.zIndex;
	targetDob.zIndex = 10005;
	targetDob.updateElement();
	
	delete sourceDob.contents;
	sourceDob.visible = false;
	sourceDob.updateElement();
	
	unit.v = targetHex.tile.v;
	unit.d = targetHex.tile.d;
	view.connectUnit(targetHex, unit);
	
	view.sinistra.normalizeMode();
	
	var values = this.plotHexPath(steps);
	
	scheduler.addSchedule(values, 
	function(value, first, last) {
		targetDob.x = value.x + offsetX;
		targetDob.y = value.y + offsetY;
		
		if ("zIndex" in value) {
			targetDob.zIndex = value.zIndex;
			targetDob.forceUpdateElementXYZ();
		}
		else {
			targetDob.forceUpdateElementXY();
		}
		
		if (last) {
			targetDob.x = targetHex.x;
			targetDob.y = targetHex.y;
			targetDob.zIndex = oldZIndex;
			targetDob.updateElement();
			
			callback.execute();
		}
	}, this);
}

Iris.prototype.plotHexPath = function(steps) {
	var path = new Array();
	
	for (var i = 0; i < steps.length - 1; i++) {
		var currentTile = steps[i];
		var nextTile = steps[i + 1];
		var direction = currentTile.direction(nextTile);
		
		this.plotAdjacentHexPath(path, new Pos(currentTile.hex.x, currentTile.hex.y), new Pos(nextTile.hex.x, nextTile.hex.y), direction, nextTile.hex.tileDob.zIndex + 6);
		
		currentTile = nextTile;
	}
	
	return path;
}

Iris.prototype.plotAdjacentHexPath = function(path, startPos, endPos, direction, targetZIndex) {
	var leftX = 110;
	var centerX = 180;
	var rightX = 250;
	var topY = 45;
	var centerY = 90;
	var lowY = 135;
	var exaggeration = 10;
	
	var endPos;
	
	switch (direction) {
	case Tile.NORTH:
		var secondPos = startPos.offsetXY(0, topY - centerY);
		this.plotBounce(path, startPos, secondPos, centerY - topY);
		
		var thirdPos = secondPos.offsetXY(0, endPos.y - startPos.y - (topY - lowY));
		this.plotBounce(path, secondPos, thirdPos, Math.abs(endPos.y - startPos.y - (topY - lowY)), targetZIndex);
		
		endPos = thirdPos.offsetXY(0, centerY - lowY);
		this.plotBounce(path, thirdPos, endPos, lowY - centerY);
		break;
	
	case Tile.NORTHEAST:
		var secondPos = startPos.offsetXY(rightX - centerX, -exaggeration);
		this.plotBounce(path, startPos, secondPos, rightX - centerX);
		
		var thirdPos = secondPos.offsetXY(endPos.x - startPos.x + (leftX - rightX), endPos.y - startPos.y + 2 * exaggeration);
		this.plotBounce(path, secondPos, thirdPos, secondPos.distanceTo(thirdPos), targetZIndex);
		
		endPos = thirdPos.offsetXY(centerX - leftX, -exaggeration);
		this.plotBounce(path, thirdPos, endPos, centerX - leftX);
		break;
	
	case Tile.SOUTHEAST:
		var secondPos = startPos.offsetXY(rightX - centerX, exaggeration);
		this.plotBounce(path, startPos, secondPos, rightX - centerX);
		
		var thirdPos = secondPos.offsetXY(endPos.x - startPos.x + (leftX - rightX), endPos.y - startPos.y - 2 * exaggeration);
		this.plotBounce(path, secondPos, thirdPos, secondPos.distanceTo(thirdPos), targetZIndex);
		
		endPos = thirdPos.offsetXY(centerX - leftX, exaggeration);
		this.plotBounce(path, thirdPos, endPos, centerX - leftX);
		break;
	
	case Tile.SOUTHWEST:
		var secondPos = startPos.offsetXY(leftX - centerX, exaggeration);
		this.plotBounce(path, startPos, secondPos, centerX - leftX);
		
		var thirdPos = secondPos.offsetXY(endPos.x - startPos.x + (rightX - leftX), endPos.y - startPos.y - 2 * exaggeration);
		this.plotBounce(path, secondPos, thirdPos, secondPos.distanceTo(thirdPos), targetZIndex);
		
		endPos = thirdPos.offsetXY(centerX - rightX, exaggeration);
		this.plotBounce(path, thirdPos, endPos, rightX - centerX);
		break;
	
	case Tile.NORTHWEST:
		var secondPos = startPos.offsetXY(leftX - centerX, -exaggeration);
		this.plotBounce(path, startPos, secondPos, centerX - leftX);
		
		var thirdPos = secondPos.offsetXY(endPos.x - startPos.x + (rightX - leftX), endPos.y - startPos.y + 2 * exaggeration);
		this.plotBounce(path, secondPos, thirdPos, secondPos.distanceTo(thirdPos), targetZIndex);
		
		endPos = thirdPos.offsetXY(centerX - rightX, -exaggeration);
		this.plotBounce(path, thirdPos, endPos, rightX - centerX);
		break;
		
	case Tile.SOUTH:
		var secondPos = startPos.offsetXY(exaggeration, lowY - centerY);
		this.plotBounce(path, startPos, secondPos, lowY - centerY);
		
		var thirdPos = secondPos.offsetXY(-2 * exaggeration, endPos.y - startPos.y - (lowY - topY));
		this.plotBounce(path, secondPos, thirdPos, Math.abs(endPos.y - startPos.y - (lowY - topY)), targetZIndex);
		
		endPos = thirdPos.offsetXY(exaggeration, centerY - topY);
		this.plotBounce(path, thirdPos, endPos, centerY - topY);
		break;
	}
	
	return endPos;
}

Iris.prototype.plotBounce = function(path, start, end, dist, targetZIndex) {	
	// distance affects the height of the bounce. Just an estimate is fine,
	// it doesnt have to be accurate.
	
	var increment = end.minus(start).scale(1/12);
	
	path.push(start.offset(increment.scale(1)).offsetXY(0, -dist * 0.153));
	path.push(start.offset(increment.scale(2)).offsetXY(0, -dist * 0.278));
	path.push(start.offset(increment.scale(3)).offsetXY(0, -dist * 0.365));
	path.push(start.offset(increment.scale(4)).offsetXY(0, -dist * 0.444));
	path.push(start.offset(increment.scale(5)).offsetXY(0, -dist * 0.486));
	var borderPosition = start.offset(increment.scale(6)).offsetXY(0, -dist * 0.500);
	borderPosition.zIndex = targetZIndex;
	path.push(borderPosition);
	path.push(start.offset(increment.scale(7)).offsetXY(0, -dist * 0.486));
	path.push(start.offset(increment.scale(8)).offsetXY(0, -dist * 0.444));
	path.push(start.offset(increment.scale(9)).offsetXY(0, -dist * 0.365));
	path.push(start.offset(increment.scale(10)).offsetXY(0, -dist * 0.278));
	path.push(start.offset(increment.scale(11)).offsetXY(0, -dist * 0.153));
	path.push(end);
}

// Elevation

Iris.prototype.toggleElevation = function(checked) {
	// having overlays is very taxing on the animations.
	view.sinistra.normalizeMode();
	
	if (view.dextra.showAnimationBox.checked) {
		var currentStartTick = 0;
		
		if (checked) {
			for (var hexKey in view.hexes) {
				var hex = view.hexes[hexKey];
				if (hex.y != hex.anchorY) {
					hex.startMoving(currentStartTick, 6, hex.anchorX, hex.anchorY);
					
					// at 21, its the maximum load on the animation, thats when we slow down.
					// (2/7) * x = 6, so x = 21. a graph would look like a trapezoid, so that's
					// why it doubles (the triangles at the end are half area)
					if (hex.id < 21 || hex.id > view.hexes.length - 21) {
						currentStartTick += 2 / 7;
					}
					else {
						currentStartTick += 4 / 7;
					}
				}
			}
		}
		else {
			var newElevation = game.map.topTile.elevation;
			
			for (var hexKey in view.hexes) {
				var hex = view.hexes[hexKey];
				var shiftY = (newElevation - hex.tile.elevation) * view.hexH / 4;
				
				if (shiftY) {
					hex.startMoving(currentStartTick, 6, hex.anchorX, hex.anchorY - shiftY);
					
					if (hex.id < 21 || hex.id > view.hexes.length - 21) {
						currentStartTick += 2 / 7;
					}
					else {
						currentStartTick += 4 / 7;
					}
				}
			}
		}
	
		view.dextra.hideMenuTemporarily(currentStartTick * MILLISECONDS_PER_TICK + 200);
	}
	else {
		if (checked) {
			for (var hexKey in view.hexes) {
				var hex = view.hexes[hexKey];
				hex.setPosition(hex.anchorX, hex.anchorY).update();
			}
		}
		else {
			var newElevation = game.map.topTile.elevation;
			
			for (var hexKey in view.hexes) {
				var hex = view.hexes[hexKey];
				var shiftY = (newElevation - hex.tile.elevation) * view.hexH / 4;
				hex.setPosition(hex.anchorX, hex.anchorY - shiftY).update();
			}
		}
	}
}
