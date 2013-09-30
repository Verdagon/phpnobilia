function Stats(raw) {
	for (var key in raw)
		this[key] = raw[key];
}
Stats.classNames = {str: "Str", sta: "Sta", dex: "Dex", agl: "Agl", intel: "Intel", spir: "Spir"};

Stats.prototype.add = function(other) {
	var raw = new Array();
	var stats = ["str", "sta", "dex", "agl", "intel", "spir"];
	for (var i = 0; i < stats.length; i++)
		raw[stats[i]] = this[stats[i]] + other[stats[i]];
	return new Stats(raw);
}

Stats.prototype.subtract = function(other) {
	var raw = new Array();
	var stats = ["str", "sta", "dex", "agl", "intel", "spir"];
	for (var i = 0; i < stats.length; i++)
		raw[stats[i]] = this[stats[i]] - other[stats[i]];
	return new Stats(raw);
}

Stats.prototype.getMinimum = function() {
	return Math.min(Math.min(Math.min(Math.min(Math.min(this.str, this.sta), this.dex), this.agl), this.intel), this.spir);
}

Stats.prototype.getMaximum = function() {
	return Math.max(Math.max(Math.max(Math.max(Math.max(this.str, this.sta), this.dex), this.agl), this.intel), this.spir);
}

Stats.prototype.getAverage = function() {
	return (this.str + this.sta + this.dex + this.agl + this.intel + this.spir) / 5.0;
}

Stats.prototype.getTacticalWorth = function() {
	return this.getAverage() + (this.getMaximum() - this.getMinimum()) / 4.0;
}

function Unit(raw) {
	vtility.assert("player" in raw);
	for (var key in raw)
		this[key] = raw[key];
	
	delete this.playerID;
	
	this.stats = new Stats(this.stats);
	this.potentialStats = new Stats(this.potentialStats);
	
	this.recalculate();
	
	this.hex = undefined;
}
Unit.RECRUITED = "RECRUITED";
Unit.UNRECRUITED = "UNRECRUITED";
Unit.getter = function(field) {
	return function() { return Unit.all[this[field]]; }
}
Unit.prototype.classBoost = function()
 { return new Stats({str: 0, sta: 0, dex: 0, agl: 0, intel: 0, spir: 0}); }
Unit.prototype.imagePath = function()
 { return ROOT + "/images/classes/" + this.className + ".png"; }
Unit.prototype.recalculate = function() {
	this.maxHealthPoints = 48 + 3 * this.stats.str + 1 * this.stats.sta;
	this.healthPoints = Math.round(this.health * this.maxHealthPoints);
	this.maxEnergyPoints = 200 + 2 * this.stats.str + 10 * this.stats.sta;
	this.energyPoints = Math.round(this.energy * this.maxEnergyPoints);
	this.maxMagicPoints = 2 + 3 * this.stats.spir;
	this.magicPoints = Math.round(this.magic * this.maxMagicPoints);
	this.movementDistance =  30 + (this.stats.sta + this.stats.str + this.stats.agl) * 3;
	this.pv = this.stats.str / 2;
	this.dv = this.stats.agl + this.stats.intel / 3;
	this.worth = Math.round(this.stats.getTacticalWorth() * 25);
}
Unit.prototype.getTile = function() {
	return this.player.game.map.getTile(this.v, this.d);
}



vtility.subclass(Archer, Unit);
function Archer(raw) {
	Unit.call(this, raw);
}
Archer.prototype.classBoost = function()
 { return new Stats({str: 1, sta: 0, dex: 0, agl: 1, intel: 2, spir: 0}); }



vtility.subclass(Warrior, Unit);
function Warrior(raw) {
	Unit.call(this, raw);
}
Warrior.prototype.classBoost = function()
 { return new Stats({str: 2, sta: 2, dex: 0, agl: 1, intel: 0, spir: 0}); }



vtility.subclass(Mage, Unit);
function Mage(raw) {
	Unit.call(this, raw);
}
Mage.prototype.classBoost = function()
 { return new Stats({str: 0, sta: 0, dex: 0, agl: 0, intel: 3, spir: 3}); }
