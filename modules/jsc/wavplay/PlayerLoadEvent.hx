//
// WAV/AU Flash player with resampler
// 
// Copyright (c) 2009, Anton Fedorov <datacompboy@call2ru.com>
//
/* This code is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License version 2 only, as
 * published by the Free Software Foundation.
 *
 * This code is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
 * version 2 for more details (a copy is included in the LICENSE file that
 * accompanied this code).
 */

// Events, fired by Player to notify about it state
class PlayerLoadEvent extends PlayerEvent {
	static public inline var LOAD : String = "PLAYER_LOAD";
	public var SecondsLoaded : Float;
	public var SecondsTotal : Float;
	public function new(type : String, ?bubbles : Bool, ?cancelable : Bool, SecondsLoaded: Float, SecondsTotal: Float) {
		super(type, bubbles, cancelable);
		this.SecondsLoaded = SecondsLoaded;
		this.SecondsTotal = SecondsTotal;
	}
	public override function toString(): String {
		var res = super.toString();
		return res.substr(0, res.length-1)+" SecondsLoaded="+SecondsLoaded+" SecondsTotal="+SecondsTotal+"]";
	}
}
