function Player(raw, game) {
	for (var key in raw)
		this[key] = raw[key];
	
	delete this.gameID;
	
	Player.all[this.id] = this;
	
	for (var i = 0; i < this.units.length; i++) {
		this.units[i].player = this;
		this.units[i] = vtility.polymorph(this.units[i]);
	}
}
Player.all = new Array();