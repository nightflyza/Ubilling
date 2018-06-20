//
// AudioSink
// Generated sound player from FOGG project
// http://bazaar.launchpad.net/~arkadini/fogg/trunk/files
// Licensed under GPL
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
package org.xiph.system;

//import org.xiph.system.Bytes;

import flash.Vector;

import flash.media.Sound;
import flash.media.SoundChannel;
import flash.media.SoundTransform;
import flash.events.SampleDataEvent;


class AudioSink extends flash.events.EventDispatcher {
    var buffer : Bytes;
    public var available : Int;
    var bufpos : Int;
    var triggered : Bool;
    var bufsize : Int;
    var trigger : Int;
    var bufstart : Int;
    var fill : Bool;
    var size : Int;
    public var pos : Float;
    public var volume(get_volume, set_volume): Float;
    public var pan(get_pan, set_pan): Float;
    public var soundTransform(get_soundTransform, set_soundTransform): SoundTransform;

    var s : Sound;
    var sch : SoundChannel;
    var schtr : SoundTransform;

    public function new(chunk_size : Int, fill = true, bufsize = 0, ?trigger : Int, ?st: SoundTransform) {
        super();
        size = chunk_size;
        this.fill = fill;
        this.bufsize = bufsize > 0 ? bufsize : 5*44100;
        this.trigger = trigger == null ? (bufsize > 0 ? bufsize : chunk_size) : trigger;
        triggered = false;
        trace("bufsize = "+this.bufsize+"; trigger="+this.trigger);

        buffer = new Bytes();
        available = 0;
        bufpos = 0;
        bufstart = 0;
        pos = 0.0;
        s = new Sound();
        s.addEventListener("sampleData", _data_cb);
        if (st == null) schtr = new SoundTransform();
        else schtr = st;
        sch = null;
    }

    public function set_volume(volume: Float): Float {
        this.schtr.volume=volume;
        this.soundTransform = this.soundTransform;
        return volume;
    }

    public function get_volume(): Float {
        return this.schtr.volume;
    }

    public function set_pan(pan: Float): Float {
        this.schtr.pan=pan;
        this.soundTransform = this.soundTransform;
        return this.schtr.pan;
    }

    public function get_pan(): Float {
        return this.schtr.pan;
    }

    public function set_soundTransform(st: SoundTransform): SoundTransform {
        this.schtr = st;
        if (this.sch!=null) {
            this.sch.soundTransform = this.schtr;
        }
        return this.schtr;
    }

    public function get_soundTransform(): SoundTransform {
        return this.schtr;
    }

    public function play(?position: Float) : Bool {
        if (sch!=null) return false;
        triggered = true;
        
        if (position!=null) {
            pos = position;
            var startpos = Math.ceil( pos*44100.0 ) - bufstart;
            trace("Playback from "+pos+" bufferpos="+startpos);
            if (startpos != bufpos) { // Need to seek in buffer
                trace("Need to seek to new buffer position: "+startpos+" / "+bufpos);
                if (startpos < 0) {
                    trace("Need to seek in past, we can't");
                    return false;
                }
                if (startpos > bufpos+available-size) {
                    trace("Need to seek in future, we can't");
                    return false;
                }
                var diff = bufpos - startpos;
                bufpos = startpos;
                available += diff;
            }
            pos = (bufstart + bufpos) / 44100.0;
        } else {
            bufstart += Math.ceil( pos * 44100 );
        }
        
        trace("playing");
        sch = s.play();
        sch.soundTransform = this.schtr;
        trace("SoundTransform volume = "+this.schtr.volume);
        sch.addEventListener(flash.events.Event.SOUND_COMPLETE, soundCompleteHandler);
        dispatchEvent(new PlayerEvent(PlayerEvent.PLAYING, pos));
        return true;
    }

    public function soundCompleteHandler(e:flash.events.Event):Void {
        sch = null;
        trace("Sound Complete: "+e);
        dispatchEvent(new PlayerEvent(PlayerEvent.STOPPED, 0.0));
    }

    public function pause() : Float {
        return stop(false);
    }
    
    public function stop(fireEvent: Bool = true) : Null<Float> {
        if (sch != null) {
            pos += sch.position / 1000.0;
            sch.stop();
            if (fireEvent)
                dispatchEvent(new PlayerEvent(PlayerEvent.STOPPED, pos));
            sch = null;
        }
        triggered = true;
        return pos;
    }

    function _data_cb(event : SampleDataEvent) : Void {
        trace("_data_cb "+event.position);
        var i : Int;
        var to_write : Int = available > size ? size : available;
        var missing = to_write < size ? size - to_write : 0;
        var bytes : Int = to_write * 8;
        if (to_write > 0) {
            event.data.writeBytes(buffer, bufpos * 8, bytes);

            trace("Bufstart="+bufstart+"; bufpos="+bufpos+"; avail="+available+"to_write = "+to_write);

            var bufend = available + bufpos;
            bufpos += to_write;
            available -= to_write;
            if (bufpos > bufsize) {
                var cutsize = bufpos - bufsize;
                bufpos = bufsize;
                bufstart += cutsize;
                System.bytescopy(buffer, cutsize*8, buffer, 0, (bufend-cutsize)*8);
            }
        }
        i = 0;
        if (missing > 0 && missing != size && fill) {
            trace("samples data underrun: " + missing);
            while (i < missing) {
                untyped {
                event.data.writeFloat(0.0);
                event.data.writeFloat(0.0);
                };
                i++;
            }
        } else if (missing > 0) {
            trace("not enough data, stopping");
            //stop();
        }
    }

    public function write(pcm : Array<Array<Float>>, index : Array<Int>,
                          samples : Int, last : Bool = false) : Void {
        var i : Int;
        var end : Int;
        buffer.position = (available+bufpos) * 8; // 2 ch * 4 bytes per sample (float)
        if (pcm.length == 1) {
            // one channel
            var c = pcm[0];
            var s : Float;
            i = index[0];
            end = i + samples;
            while (i < samples) {
                s = c[i++];
                buffer.writeFloat(s);
                buffer.writeFloat(s);
            }
        } else if (pcm.length == 2) {
            // two channels
            var c1 = pcm[0];
            var c2 = pcm[1];
            i = index[0];
            var i2 = index[1];
            end = i + samples;
            while (i < end) {
                buffer.writeFloat(c1[i]);
                buffer.writeFloat(c2[i2++]);
                i++;
            }
        } else {
            throw "-EWRONGNUMCHANNELS";
        }

        available += samples;
        if (!triggered && (last || (trigger > 0 && available > trigger))) {
            play();
        }
    }
}
