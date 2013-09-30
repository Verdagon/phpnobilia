// Scheduling scripts, used for animation.
// Author: Evan Ovadia (verdagon@gmail.com)
//
// Call scheduler.start to start the scheduler with a different framerate.
//
// Use scheduler.addSchedule to start the animation.
//
// Multiple schedules can overlap without problems.
//
// Feel free to email me with questions.



var scheduler = new Object();
scheduler.started = false;
scheduler.desiredInterval = undefined;
scheduler.interval = undefined;
scheduler.lastTickTime = undefined;
scheduler.currentTick = 0;
scheduler.timeline = new Array();


// scheduler.addSchedule
//   Call this to add a series of ticks to the scheduler's timeline. If the
//   scheduler hasn't been started yet, it will start it with the default
//   options.
// parameters:
//   values:
//     An array with information for all the steps of the animation. Each entry
//     in the array should have the key that corresponds to which tick it
//     it should fire on. For example:
//       var values = new Array();
//       values[3] = 40;
//       values[4] = 55;
//       values[5] = 75;
//       values[6] = 100;
//     will call your callback with the argument 40 after third ticks. On the
//     fourth tick it will call your callback with the argument 55, and so on.
//   callback:
//     The function to call on every tick. The value will be passed in as the
//     first argument. The second argument is optional, it's a boolean saying
//     whether or not this tick is the first in your schedule. The third
//     argument is also optional, saying whether or not this tick is the
//     second in your schedule.
//   context:
//     Optional. If the callback was a method in a class, pass the context
//     object here.
//
scheduler.addSchedule = function(values, callback, context) {
	if (!this.started)
		this.start();
	
	var firstTickActionInSchedule = undefined;
	var lastTickActionInSchedule = undefined;
	
	for (var relativeTick in values) {
		relativeTick = Number(relativeTick);
		
		var targetTick = Math.round(relativeTick + this.currentTick);
		
		if (this.timeline[targetTick] == undefined)
			this.timeline[targetTick] = new Array();
		
		var tickAction = new TickAction(values[relativeTick], callback, context);
		this.timeline[targetTick].push(tickAction);
		
		
		if (firstTickActionInSchedule == undefined)
			firstTickActionInSchedule = tickAction;
		lastTickActionInSchedule = tickAction;
	}
	
	firstTickActionInSchedule.firstInSchedule = true;
	lastTickActionInSchedule.lastInSchedule = true;
}


// scheduler.start
//   Call this to start the scheduler with a different framerate than the default.
// parameters:
//   desiredInterval:
//     Animations are done in frames, in this we will refer to a frame as a
//     "tick". The number of milliseconds per tick can be specified via
//     this argument. The actual interval will go up and down depending on
//     the speed of the user's browser, but will always try to get back to the
//     desired interval.
//
scheduler.start = function(desiredInterval) {
	desiredInterval == (desiredInterval == undefined ? 30 : desiredInterval);
	// default interval is 30 ms/tick (about 33 ticks per second, which is
	// roughly the frequency of human eyes) Most browsers won't go this fast
	// but it will automatically adjust
	
	this.desiredInterval = desiredInterval;
	this.interval = desiredInterval;
	this.lastTickTime = new Date().getTime();
	this.tick();
	this.started = true;
}



scheduler.tick = function() {
	var nextTickTime = this.lastTickTime + this.interval;
	var now = new Date().getTime();
	var timeUntilNextTick = nextTickTime - now;
	
	
	if (timeUntilNextTick < 0) {
		this.interval += 10;
		//console.log("Slowing down... new interval: ", this.interval);
	}
	else if (this.interval - timeUntilNextTick < 10 && this.interval > this.desiredInterval) {
		this.interval -= 10;
		//console.log("Speeding up! new interval: ", this.interval);
	}
	
	setTimeout(function() { scheduler.tick(); }, Math.max(0, timeUntilNextTick));
	
	var tickActions = this.timeline[this.currentTick];
	
	if (tickActions != undefined) {
		for (var i = 0; i < tickActions.length; i++)
			tickActions[i].execute();
	}
	
	this.lastTickTime += this.interval;
	this.currentTick++;
}




function TickAction(value, callback, context) {
	this.value = value;
	this.callback = callback;
	this.context = context;
	this.firstInSchedule = false;
	this.lastInSchedule = false;
}

TickAction.prototype.execute = function() {
	if (this.context == undefined)
		this.callback(this.value, this.firstInSchedule, this.lastInSchedule);
	else
		this.callback.call(this.context, this.value, this.firstInSchedule, this.lastInSchedule);
}