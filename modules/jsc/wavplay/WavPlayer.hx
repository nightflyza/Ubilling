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

class JsEventHandler {
    public var Id: Int;
    public var Event: String;
    public var Handler: String;
    public var User: Null<String>;
    public inline function new(id:Int, event:String, handler:String, ?user:String) {
        Id = id;
        Event = event;
        Handler = handler;
        User = user;
    }
}

class WavPlayerGui extends flash.events.EventDispatcher {
    var length: Float;
    var ready: Float;
    var position: Float;
    public function drawStopped(): Void { throw("Try to instantiate interface"); }
    public function drawBuffering(): Void { throw("Try to instantiate interface"); }
    public function drawPlaying(): Void { throw("Try to instantiate interface"); }
    public function drawPaused(): Void { throw("Try to instantiate interface"); }
    public function setLength(length: Float) { this.length = length; }
    public function setReady(ready: Float)   { this.ready = ready; }
    public function setPosition(pos: Float) { this.position = pos; }
    function Sizer(x:Float,y:Float,color=0xFF0000,alpha:Float=0) {
        var sprite = new flash.display.Sprite();
        Rect(sprite,x,y,color,alpha);
        return sprite;
    }
    function Rect(sprite,x:Float,y:Float,color,alpha:Float=100) {
        var g:flash.display.Graphics = sprite.graphics;
        g.clear();
        g.lineStyle(1, color, alpha, true);
        g.beginFill(color, alpha);
        g.moveTo(0, 0);
        g.lineTo(0, y-1);
        g.lineTo(x-1, y-1);
        g.lineTo(x-1, 0);
        g.endFill();
    }
}
class WavPlayerGuiEvent extends flash.events.Event {
   static public inline var CLICKED : String = "PLAYERGUI_CLICKED";
   static public inline var DBLCLICKED : String = "PLAYERGUI_DOUBLECLICKED";
   static public inline var SEEKING : String = "PLAYERGUI_SEEKING";
   public var position: Null<Float>;
   public function new(type : String, ?position : Float, ?bubbles : Bool, ?cancelable : Bool) {
       super(type, bubbles, cancelable);
       this.position = position;
   }
}

