
// Dob, Display Object ////////////////////////////////////////////////////////////////////// Dob


function Dob(selection) {
	this.jelly = $(selection);
	
	this.positionChanged = false;
	this.left = undefined;
	this.height = undefined;
	
	this.areaChanged = false;
	this.width = undefined;
	this.height = undefined;
	
	this.adjustmentsChanged = false;
	this.leftAdjustment = Dob.identityAdjustment;
	this.topAdjustment = Dob.identityAdjustment;
	this.widthAdjustment = Dob.identityAdjustment;
	this.heightAdjustment = Dob.identityAdjustment;
	
	this.zIndexChanged = false;
	this.zIndex = "auto";
	
	this.opacityChanged = false;
	this.opacity = 1;
	
	this.visibilityChanged = false;
	this.visible = true;
	
	this.pendingCSSChanges = new Object();
}
Dob.identityAdjustment = function(input) { return input; }
Dob.prototype.setPosition = function(left, top) {
	this.positionChanged = true;
	this.left = left;
	this.top = top;
	return this;
}
Dob.prototype.show = function() {
	vtility.assert(!this.visible);
	this.visible = true;
	this.visibilityChanged = true;
	return this;
}
Dob.prototype.hide = function() {
	vtility.assert(this.visible);
	this.visible = false;
	this.visibilityChanged = true;
	return this;
}
Dob.prototype.setArea = function(width, height) {
	this.areaChanged = true;
	this.width = width;
	this.height = height;
	return this;
}
Dob.prototype.setLeftAdjustment = function(adjustment) {
	this.adjustmentsChanged = true;
	this.leftAdjustment = adjustment;
	return this;
}
Dob.prototype.setPositionAdjustments = function(left, top) {
	this.adjustmentsChanged = true;
	this.leftAdjustment = left;
	this.topAdjustment = top;
	return this;
}
Dob.prototype.setAreaAdjustments = function(width, height) {
	this.adjustmentsChanged = true;
	this.widthAdjustment = width;
	this.heightAdjustment = height;
	return this;
}
Dob.prototype.setZIndex = function(zIndex) {
	this.zIndexChanged = true;
	this.zIndex = zIndex;
	return this;
}
Dob.prototype.setOpacity = function(opacity) {
	this.opacityChanged = true;
	this.opacity = opacity;
	return this;
}
Dob.prototype.update = function() {
	var css = new Object();
	
	if (this.positionChanged || this.adjustmentsChanged) {
		css.left = this.leftAdjustment(this.left);
		css.top = this.topAdjustment(this.top);
	}
	this.positionChanged = false;
	
	if (this.areaChanged || this.adjustmentsChanged) {
		css.width = this.widthAdjustment(this.width);
		css.height = this.heightAdjustment(this.height);
	}
	this.areaChanged = false;
	
	if (this.opacityChanged) {
		css.opacity = this.opacity;
		css.filter = "alpha(opacity=" + this.opacity * 100 + ")";
		this.opacityChanged = false;
	}
	
	if (this.zIndexChanged) {
		css.zIndex = this.zIndex;
		this.zIndexChanged = false;
	}
	
	if (this.visibilityChanged) {
		css.display = (this.visible ? "block" : "none");
		this.visibilityChanged = false;
	}
	
	for (var key in this.pendingCSSChanges)
		css[key] = this.pendingCSSChanges[key];
	this.pendingCSSChanges = new Object();
	
	this.jelly.css(css);
	
	return this;
}
Dob.prototype.goTo = function(otherDob) {
	this.bounds.left = otherDob.bounds.left;
	this.bounds.top = otherDob.bounds.top;
	return this;
}

vtility.subclass(ImageDob, Dob);
function ImageDob() {
	Dob.call(this, "<img />");
	this.pendingCSSChanges.position = "absolute";
}
ImageDob.prototype.setImage = function(path) {
	this.jelly.attr("src", path);
	return this;
}

