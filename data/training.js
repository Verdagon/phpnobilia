function Training(raw) {
	for (var key in raw)
		this[key] = raw[key];
	
	this.unit = Unit.all[this.unitID];
	delete this.unitID;
}


vtility.subclass(HunterTraining, Training);
function HunterTraining(raw) { }
HunterTraining.className = "HunterTraining";
HunterTraining.friendlyName = "Hunter Training";
HunterTraining.description = "Hunter training trains soldiers in stamina and intelligence. It teaches patience, and tactics for capturing enemies, whether they be animal or not.";

vtility.subclass(StrengthTraining, Training);
function StrengthTraining(raw) { }
StrengthTraining.className = "StrengthTraining";
StrengthTraining.friendlyName = "Strength Training";
StrengthTraining.description = "Strength training builds soldiers up to be stronger, so they can have a physical advantage over their enemy. It's required to become some of the higher fighting classes.";

vtility.subclass(HistoricalTraining, Training);
function HistoricalTraining(raw) { }
HistoricalTraining.className = "HistoricalTraining";
HistoricalTraining.friendlyName = "Historical Training";
HistoricalTraining.description = "Historical training will teach one about the history and state of the nation, so that he may exercise his mind, and use his knowledge outside of battle.";

vtility.subclass(ArcheryTraining, Training);
function ArcheryTraining(raw) { }
ArcheryTraining.className = "ArcheryTraining";
ArcheryTraining.friendlyName = "Archery Training";
ArcheryTraining.description = "Archery training is required to become an archer. Not everyone can wield a missile weapon adeptly, but this training will teach them the finer techniques of archery, while training dexterity, and exercise their strength and intelligence.";

vtility.subclass(SwordplayTraining, Training);
function SwordplayTraining(raw) { }
SwordplayTraining.className = "SwordplayTraining";
SwordplayTraining.friendlyName = "Swordplay Training";
SwordplayTraining.description = "Swordplay training teaches a soldier to wield a sword effectively as a defensive and offensive weapon. This can be the most valuable training for melee soldiers, because it can deal or block fatal blows to the enemy. This training also trains strength, agility, dexterity, and exercises intelligence.";

vtility.subclass(DefensiveTraining, Training);
function DefensiveTraining(raw) { }
DefensiveTraining.className = "DefensiveTraining";
DefensiveTraining.friendlyName = "Defensive Training";
DefensiveTraining.description = "Defensive training teaches a person to read the enemy's movements and be able to dodge many kinds of attacks, with or without a shield. It trains agility and intelligence.";

vtility.subclass(StrategeryTraining, Training);
function StrategeryTraining(raw) { }
StrategeryTraining.className = "StrategeryTraining";
StrategeryTraining.friendlyName = "Strategery Training";
StrategeryTraining.description = "Strategery training teaches a soldier vital strategies to defeat his enemies, including formations, teamwork, and leadership. It trains intelligence.";

vtility.subclass(ManaTraining, Training);
function ManaTraining(raw) { }
ManaTraining.className = "ManaTraining";
ManaTraining.friendlyName = "Mana Training";
ManaTraining.description = "Mana training attunes a person's mind to their spiritual energy, allowing them to influence events outside normal physical bounds. This ability is vital to performing various types of sorcery. It trains intelligence and spirituality.";