class WavPlayerGui_None extends WavPlayerGui {
    var sprite: flash.display.Sprite;
    public inline function new(root, myMenu) {
        super();
        sprite = new flash.display.MovieClip();
        sprite.contextMenu = myMenu;
        sprite.useHandCursor = false;
        sprite.buttonMode = false;
        var Sizer = Sizer(1,1);
        sprite.addChild(Sizer);
        root.addChild(sprite);
    }
    public override function drawStopped() { }
    public override function drawBuffering() { }
    public override function drawPlaying() { }
    public override function drawPaused() { }
}
class WavPlayerGui_Mini extends WavPlayerGui {
    var sprite: flash.display.Sprite;
        var color: Int;
    public inline function new(root, myMenu, zoom:Float=1, x:Float=0, y:Float=0, color=0x808080) {
        super();
                this.color = color;
        sprite = new flash.display.MovieClip();
        sprite.contextMenu = myMenu;
        sprite.useHandCursor = true;
        sprite.buttonMode = true;
        sprite.doubleClickEnabled = true;
        sprite.addEventListener(flash.events.MouseEvent.CLICK, handleClicked);
        sprite.addEventListener(flash.events.MouseEvent.DOUBLE_CLICK, handleDblClicked);
        sprite.scaleX = zoom;
        sprite.scaleY = zoom;
        sprite.scaleZ = 1;
        sprite.x = x;
        sprite.y = y;
        var Sizer = Sizer(32,32);
        sprite.addChild(Sizer);
        Sizer.x = 4;
        Sizer.y = 4;
        root.addChild(sprite);
    }
    public override function drawStopped() {
        var g:flash.display.Graphics = sprite.graphics;
        g.clear();
        g.lineStyle(4, color, 1, true, flash.display.LineScaleMode.NORMAL,
                    flash.display.CapsStyle.ROUND, flash.display.JointStyle.ROUND);
        g.beginFill(color);
        g.moveTo(8, 6);
        g.lineTo(30, 20);
        g.lineTo(8, 34);
        g.lineTo(8, 6);
        g.endFill();
    }
    public override function drawBuffering() {
        var g:flash.display.Graphics = sprite.graphics;
        g.clear();
        g.lineStyle(4, color, 1, true, flash.display.LineScaleMode.NORMAL,
                    flash.display.CapsStyle.ROUND, flash.display.JointStyle.ROUND);
        g.drawCircle(20, 20, 10);
    }
    public override function drawPlaying() {
        var g:flash.display.Graphics = sprite.graphics;
        g.clear();
        g.lineStyle(6, color, 1, true, flash.display.LineScaleMode.NORMAL,
                    flash.display.CapsStyle.ROUND, flash.display.JointStyle.ROUND);
        g.beginFill(color);
        g.moveTo(8, 8);
        g.lineTo(32, 8);
        g.lineTo(32, 32);
        g.lineTo(8, 32);
        g.lineTo(8, 8);
        g.endFill();
    }
    public override function drawPaused() {
        var g:flash.display.Graphics = sprite.graphics;
        g.clear();
        g.lineStyle(8, color, 1, true, flash.display.LineScaleMode.NORMAL,
                    flash.display.CapsStyle.ROUND, flash.display.JointStyle.ROUND);
        g.moveTo(12, 8);
        g.lineTo(12, 32);
        g.moveTo(28, 8);
        g.lineTo(28, 32);
    }
    function handleClicked(event:flash.events.Event) {
        dispatchEvent(new WavPlayerGuiEvent(WavPlayerGuiEvent.CLICKED));
    }
    function handleDblClicked(event:flash.events.Event) {
        dispatchEvent(new WavPlayerGuiEvent(WavPlayerGuiEvent.DBLCLICKED));
    }
}
class WavPlayerGui_Full extends WavPlayerGui {
    var GuiMini: WavPlayerGui;
    var sprite: flash.display.Sprite;
    var rectFile: flash.display.Sprite;
    var rectReady: flash.display.Sprite;
    var rectMark: flash.display.Sprite;
    var minTicks: flash.display.Sprite;
    var width: Float;
    var zoom: Float;
    var timer: flash.utils.Timer;
    var lastTime: Float;
    var minor_tick_color: Int;
    var major_tick_color: Int;
    