vtility.subclass(ContentDob, Dob);
function ContentDob() {
	Dob.call(this, "<div></div>");
}
ContentDob.prototype.center = function() {
	this.pendingCSSChanges.textAlign = "center";
	return this;
}
ContentDob.prototype.setContent = function(jelly) {
	this.jelly.empty().append(jelly);
	return this;
}
ContentDob.prototype.addClass = function(newClass) {
	this.jelly.addClass(newClass);
	return this;
}

/*
function Dob(type, contentSensitive) { // x,y,w,h can be left undefined and set later
	this.elementID = 0;

	this.type = type;
	
	this.contentSensitive = (contentSensitive != false); // basically, undefined -> true.
	
	this.adjustX = 0; //how much should be added to x.
	this.adjustY = 0; //how much should be added to y.
	this.adjustW = 0; //how much should be added to w.
	this.adjustH = 0; //how much should be added to h.
	
	//this.cssClass, added dynamically
	//this.id, added dynamically
	//this.opacity, added dynamically
	//this.textAlign, added dynamically
	//this.zIndex, added dynamically
	//this.display, added dynamically. either "block" or "inline".
	//this.visible, added dynamically
}

Dob.prototype.nextID = 1000;

Dob.prototype.getElement = function() {
	if (this.elementID == 0) {
		alert("This dob hasnt been defined yet! (getElement())");
		return;
	}
	
	return document.getElementByID(this.elementID);
}

Dob.prototype.renderElement = function() {
	if (this.wasRendered()) {
		alert("This dob was already defined!");
	}
	
	this.elementID = ("id" in this ? this.id : "dob" + Dob.prototype.nextID++);
	
	var elly = null;

	if (this.type == "code") {
		var elly = document.createElement("div");
		elly.style.width = view.scaleOut(this.w + this.adjustW);
		elly.style.height = view.scaleOut(this.h + this.adjustH);
		
		if ("textAlign" in this) {
			elly.style.textAlign = this.textAlign;
		}
		
		if ("contents" in this) {
			elly.innerHTML = this.contents;
		}
	}
	else if (this.type == "image") {
		var elly = document.createElement("img");
		elly.setAttribute("src", this.contents);
		elly.setAttribute("width", view.scaleOut(this.w + this.adjustW));
		elly.setAttribute("height", view.scaleOut(this.h + this.adjustH));
	}
	
	elly.setAttribute("id", this.elementID);
	elly.style.position = "absolute";
	elly.style.left = view.scaleOut(this.x + this.adjustX);
	elly.style.top = view.scaleOut(this.y + this.adjustY);
	
	if ("cssClass" in this) {
		elly.setAttribute("class", this.cssClass);
	}
	
	if ("opacity" in this) {
		elly.style.opacity = this.opacity;
		elly.style.filter = "alpha(opaciy=" + this.opacity * 100 + ")";
	}
	
	if ("visible" in this && this.visible == false) {
		elly.style.display = "none";
	}
	else if ("display" in this) {
		elly.style.display = this.display;
	}
	
	if ("zIndex" in this) {
		elly.style.zIndex = this.zIndex;
	}
	
	return elly;
}

Dob.prototype.attach = function(elementID) {
	this.elementID = elementID;
	var element = $("#" + elementID);
	var position = element.position();
	this.x = position.left;
	this.y = position.top;
	this.w = element.width();
	this.h = element.height();
	
	if (element.style.opacity != "") {
		this.opacity = element.style.opacity - 0;
	}
	
	if (element.style.zIndex != "") {
		this.zIndex = element.style.zIndex - 0;
	}
	
	if (element.style.textAlign != "") {
		this.textAlign = element.style.textAlign;
	}
	
	switch (element.style.display) {
	case "block":
	case "inline":
		this.display = element.style.display;
		break;
		
	case "none":
		this.visible = false;
	}
	
	if (this.contentSensitive) {
		this.contents = (this.type == "image" ? element.src : element.innerHTML);
	}
}

Dob.prototype.wasRendered = function() {
	return this.elementID > 0;
}

Dob.prototype.forceUpdateElementXY = function() {
	var element = document.getElementByID(this.elementID);
	
	// whenever you change something here, change it below.
	
	// + "px" slows firefox but safari needs it.
	element.style.left = view.scaleOut(this.x + this.adjustX) + "px";
	
	element.style.top = view.scaleOut(this.y + this.adjustY) + "px";
	
	// end whenever you change something here, change it below.
}

Dob.prototype.forceUpdateElementXYZ = function() {
	var element = document.getElementByID(this.elementID);
	
	// whenever you change something here, change it below.
	
	// + "px" slows firefox but safari needs it.
	element.style.left = view.scaleOut(this.x + this.adjustX) + "px";
	
	element.style.top = view.scaleOut(this.y + this.adjustY) + "px";
	
	element.style.zIndex = this.zIndex;
	
	// end whenever you change something here, change it below.
}

Dob.prototype.updateElement = function() {
	if (this.elementID == 0) {
		alert("This dob hasnt been defined yet! (updateElement())");
		return;
	}
	
	var element = $("#" + this.elementID);
	
	// display
	var oldDisplay = element.style.display;
	var newDisplay = oldDisplay;
	
	if ("visible" in this && !this.visible) {
		newDisplay = "none";
	}
	else {
		newDisplay = ("display" in this ? this.display : "");
	}
	
	if (oldDisplay != newDisplay) {
		element.style.display = newDisplay;
	}
	
	if (newDisplay == "none") {
		return;
	}
	
	
	// class
	var oldClass = element.className;
	if ((oldClass != "" || "cssClass" in this) && oldClass != this.cssClass) {
		element.className = this.cssClass;
	}
	
	// opacity
	var oldOpacity = element.style.opacity;
	if ((oldOpacity != "" || "opacity" in this) && oldOpacity != this.opacity) {
		element.style.opacity = this.opacity;
		element.style.filter = "alpha(opacity=" + this.opacity * 100 + ")";
	}
	
	// text align
	var oldTextAlign = element.style.textAlign;
	if ((oldTextAlign != "" || "textAlign" in this) && oldTextAlign != this.textAlign) {
		element.style.textAlign = this.textAlign;
	}
	
	// z index
	var oldZIndex = element.style.zIndex;
	if ((oldZIndex != "" || "zIndex" in this) && oldZIndex != this.zIndex) {
		element.style.zIndex = this.zIndex;
	}
	
	if (this.contentSensitive) {
		// contents. undefined contents means the dob doesnt touch the contents.
		if (this.type == "code") {
			if (this.contents != undefined || element.innerHTML != this.contents) {
				var oldCode = element.innerHTML;
				if (oldCode != this.contents) {
					element.innerHTML = (this.contents == undefined ? "" : this.contents);
				}
			}
		}
		else if (this.type == "image") {
			if (this.contents != undefined) {
				var oldSource = element.src;
				if (oldSource != this.contents) {
					element.src = this.contents;
				}
			}
		}
	}
	
	
	// whenever you change something here, change it above.
	
	// x, y
	var finalX = view.scaleOut(this.x + this.adjustX);
	var finalY = view.scaleOut(this.y + this.adjustY);
	
	// + "px" slows firefox but safari needs it.
	
	if (element.style.left != finalX) {
		element.style.left = finalX + "px";
	}
	
	if (element.style.top != finalY) {
		element.style.top = finalY + "px";
	}
	
	// end whenever you change something here, change it above.
	
	
	
	// w, h
	var finalW = view.scaleOut(this.w + this.adjustW);
	var finalH = view.scaleOut(this.h + this.adjustH);
	
	if (this.type == "image") {
		var oldW = element.width;
		if (oldW != finalW) {
			element.width = finalW;
		}
		
		var oldH = element.height;
		if (oldH != finalH) {
			element.height = finalH;
		}
	}
	else {
		var oldW = element.style.width;
		if ((this.w != undefined || oldW != "") && oldW != finalW) {
			element.style.width = view.scaleOut(this.w + this.adjustW) + "px";
		}
		
		var oldH = element.style.height;
		if ((this.h != undefined || oldH != "") && oldH != finalH) {
			element.style.height = view.scaleOut(this.h + this.adjustH) + "px";
		}
	}
}

Dob.prototype.setUpdate = function(attribute, value) {
	this[attribute] = value;
	
	this.updateElement();
}
*/