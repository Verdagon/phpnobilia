
function Game(raw) {
	for (var key in raw)
		this[key] = raw[key];
	
	this.map = new Map(this.radius, this.terrain);
	delete this.radius;
	delete this.terrain;
	
	this.playersByID = new Array();
	
	for (var i = 0; i < this.players.length; i++) {
		this.players[i].game = this;
		var player = vtility.polymorph(this.players[i], this);
		this.players[i] = player;
		this.playersByID[player.id] = player;
	}
	
	this.currentTurnPlayer = this.playersByID[this.currentTurnPlayerID];
	delete this.currentTurnPlayerID;
	
	this.hostPlayer = this.playersByID[this.hostPlayerID];
	delete this.hostPlayerID;
	
}

Game.prototype.getPlayer = function(playerID) {
	return this.playersByID[playerID];
}

Game.findUnitByPosition = function(v, d) {
	for (var key in this.units)
		if (this.units[key].v == v && this.units[key].d == d)
			return this.units[key];
	
	return undefined;
}

/*
Game.deleteUnit = function(id) {
	for (var i = 0; i < this.units.length; i++) {
		if (this.units[i].id == id) {
			this.units.splice(i, 1);
			return;
		}
	}
	
	console.error("Unit id " + id + " not found!");
	errormeup();
}
*/
/*game.countUnitsForPlayer = function(playerID) {
	var count = 0;
	
	for (var i = 0; i < game.units.length; i++)
		if (game.units[i].playerID == playerID)
			count++;
	
	return count;
}

game.getPlayer = function(playerID) {
	for (playerKey in game.players) {
		if (game.players[playerKey].id == playerID) {
			return game.players[playerKey];
		}
	}
	
	alert("Can't find player id " + playerID);
}*/




// Tile //////////////////////////////////////////////////////////////////////////////////// Tile

function Tile(id, v, d, t, elevation) {
	this.id = id;
	this.v = v;
	this.d = d;
	this.t = t;
	this.elevation = elevation;
	
	this.hex = undefined;
}

Tile.NORTH = 0;
Tile.NORTHEAST = 1;
Tile.SOUTHEAST = 2;
Tile.SOUTH = 3;
Tile.SOUTHWEST = 4;
Tile.NORTHWEST = 5;

Tile.prototype.distance = function(that) {
	var diff = Math.abs(that.v - this.v) + Math.abs(that.d - this.d)

	if ((that.v - this.v)*(that.d - this.d) < 0) {
		diff -= Math.min(Math.abs(that.v - this.v), Math.abs(that.d - this.d));
	}
	
	return diff;
}

Tile.prototype.direction = function(that) {
	var vDif = that.v - this.v;
	var dDif = that.d - this.d;
	
	if (vDif == -1) {
		if (dDif == 0)
			return Tile.NORTH;
		else if (dDif == 1)
			return Tile.NORTHEAST;
	}
	else if (vDif == 0) {
		if (dDif == -1)
			return Tile.NORTHWEST;
		else if (dDif == 1)
			return Tile.SOUTHEAST;
	}
	else if (vDif == 1) {
		if (dDif == -1)
			return Tile.SOUTHWEST;
		else if (dDif == 0)
			return Tile.SOUTH;
	}
	
	alert("This tile (" + this.v + "," + this.d + ") not adjacent to (" + that.v + "," + that.d + ")!");
	errormeup();
}

// -2 means going down two, etc.
//effort table (for reference): {-5: 30, -4: 20, -3: 10, -2: 0, -1: -10, 0: 0, 1: 10, 2: 20, 3: 30, 4: 50, 5: 70};
//ALSO DOCUMENTED IN GAME.PHP (keep them synched)

// When the two tiles are adjacent, this is accurate.
// This gets less accurate the further the tiles are.
Tile.prototype.movementCost = function(that) {
	var elevationDifference = that.elevation - this.elevation;
	
	if (elevationDifference < 4) {
		return this.distance(that) * 20 + Math.abs(elevationDifference + 1) * 10 - 10;
	}
	else {
		return this.distance(that) * 20 + elevationDifference * 20 - 30;
	}
}

// Map ////////////////////////////////////////////////////////////////////////////////////// Map

function Map(r, rawT) {
	this.r = r;
	this.tiles = new Array();
	this.rectTiles = new Array();
	
	rawT = rawT.split(" ");
	
	var currentID = 0;
	
	var highTile = undefined;
	
	for (var v = -r; v <= r; v++) {
		for (var d = -r; d <= r; d++) {
			if (this.coordinateValid(v, d)) {
				var tileStr = rawT[currentID];
				var colonPos = tileStr.search(":");
				var terrain = tileStr.substr(0, colonPos);
				var attributes = tileStr.substr(colonPos + 1).split(",");
				var elevation = attributes[0] - 0 + 1;
				var tile = new Tile(currentID, v, d, terrain, elevation);
				this.tiles[currentID] = tile;
				this.rectTiles[v * (2 * r + 1) + d] = tile;
				
				if (highTile == undefined) {
					highTile = this.tiles[0];
				}
				else {
					if (v - elevation/4 + d/2 < highTile.v - highTile.elevation/4 + highTile.d/2) {
						highTile = this.tiles[currentID];
					}
				}
				
				currentID++;
			}
		}
	}
	
	this.numTiles = currentID;
	this.leftTile = this.tiles[currentID - 1 - r];
	this.rightTile = this.tiles[r];
	this.topTile = this.tiles[0];
	this.bottomTile = this.tiles[currentID - 1];
	this.highTile = highTile;
}