    public inline function new(root : flash.display.Sprite, myMenu, zoom:Float=1, size:Float=10, 
                               bg_color=0x303030, ready_color=0xA0A0A0, cursor_color=0x7FA03F, button_color=0x808080,
                               minor_tick_color=0x006600, major_tick_color=0x000066) {
        super();
        this.zoom = zoom;
        this.minor_tick_color = minor_tick_color;
        this.major_tick_color = major_tick_color;
        
        sprite = new flash.display.MovieClip();
        sprite.contextMenu = myMenu;
        sprite.scaleX = 1;
        sprite.scaleY = 1;
        sprite.scaleZ = 1;
        sprite.addEventListener(flash.events.MouseEvent.CLICK, handleClicked);
        sprite.useHandCursor = true;
        sprite.buttonMode = true;
        sprite.x = 40*zoom;
        sprite.y = 0;
        sprite.addChild(Sizer(40.0*size*zoom,40.0*zoom));
        GuiMini = new WavPlayerGui_Mini(root, myMenu, zoom, -3, 0, button_color);
        GuiMini.addEventListener(WavPlayerGuiEvent.CLICKED, proxyEvent);
        GuiMini.addEventListener(WavPlayerGuiEvent.DBLCLICKED, proxyEvent);

        rectFile = new flash.display.MovieClip();
        rectFile.scaleX = 1;
        rectFile.scaleY = 1;
        rectFile.scaleZ = 1;
        rectFile.addChild(Sizer(40.0*size*zoom+3,26.0*zoom,bg_color,1));
        sprite.addChild(rectFile);
        rectFile.x = -2;
        rectFile.y = 7*zoom;

        rectReady = new flash.display.MovieClip();
        rectReady.scaleX = 1;
        rectReady.scaleY = 1;
        rectReady.scaleZ = 1;
        rectReady.addChild(Sizer(40.0*size*zoom,10.0*zoom,ready_color,1));
        sprite.addChild(rectReady);
        rectReady.x = 0;
        rectReady.y = 15*zoom;
        rectReady.scaleX = 0.0;

        minTicks = new flash.display.MovieClip();
        minTicks.scaleX = 1;
        minTicks.scaleY = 1;
        minTicks.scaleZ = 1;
        minTicks.x = 0;
        minTicks.y = 0;
        sprite.addChild(minTicks);

        rectMark = new flash.display.MovieClip();
        rectMark.scaleX = 1;
        rectMark.scaleY = 1;
        rectMark.scaleZ = 1;
        rectMark.addChild(Sizer(5.0*zoom,40.0*zoom,cursor_color,1));
        sprite.addChild(rectMark);
        width = 40.0*size*zoom;
        rectMark.x = width*0.0-3;
        rectMark.y = 0;
        rectMark.scaleX = 0.7;
        
        root.addChild(sprite);
        timer = new flash.utils.Timer(100);
        timer.addEventListener( flash.events.TimerEvent.TIMER, delay );
        timer.stop();
    }
    public override function setLength(length: Float) {
        var oldlen = this.length;
        super.setLength(length);
        if (oldlen != length && length > 0) {
            var g:flash.display.Graphics = minTicks.graphics;
            g.clear();
            g.lineStyle(1, this.minor_tick_color, 1, true, flash.display.LineScaleMode.NONE,
                        flash.display.CapsStyle.ROUND, flash.display.JointStyle.ROUND);
            var i: Int = 0;
            while( (i+=10) < length ) if (i%60!=0) {
                    var x = width*(i/length)-3;
                g.moveTo(x, 0);
                g.lineTo(x, 10);
            }
            g.lineStyle(3, this.major_tick_color, 1, true, flash.display.LineScaleMode.NORMAL,
                        flash.display.CapsStyle.ROUND, flash.display.JointStyle.ROUND);
            i = 0;
            while( (i+=60) < length ) {
                var x = width*(i/length)-3;
                g.moveTo(x, 0);
                g.lineTo(x, 10);
            }
        }
    }
    public override function setReady(ready: Float) {
        super.setReady(ready);
        if (length > 0) rectReady.scaleX = ready / length;
    }
    public override function setPosition(pos: Float) { 
        super.setPosition(pos);
        if (length > 0) rectMark.x = width*(position/length)-3;
    }
    public override function drawStopped() {
        timer.stop();
        GuiMini.drawStopped();
    }
    public override function drawBuffering() {
        timer.stop();
        GuiMini.drawBuffering();
    }
    public override function drawPlaying() {
        lastTime = haxe.Timer.stamp();
        timer.reset();
        timer.start();
        GuiMini.drawPlaying();
    }
    public override function drawPaused() {
        timer.stop();
        GuiMini.drawPaused();
    }
    function delay(evt : flash.events.TimerEvent) {
        var ts = haxe.Timer.stamp();
        setPosition(position + (ts-lastTime));
        lastTime = ts;
    }
    function proxyEvent(event:flash.events.Event) {
        dispatchEvent(event);
    }
    function handleClicked(event:flash.events.MouseEvent) {
        var pos: Float = Math.max(0.0, Math.min(1.0, (event.stageX-sprite.x)/width));
        trace("Clicked to "+(event.stageX-sprite.x)+" from "+width+" pos="+pos);
        dispatchEvent(new WavPlayerGuiEvent(WavPlayerGuiEvent.SEEKING, pos*length));
    }
}

