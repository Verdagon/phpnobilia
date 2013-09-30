var vtility = {
	rawClone: function(source) {
		if (typeof source == "object") {
			var result = new Object();
			for (var key in source)
				result[key] = vtility.rawClone(source[key]);
		}
		
		return source;
	},
	
	assert: function(condition) {
		if (!condition) {
			console.error("Assertion failed!");
			console.trace();
		}
	},
	
	polymorph: function(raw) {
		vtility.assert(raw.phpClassName in window);
		vtility.assert(typeof(window[raw.phpClassName]) == "function");
		return new (window[raw.phpClassName])(raw);
	},
	
	// Many thanks to http://www.golimojo.com/etc/js-subclass.html
	subclass: function(constructor, superConstructor) {
		function surrogateConstructor() { }
		surrogateConstructor.prototype = superConstructor.prototype;
		var prototypeObject = new surrogateConstructor();
		prototypeObject.constructor = constructor;
		constructor.prototype = prototypeObject;
		
		constructor.superConstructor = superConstructor;
		if (superConstructor.subclassConstructors === undefined)
			superConstructor.subclassConstructors = new Array();
		for (var i = superConstructor; i != undefined; i = i.superConstructor)
			i.subclassConstructors.push(constructor);
	}
};

jQuery.fn.bounds = function() {
	vtility.assert(this.length == 1);
	var bounds = this.position();
	bounds.width = this.width();
	bounds.height = this.height();
	return bounds;
}

jQuery.fn.offsetBounds = function() {
	vtility.assert(this.length == 1);
	var bounds = this.offset();
	bounds.width = this.width();
	bounds.height = this.height();
	return bounds;
}

jQuery.fn.absoluteCenter = function() {
	var bounds = this.offsetBounds();
	
	return {
		left: bounds.left + bounds.width / 2,
		top: bounds.top + bounds.height / 2
	};
}

jQuery.fn.centerOn = function(otherJelly) {
	vtility.assert(otherJelly instanceof jQuery);
	
	this.css({
		display: "inline",
		position: "absolute"
	}).appendTo($("body"));
	
	var position = otherJelly.absoluteCenter();
	position.left -= this.width() / 2;
	position.top -= this.height() / 2;
	
	return this.css("position", "absolute").css(position);
}

function callback(object, method) {
	if (typeof method == "function") {
		return function() { return method.apply(object, arguments); }
	}
	else {
		return function() { return object[method].apply(object, arguments); }
	}
}

// Intercepts events, and returns false to keep the next events from seeing them.
function interceptor(object, method) {
	if (typeof method == "function") {
		return function() { method.apply(object, arguments); return false; }
	}
	else {
		return function() { object[method].apply(object, arguments); return false; }
	}
}

vtility.subclass(ExtensibleJelly, jQuery);
function ExtensibleJelly(expression, context) {
	var result = $(expression, context);
	this.length = result.length;
	for (var i = 0; i < result.length; i++)
		this[i] = result[i];
	this.jquery = result.jquery;
}
