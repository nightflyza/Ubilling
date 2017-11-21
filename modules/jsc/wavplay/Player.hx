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
import flash.media.SoundTransform;

// Main player class: loads stream, process it by appropriate file decoder,
// that will initialize correct sound decoder. Decoded audio samples 
// resample to 44100 and play via AudioSink
class Player extends flash.events.EventDispatcher, implements IPlayer {
    var File : flash.net.URLStream;
    var Sound : fmt.File;
    var Resampler : com.sun.media.sound.SoftAbstractResampler;
    var pitch : Array<Float>;
    var asink : org.xiph.system.AudioSink;
    var buffer : Array<Array<Float>>;
    var padding : Array<Float>;
    var in_off : Array<Float>;
    var fname : String;
    var first : Bool;
    var timer : flash.utils.Timer;
    var trigger : Null<Float>;
    var pos : Null<Float>;

    var schtr: SoundTransform;
    public var volume(get_volume, set_volume): Float;
    public var pan(get_pan, set_pan): Float;
    public var soundTransform(get_soundTransform, set_soundTransform): SoundTransform;

    public function new(?path : String) {
        super();
        schtr = new SoundTransform();
        fname = path;
        asink = null;
        File = null;
    }

    public function play(?path : String, ?trigger_buffer : Float) {
        if (trigger_buffer != null) trigger = trigger_buffer;
        if (path != null) fname = path;
        if (fname == null) throw "No sound URL given";
        // To-do: re-play already loaded stream
        pitch = new Array<Float>();
        trace("Player for "+fname);
        var slnrx = ~/[.](sln(\d{1,3}))$/i;
        if ((~/[.]au$/i).match(fname)) {
            Sound = new fmt.FileAu();
        } else
        if ((~/[.]wav(49)?$/i).match(fname)) {
            Sound = new fmt.FileWav();
        } else
        if ((~/[.](sln|raw)$/i).match(fname)) {
            Sound = new fmt.FileSln();
        } else
        if (slnrx.match(fname)) {
            Sound = new fmt.FileSln(Std.parseInt(slnrx.matched(2)) * 1000);
        } else
        if ((~/[.](alaw|al)$/i).match(fname)) {
            Sound = new fmt.FileAlaw();
        } else
        if ((~/[.](ulaw|ul|pcm|mu)$/i).match(fname)) {
            Sound = new fmt.FileUlaw();
        } else
        if ((~/[.]la$/i).match(fname)) {
            Sound = new fmt.FileAlawInv();
        } else
        if ((~/[.]lu$/i).match(fname)) {
            Sound = new fmt.FileUlawInv();
        } else
        if ((~/[.]gsm$/i).match(fname)) {
            Sound = new fmt.FileGsm();
        } else {
            trace("Unsupported file type");
            throw "Unsupported file type";
        }
        Resampler = new com.sun.media.sound.SoftLanczosResampler();
        initAsink();
        try {
            File = new flash.net.URLStream();
            var Req = new flash.net.URLRequest(fname);
            File.addEventListener(flash.events.Event.COMPLETE, completeHandler);
            File.addEventListener(flash.events.ProgressEvent.PROGRESS, progressHandler);
            File.addEventListener(flash.events.IOErrorEvent.IO_ERROR, errorHandler);
            trace("Load begin!");
            first = true;
            File.load(Req);
            dispatchEvent(new PlayerEvent(PlayerEvent.BUFFERING, 0));
            timer = new flash.utils.Timer(100);
            timer.addEventListener( flash.events.TimerEvent.TIMER, timeout );
            timer.start();
        }
        catch (error : Dynamic) {
            trace("Unable to load: "+error);
            throw error;
        }
    }
    function initAsink() {
        try {
            asink = new org.xiph.system.AudioSink(8192, true, 44100*5, trigger==null?null:Math.round(trigger*44100), schtr);
            asink.addEventListener(PlayerEvent.PLAYING, playingEvent);
            asink.addEventListener(PlayerEvent.STOPPED, stoppedEvent);
        } catch (error : Dynamic) {
            trace("Unable to load: "+error);
            //trace(haxe.Stack.exceptionStack());
            throw error;
        }
    }

    public function set_volume(volume: Float): Float {
        this.schtr.volume=volume;
        trace("set_volume("+volume+")");
        this.soundTransform = this.soundTransform; // Apply changes
        return volume;
    }

    public function get_volume(): Float {
        return this.schtr.volume;
    }

    public function set_pan(pan: Float): Float {
        this.schtr.pan=pan;
        this.soundTransform = this.soundTransform; // Apply changes
        return this.schtr.pan;
    }

    public function get_pan(): Float {
        return this.schtr.pan;
    }

    public function set_soundTransform(st: SoundTransform): SoundTransform {
        this.schtr = st;
        if (this.asink!=null) {
            this.asink.soundTransform = this.schtr;
        }
        return this.schtr;
    }

    public function get_soundTransform(): SoundTransform {
        return this.schtr;
    }

    function playingEvent(event:PlayerEvent) {
        dispatchEvent(new PlayerEvent(PlayerEvent.PLAYING, event.position));
    }
    function stoppedEvent(event:PlayerEvent) {
        dispatchEvent(new PlayerEvent(PlayerEvent.STOPPED, event.position));
    }