// Main user interface: play / stop buttons & ExternalInterface
class WavPlayer {
    static var Version = "1.9.0";
    static var player : IPlayer;
	static var wavplayer : Player;
	static var mp3player : Mp3Player;
    static var state : String = PlayerEvent.STOPPED;
    static var handlers : List<JsEventHandler>;
    static var handlerId : Int;
    static var lastNotifyProgress : Float;
    static var lastNotifyLoad : Float;
    static var iface : WavPlayerGui;
    static function main() {

        trace("WavPlayer "+Version+" - startup");
        var myMenu = new flash.ui.ContextMenu();
        var ciVer = new flash.ui.ContextMenuItem("WavPlayer "+Version);
        var ciCop = new flash.ui.ContextMenuItem("Licensed under GPL");
        myMenu.customItems.push(ciVer);
        myMenu.customItems.push(ciCop);

        var fvs : Dynamic<String> = flash.Lib.current.loaderInfo.parameters;
        handlers = new List<JsEventHandler>();
        handlerId = 0;

        lastNotifyProgress = 0;
        lastNotifyLoad = 0;

        var volume: Float = 1.0;
        var pan: Float = 0.0;
        if (fvs.volume != null) volume = Std.parseFloat(fvs.volume);
        if (fvs.pan != null) pan = Std.parseFloat(fvs.pan);

        var zoom:Float = Std.parseInt(fvs.h); zoom = (zoom>0?zoom:40.0) / 40.0;
        var bg_color:     Int = 0x303030; 
        var ready_color:  Int = 0xA0A0A0;
        var cursor_color: Int = 0x7FA03F;
        var button_color: Int = 0x808080;
        var minor_tick_color: Int = 0x006600;
        var major_tick_color: Int = 0x000066;
        
        if (fvs.bg_color != null) bg_color = Std.parseInt(fvs.bg_color);
        if (fvs.ready_color != null) ready_color = Std.parseInt(fvs.ready_color);
        if (fvs.cursor_color != null) cursor_color = Std.parseInt(fvs.cursor_color);
        if (fvs.button_color != null) button_color = Std.parseInt(fvs.button_color);

        if (fvs.minor_tick_color != null) minor_tick_color = Std.parseInt(fvs.minor_tick_color);
        if (fvs.major_tick_color != null) major_tick_color = Std.parseInt(fvs.major_tick_color);
        
        if (fvs.gui == "full") {
            var width:Float = Std.parseInt(fvs.w); width = (width>0?width:40.0) / zoom / 40.0;
            iface = new WavPlayerGui_Full(flash.Lib.current, myMenu, zoom, width-1, bg_color, ready_color,
                                          cursor_color, button_color,
                                          minor_tick_color, major_tick_color);
        } else if(fvs.gui == "none") {
            iface = new WavPlayerGui_None(flash.Lib.current, myMenu);
        } else {
            iface = new WavPlayerGui_Mini(flash.Lib.current, myMenu, zoom, 0, 0, button_color);
        }
        iface.addEventListener(WavPlayerGuiEvent.CLICKED, handleClicked);
        iface.addEventListener(WavPlayerGuiEvent.DBLCLICKED, handleDblClicked);
        iface.addEventListener(WavPlayerGuiEvent.SEEKING, handleSeeking);
        trace("WavPlayer - gui started " + iface);

        iface.drawStopped();
		
		initPlayerForUrl(fvs.sound);		
        player.volume = volume;
        player.pan = pan;
		
        if( !flash.external.ExternalInterface.available )
            throw "External Interface not available";
        try flash.external.ExternalInterface.addCallback("getVersion",doGetVer) catch( e : Dynamic ) {};

        try flash.external.ExternalInterface.addCallback("doPlay",doPlay) catch( e : Dynamic ) {};
        try flash.external.ExternalInterface.addCallback("play",doPlay) catch( e : Dynamic ) {};

        try flash.external.ExternalInterface.addCallback("doStop",doStop) catch( e : Dynamic ) {};
        try flash.external.ExternalInterface.addCallback("stop",doStop) catch( e : Dynamic ) {};

        try flash.external.ExternalInterface.addCallback("doPause",doPause) catch( e : Dynamic ) {};
        try flash.external.ExternalInterface.addCallback("pause",doPause) catch( e : Dynamic ) {};

        try flash.external.ExternalInterface.addCallback("doResume",doResume) catch( e : Dynamic ) {};
        try flash.external.ExternalInterface.addCallback("resume",doResume) catch( e : Dynamic ) {};

        try flash.external.ExternalInterface.addCallback("doSeek",doSeek) catch( e : Dynamic ) {};
        try flash.external.ExternalInterface.addCallback("seek",doSeek) catch( e : Dynamic ) {};

        try flash.external.ExternalInterface.addCallback("volume",doVolume) catch ( e : Dynamic ) {};
        try flash.external.ExternalInterface.addCallback("setVolume",doVolume) catch ( e : Dynamic ) {};
        try flash.external.ExternalInterface.addCallback("getVolume",doVolume) catch ( e : Dynamic ) {};

        try flash.external.ExternalInterface.addCallback("pan",doPan) catch ( e : Dynamic ) {};
        try flash.external.ExternalInterface.addCallback("setPan",doPan) catch ( e : Dynamic ) {};
        try flash.external.ExternalInterface.addCallback("getPan",doPan) catch ( e : Dynamic ) {};

        try flash.external.ExternalInterface.addCallback("attachHandler",doAttach) catch ( e : Dynamic ) {};
        try flash.external.ExternalInterface.addCallback("detachHandler",doDetach) catch ( e : Dynamic ) {};
        try flash.external.ExternalInterface.addCallback("removeHandler",doRemove) catch ( e : Dynamic ) {};

        //calls a callback indicating that this player is ready
        if(fvs.id != null)
            flash.external.ExternalInterface.call("onWavPlayerReady", fvs.id);
        else
            flash.external.ExternalInterface.call("onWavPlayerReady", flash.external.ExternalInterface.objectID);
    }
	
