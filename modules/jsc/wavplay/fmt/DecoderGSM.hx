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

package fmt;
import org.tritonus.lowlevel.gsm.GSMDecoder;

class DecoderGSM extends fmt.Decoder {
	private var wavmode : Bool;
    private var decoder : GSMDecoder;
	private var temp : haxe.io.BytesData;
	public function new(bps : Int, ?bs : Int) {
		if (bps == 264 || (bps == 0 && bs==33)) { // Standarts mode, 264bit GSM -> 160 samples)
			wavmode = false;
			sampleSize = 33;
			sampleLength = 160;
		} else
		if (bps == 260 || (bps == 0 && bs==65)) { // WAV mode: 65 bytes per twin 32+33 packs
			wavmode = true;
			sampleSize = 65; 
			temp = new haxe.io.BytesData();
			sampleLength = 320;
		} else 
			throw "Unsupported BPS";
		decoder = new GSMDecoder();
	}
	public override function decode( InBuf : haxe.io.BytesData, InOff: Int, Chan: Int, OutBuf : Array<Float>, OutOff: Int) : Int {
		decoder.decode( InBuf, InOff, OutBuf, OutOff, wavmode );
		return wavmode ? 320 : 160;
	}
}
