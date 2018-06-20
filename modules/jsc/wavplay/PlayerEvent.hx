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
class PlayerEvent extends flash.events.Event {
   static public inline var BUFFERING : String = "PLAYER_BUFFERING";
   static public inline var PLAYING : String = "PLAYER_PLAYING";
   static public inline var PAUSED : String = "PLAYER_PAUSED";
   static public inline var STOPPED : String = "PLAYER_STOPPED";
   public var position : Null<Float>;
   public function new(type : String, ?position : Float, ?bubbles : Bool, ?cancelable : Bool) {
	   super(type, bubbles, cancelable);
	   this.position = position;
   }
}