    public function pause() {
        if (asink != null) {
            pos = asink.pause();
            trace("Paused pos = "+pos);
            dispatchEvent(new PlayerEvent(PlayerEvent.PAUSED, pos));
        }
        else stop();
    }
    public function resume() {
        trace("Try to resume from"+pos);
        if (pos!=null) {
            asink.play(pos);
        }
        else play();
    }
    public function seek(pos: Float) {
        if (asink != null && Sound != null && Sound.ready()==1) {
            asink.pause();
            if (!asink.play(pos)) { // If we seek outside prepared buffer, need to re-setup resampler buffer
                asink = null;
                initAsink();
                initResampler();
                asink.pos += Sound.seek(pos);
                dispatchEvent(new PlayerEvent(PlayerEvent.BUFFERING, asink.pos));
                // timeout handler will populate from new pos when ready
            }
        }
    }
    
    public function stop() {
        if (asink != null) {
            var pos = asink.stop();
            trace("Stopped position = "+pos);
            asink = null;
        }
        if (File != null) {
            File.close();
            File = null;
            dispatchEvent(new PlayerEvent(PlayerEvent.STOPPED, 0.0));
        }
        if (timer != null) {
            timer = null;
        }
    }

    function completeHandler(event:flash.events.Event) {
        trace("completeHandler: " + event);
        timeout(null);
        dispatchEvent(event);
    }
    function progressHandler(event:flash.events.ProgressEvent) {
        trace("progressHandler: " + event);
        if (first) {
            first = false;
            if (event.bytesTotal>0)
                Sound.setSize(Std.int(event.bytesTotal));
        }
        dispatchEvent(event); // here we fire byte progress
    }
    function errorHandler(event:flash.events.IOErrorEvent) {
        trace("ERROR ERROR");
                dispatchEvent(event);
    }

    function timeout(event:Null<flash.events.Event>) {
        if (asink.available < 44100*5) {
            read(event == null);
            Sound.populate( Math.ceil(Math.min(Sound.getRate(), Sound.getRate()*((44100*5-asink.available)/44100.0) )) );
            populate();
        }
    }

    function read(last: Bool) {
        if (File.bytesAvailable > 0) {
            Sound.push( File, last );
            dispatchEvent( new PlayerLoadEvent(PlayerLoadEvent.LOAD, false, false, Sound.getLoadedLength(), Sound.getEtaLength()) );
            //trace("Sound ready = "+Sound.ready()+"; rate="+Sound.getRate()+"; channels="+Sound.getChannels()+"; samples="+Sound.samplesAvailable());
        }
    }
    
    function initResampler() {
        if (Sound.getRate() != 44100) {
            pitch[0] = Sound.getRate() / 44100.0;
            trace("Resample with "+pitch[0]+" pitch");
            buffer = new Array<Array<Float>>();
            if (padding == null) {
                padding = new Array<Float>();
                for( k in 0...Resampler.getPadding() )
                    padding.push( 0.0 ); // Fill startup padding
            }
            for( c in 0...Sound.getChannels() ) {
                // Fill with double padding
                buffer.push( padding.copy() );
                buffer[c] = buffer[c].concat( padding );
            }
            in_off = new Array<Float>();
            in_off[0] = Resampler.getPadding();
            asink.pos -= (padding.length / Sound.getRate());        
        }
    }
    
    function populate() {
        if (Sound.samplesAvailable()>0) {
            if (Sound.getRate() == 44100) {
                var Samples = Sound.getSamples();
                var ind = new Array<Int>(); ind[0] = 0;
                var cnt = Samples[0].length;
                asink.write(Samples, ind, cnt, Sound.last);
            } else {
                var Samples = Sound.getSamples();
                if (pitch.length != 1)
                    initResampler();
                var Res = new Array<Array<Float>>();
                var out_off = new Array<Int>();
                var inOff = in_off[0];
                // Conversion needs padding samples before and padding samples after
                // So, for last pack we need to add one more padding zone
                for( c in 0...Sound.getChannels() ) {
                    buffer[c] = buffer[c].concat( Samples[c] );
                    if (Sound.last) {
                        buffer[c] = buffer[c].concat( padding );
                        buffer[c] = buffer[c].concat( padding );
                    }
                    Res.push( new Array<Float>() );
                    in_off[0] = inOff;
                    out_off[0] = 0;
                    // Note: number of last element, not count!
                    // Always hold 1 padding left and 1 padding right
                    var in_end: Float = buffer[c].length-padding.length;
                    var out_end: Int = (Std.int( buffer[0].length / pitch[0] + 1 )+Resampler.getPadding())*5;
                    Resampler.interpolate(buffer[c], in_off, in_end, pitch, 0, Res[c], out_off, out_end);
                }

                // Write resampled sound
                var ind = new Array<Int>(); ind[0] = 0;
                asink.write(Res, ind, out_off[0], Sound.last);

                // Shift buffers
                for( c in 0...Sound.getChannels() ) {
                    buffer[c].splice(0, Std.int( in_off[0] )-2*padding.length );
                }
                in_off[0] -= Std.int( in_off[0]-2*padding.length );
            }
        }
    }
}