	static function initPlayerForUrl(?path: String) {
		if(path == null && player != null) return;
		
		if(path != null && (~/[.]mp3$/i).match(path)) {
			if(mp3player == null) {
				mp3player = new Mp3Player(path);
				AddPlayerEventListeners(mp3player);
			}
			player = mp3player;
        }
		else {
			if(wavplayer == null) {
				wavplayer = new Player(path);
				AddPlayerEventListeners(wavplayer);
			}
			player = wavplayer;
		}
	}	
	
	static function AddPlayerEventListeners(dispatcher: flash.events.EventDispatcher) {		
        dispatcher.addEventListener(PlayerEvent.BUFFERING, handleBuffering);
        dispatcher.addEventListener(PlayerEvent.PLAYING, handlePlaying);
        dispatcher.addEventListener(PlayerEvent.STOPPED, handleStopped);
        dispatcher.addEventListener(PlayerEvent.PAUSED, handlePaused);
		
        dispatcher.addEventListener(flash.events.ProgressEvent.PROGRESS, handleProgress);
        dispatcher.addEventListener(flash.events.IOErrorEvent.IO_ERROR, handleError);
		
        dispatcher.addEventListener(PlayerLoadEvent.LOAD, handleLoad);
	}
	
    static function handleSeeking(event:WavPlayerGuiEvent) {
        player.seek(event.position);
    }
    static function handleClicked(event:flash.events.Event) {
        trace("Clicked event: "+event);
        switch( state ) {
            case PlayerEvent.STOPPED:   player.play();
            case PlayerEvent.BUFFERING: player.stop();
            case PlayerEvent.PLAYING:   player.pause();
            case PlayerEvent.PAUSED:      player.resume();
        }
    }
    static function handleDblClicked(event:flash.events.Event) {
        trace("DoubleClick event: "+event);
        player.stop();
    }
    static function handleBuffering(event:PlayerEvent) {
        trace("Buffering event: "+event);
        state = event.type;
        if (event.position!=null) iface.setPosition(event.position);
        iface.drawBuffering();
        fireJsEvent(event.type, event.position);
    }
    static function handleError(event:flash.events.IOErrorEvent) {
        trace("Error event: "+event);
        fireJsEvent(event.type);
    }
    static function handlePlaying(event:PlayerEvent) {
        trace("Playing event: "+event);
        state = event.type;
        if (event.position!=null) iface.setPosition(event.position);
        iface.drawPlaying();
        fireJsEvent(event.type, event.position);
    }
    static function handleStopped(event:PlayerEvent) {
        trace("Stopped event: "+event);
        state = event.type;
        if (event.position!=null) iface.setPosition(event.position);
        iface.drawStopped();
        fireJsEvent(event.type, event.position);
    }
    static function handlePaused(event:PlayerEvent) {
        trace("Paused event: "+event);
        state = event.type;
        if (event.position!=null) iface.setPosition(event.position);
        iface.drawPaused();
        fireJsEvent(event.type, event.position);
    }
    static function handleLoad(event:PlayerLoadEvent) {
        trace("Load event: "+event);
        var now = Date.now().getTime();
        iface.setLength(event.SecondsTotal);
        iface.setReady(event.SecondsLoaded);
        if (lastNotifyLoad==0 || event.SecondsTotal-event.SecondsLoaded < 1e-4 || now - lastNotifyLoad > 500) {
            lastNotifyLoad = now;
            fireJsEvent(event.type, event.SecondsLoaded, event.SecondsTotal);
        }
    }
    static function handleProgress(event:flash.events.ProgressEvent) {
        trace("Progress event: "+event);
        var now = Date.now().getTime();
        if (lastNotifyProgress==0 || event.bytesLoaded == event.bytesTotal || now - lastNotifyProgress > 500) {
            lastNotifyProgress = now;
            fireJsEvent(event.type, event.bytesLoaded, event.bytesTotal);
        }
    }

