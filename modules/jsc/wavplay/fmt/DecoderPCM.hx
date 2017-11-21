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

// PCM sound decoder. Supports any bitlength
class DecoderPCM extends fmt.Decoder {
	var divisor : Int;
	var shift : Int;
	public function new(bps : Int, ?bs : Int) {
		sampleSize = Math.ceil(bps / 8);
		if (sampleSize == 0) throw "Unsupported BPS";
		divisor = 1 << (bps-1);
		shift = 1 << bps;
		sampleLength = 1;
	}
	public override function decode( InBuf : haxe.io.BytesData, Off: Int, Chan: Int, OutBuf: Array<Float>, OutOff: Int) : Int {
		var Sample: Int = 0;
		switch (sampleSize) {
		  case 1: Sample = InBuf[Off];
				  Sample -= divisor; // 1byte stored as unsigned
		  case 2: Sample = Std.int(InBuf[Off] + InBuf[Off+1] * 256);
				  if (Sample > divisor) Sample -= shift;
		  case 3: Sample = Std.int(InBuf[Off] + InBuf[Off+1] * 256 + InBuf[Off+2] * 65536);
				  if (Sample > divisor) Sample -= shift;
		  case 4: Sample = Std.int(InBuf[Off] + InBuf[Off+1] * 256 + InBuf[Off+2] * 65536 + InBuf[Off+3] * 16777216);
				  if (Sample > divisor) Sample -= shift;
		  default: for(c in 0...sampleSize) Sample += InBuf[Off+c] << (8*c);
				   if (Sample > divisor) Sample -= shift;
		}
		OutBuf[OutOff] = Sample / divisor;
		return 1;
	}
}
