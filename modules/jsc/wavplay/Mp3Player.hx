import flash.media.SoundTransform;

class Mp3Player extends flash.events.EventDispatcher, implements IPlayer {
    var File : flash.net.URLStream;
	var sound : flash.media.Sound;
	var channel : flash.media.SoundChannel;
    var pitch : Array<Float>;
    var buffer : Array<Array<Float>>;
    var padding : Array<Float>;
    var in_off : Array<Float>;
    var fname : String;
    var first : Bool;
    var trigger : Null<Float>;
    var pos : Null<Float>;
	var playTimer : flash.utils.Timer;

    var schtr: SoundTransform;
    public var volume(get_volume, set_volume): Float;
    public var pan(get_pan, set_pan): Float;
    public var soundTransform(get_soundTransform, set_soundTransform): SoundTransform;

    public function new(?path : String) {
        super();
        schtr = new SoundTransform();
        fname = path;
        File = null;
    }

    public function play(?path : String, ?trigger_buffer : Float) {
		// Now we don't use trigger_buffer variable anyhow. May be we don't need it
        if (path != null) fname = path;
        if (fname == null) throw "No sound URL given";
		
        trace("Mp3Player for " + fname);
        
		try {
			File = new flash.net.URLStream();
            var Req = new flash.net.URLRequest(fname);
            
			sound = new flash.media.Sound();
			sound.addEventListener(flash.events.IOErrorEvent.IO_ERROR, ioErrorHandler);
			
			sound.load(Req);
			dispatchEvent(new PlayerEvent(PlayerEvent.BUFFERING, 0));
		}
        catch (error : Dynamic) {
            trace("Unable to load: " + error);
            throw error;
        }		
		
	    if (sound != null)
	    {
			// Add the event listeners for load progress and load
			// complete
			sound.addEventListener(flash.events.ProgressEvent.PROGRESS, progressHandler);
			sound.addEventListener(flash.events.Event.COMPLETE, completeHandler);

			// If there's a channel
			if (channel != null)
			{
				channel.stop();				
				
				// Play the music
				channel = sound.play(0);
			}
			else {
			
				// Play the music
				channel = sound.play(0);
				if (this.schtr != null) {
					this.channel.soundTransform = this.schtr;
				}
				
				// Add the event listener for sound complete
				channel.addEventListener(flash.events.Event.SOUND_COMPLETE, stoppedEvent);
							 
				// Start a timer to show play progress, there's no
				// play progress event
				startPlayTimer();
			}
	    }
	}
	
    public function set_volume(volume: Float): Float {
        this.schtr.volume = volume;
        trace("mp3 set_volume(" + volume + ")");
        this.soundTransform = this.soundTransform; // Apply changes
        return volume;
    }

    public function get_volume(): Float {
        return this.schtr.volume;
    }

    public function set_pan(pan: Float): Float {
        this.schtr.pan = pan;
        this.soundTransform = this.soundTransform; // Apply changes
        return this.schtr.pan;
    }

    public function get_pan(): Float {
        return this.schtr.pan;
    }

    public function set_soundTransform(st: SoundTransform): SoundTransform {
        this.schtr = st;
        if (this.channel != null) {
            this.channel.soundTransform = this.schtr;
        }
        return this.schtr;
    }

    public function get_soundTransform(): SoundTransform {
        return this.schtr;
    }

	function startPlayTimer() {
		if (playTimer != null)
			playTimer.stop();

		// Timer for emulating Playing event
		playTimer = new flash.utils.Timer(100, Math.round(sound.length / 100));
		playTimer.addEventListener(flash.events.TimerEvent.TIMER, playingEvent);

		playTimer.start();
    }
    function playingEvent(event: flash.events.Event) {
		if (channel != null) {
			pos = channel.position;
		}
        dispatchEvent(new PlayerEvent(PlayerEvent.PLAYING, pos));
    }
    function stoppedEvent(event: flash.events.Event) {
        dispatchEvent(new PlayerEvent(PlayerEvent.STOPPED, pos));
    }

    public function pause() {
		if (channel != null)
		{
				pos = channel.position;
				channel.stop();
				
				trace("mp3 Paused pos = " + pos);
				dispatchEvent(new PlayerEvent(PlayerEvent.PAUSED, pos));

				if (playTimer != null)
				playTimer.stop();
		}
    }
    public function resume() {
        trace("mp3 Try to resume from " + pos);
		channel.stop();
		
        if (pos != null) {
			channel = sound.play(pos);
        }
        else play();
    }
    public function seek(pos: Float) {        
			channel.stop();
			channel = sound.play(pos);
			
			dispatchEvent(new PlayerEvent(PlayerEvent.BUFFERING, pos));
    }
    
    public function stop() {
		trace("mp3 Stopped position = " + channel.position);
		pos = channel.position;
		channel.stop();
		
        if (File != null) {
            File.close();
            File = null;
            dispatchEvent(new PlayerEvent(PlayerEvent.STOPPED, 0.0));
        }
        if (playTimer != null) {
            playTimer = null;
        }
    }

    function completeHandler(event: flash.events.Event) {
        trace("mp3 completeHandler: " + event);
        dispatchEvent( new PlayerLoadEvent(PlayerLoadEvent.LOAD, false, false, sound.length, sound.length) );
        dispatchEvent(event);
    }
	
    function progressHandler(event: flash.events.ProgressEvent) {
        trace("mp3 progressHandler: " + event);
        dispatchEvent(event); // here we fire byte progress
		
		// dirty hack but could work correct I suppose :)
		var percent = event.bytesLoaded / event.bytesTotal;
		dispatchEvent( new PlayerLoadEvent(PlayerLoadEvent.LOAD, false, false, sound.length * percent, sound.length) );
    }
	
    function ioErrorHandler(event: flash.events.IOErrorEvent) {
        trace("mp3 ERROR ERROR");
                dispatchEvent(event);
    }
}