Map.RADIUS_TILES_TABLE = [1, 7, 19, 37, 61, 91, 127, 169, 217, 271, 331, 397, 469];
Map.TERRAIN_DATA = new Array();
Map.TERRAIN_DATA["GL"] = {"terrainName": "GrassLight", "imagePath": ROOT + "/images/Tiles/GrassLight", "iX": 0.013889, "iY": 0.206250, "oW": 0.050000, "oH": 0.281250};
Map.TERRAIN_DATA["GH"] = {"terrainName": "GrassHeavy", "imagePath": ROOT + "/images/Tiles/GrassHeavy", "iX": 0.025000, "iY": 0.418750, "oW": 0.038889, "oH": 0.493750};
Map.TERRAIN_DATA["DL"] = {"terrainName": "DirtLight", "imagePath": ROOT + "/images/Tiles/DirtLight",  "iX": 0.008333, "iY": 0.025000, "oW": 0.030556, "oH": 0.062500};

Map.prototype.coordinateValid = function(v, d) {
	return (Math.abs(v) <= this.r &&
	        Math.abs(d) <= this.r &&
	        Math.abs(v+d) <= this.r);
}

Map.prototype.getTileID = function(v, d) {
	return this.rectTiles[v * (2 * this.r + 1) + d].id;
}

Map.prototype.getTile = function(v, d) {
	if (!this.coordinateValid(v, d)) {
		return undefined;
	}
	
	return this.rectTiles[v * (2 * this.r + 1) + d];
}

Map.prototype.getAdjacentTiles = function(tile) {
	var tiles = [];
	
	var northTile = this.getTileNorth(tile);
	if (northTile) { tiles.push(northTile); }
	
	var northwestTile = this.getTileNorthwest(tile);
	if (northwestTile) { tiles.push(northwestTile); }
	
	var northeastTile = this.getTileNortheast(tile);
	if (northeastTile) { tiles.push(northeastTile); }
	
	var southTile = this.getTileSouth(tile);
	if (southTile) { tiles.push(southTile); }
	
	var southeastTile = this.getTileSoutheast(tile);
	if (southeastTile) { tiles.push(southeastTile); }
	
	var southwestTile = this.getTileSouthwest(tile);
	if (southwestTile) { tiles.push(southwestTile); }
	
	return tiles;
}

Map.prototype.getTileNorth = function(tile) {
	return this.getTile(tile.v - 1, tile.d);
}

Map.prototype.getTileNortheast = function(tile) {
	return this.getTile(tile.v - 1, tile.d + 1);
}

Map.prototype.getTileSoutheast = function(tile) {
	return this.getTile(tile.v, tile.d + 1);
}

Map.prototype.getTileSouth = function(tile) {
	return this.getTile(tile.v + 1, tile.d);
}

Map.prototype.getTileSouthwest = function(tile) {
	return this.getTile(tile.v + 1, tile.d - 1);
}

Map.prototype.getTileNorthwest = function(tile) {
	return this.getTile(tile.v, tile.d - 1);
}


function PathNode(parent, tile, targetTile) {
	this.parent = parent;
	this.tile = tile;
	this.travelCost = 0;
	this.guessCost = tile.movementCost(targetTile);
	this.next = undefined; // will be defined in createPath
	
	if (parent != undefined) {
		this.travelCost = this.parent.travelCost + this.parent.tile.movementCost(this.tile);
	}
	
	this.costSum = this.travelCost + this.guessCost;
}

PathNode.prototype.recalculate = function(targetTile) {
	// Don't recalculate the guesscost because that will never change.
	this.travelCost = this.parent.travelCost + this.parent.tile.movementCost(this.tile);
	this.costSum = this.travelCost + this.guessCost;
	return this;
}

PathNode.prototype.compareTo = function(that) {
	return this.costSum - that.costSum;
}

Map.prototype.openListContains = function(heap, tile) {
	// because it uses the same logic...
	return this.closedListContains(heap.data, tile);
}

Map.prototype.closedListContains = function(nodes, tile) {
	for (var nodeKey in nodes) {
		var node = nodes[nodeKey];
		
		if (node.tile.id == tile.id) {
			return node;
		}
	}
	
	return undefined;
}

Map.prototype.createPath = function(finalNode) {
	for (var node = finalNode; node.parent != undefined; node = node.parent) {
		node.parent.next = node;
	}
	
	return node;
}

// Returns an array of PathNodes.
Map.prototype.findPath = function(startTile, targetTile) {
	var open = new HeapQueue();
	var closed = [];

	open.push(new PathNode(undefined, startTile, targetTile));

	while (!open.empty()) {
		var currentNode = open.pop();
		closed.push(currentNode);
		
		if (currentNode.guessCost == 0) {
			// found the path!
			return this.createPath(currentNode);
		}
		
		var adjacentTiles = this.getAdjacentTiles(currentNode.tile);
		
		for (var tileKey in adjacentTiles) {
			var adjacentTile = adjacentTiles[tileKey];
			
			if (this.closedListContains(closed, adjacentTile) != undefined) {
				continue;
			}
			
			var potentialNode = new PathNode(currentNode, adjacentTile, targetTile);
			
			var existingNode = this.openListContains(open, adjacentTile);
			
			if (existingNode == undefined) {
				open.push(new PathNode(currentNode, adjacentTile, targetTile));
			}
			else if (potentialNode.travelCost < existingNode.travelCost) {
				existingNode.parent = currentNode;
				existingNode.recalculate();
				open.reposition(existingNode);
			}
		}
	}
	
	// if it gets here then it couldn't find anything.
	return undefined;
}