    static function doGetVer( ) {
        return Version;
    }
    static function doPlay( ?fname: String, ?buffer: Float ) {
        player.stop();
        lastNotifyProgress = 0;
        lastNotifyLoad = 0;
        iface.setPosition(0);
		
		initPlayerForUrl(fname);
		
        player.play(fname, buffer);
    }
    static function doStop( ) {
        player.stop();
    }
    static function doPause( ) {
        player.pause();
    }
    static function doResume( ) {
        player.resume();
    }
    static function doSeek( ?pos: Float ) {
        player.seek(pos);
    }
    static function doVolume( ?volume: Float ): Float {
        if (volume != null) {
            player.volume = volume;
        }
		trace("doVolume("+volume+")");
        return player.volume;
    }
    static function doPan( ?pan: Float ): Float {
        if (pan != null) {
            player.pan = pan;
        }
		trace("doPan("+pan+")");
        return player.pan;
    }

    static function doAttach( event: String, handler: String, ?user: String ) {
        var id = handlerId++;
        handlers.push(new JsEventHandler(id, event, handler, user));
        return id;
    }
    static function doDetach( event: String, handler: String, ?user: String ) {
        handlers = handlers.filter(function(h: JsEventHandler): Bool {
                return !(h.Event==event && h.Handler == handler && h.User==user);
            });
    }
    static function doRemove( handler: Int ) {
        handlers = handlers.filter(function(h: JsEventHandler): Bool {
                return h.Id != handler;
            });
    }
    static function fireJsEvent( event: String, ?p1: Dynamic, ?p2: Dynamic) {
        for (h in handlers) {
            if (h.Event == event) {
                if (h.User != null) flash.external.ExternalInterface.call(h.Handler, h.User, p1, p2);
                else flash.external.ExternalInterface.call(h.Handler, p1, p2);
            } else
                if (h.Event == '*') {
                    if (h.User != null) flash.external.ExternalInterface.call(h.Handler, event, h.User, p1, p2);
                    else flash.external.ExternalInterface.call(h.Handler, event, p1, p2);
                }
        }
    }
}
