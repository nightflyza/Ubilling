//
// WAV/AU Flash player with resampler
// 
// Copyright (c) 2011, Anton Fedorov <datacompboy@call2ru.com>
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

// IMA ADPCM decoder for MS/4bit
class IMAADPCM {
	var predictor : Int;
	var index : Int;
	var step : Int;
	var proceed : Int;
	var resync : Int;
	var spb : Int;

    static var ima_index_table : Array<Int> = [
		-1, -1, -1, -1, 2, 4, 6, 8,
		-1, -1, -1, -1, 2, 4, 6, 8
	];
	static var ima_step_table : Array<Int> = [ // 89 values 0-88
		7, 8, 9, 10, 11, 12, 13, 14, 16, 17, 
		19, 21, 23, 25, 28, 31, 34, 37, 41, 45, 
		50, 55, 60, 66, 73, 80, 88, 97, 107, 118, 
		130, 143, 157, 173, 190, 209, 230, 253, 279, 307,
		337, 371, 408, 449, 494, 544, 598, 658, 724, 796,
		876, 963, 1060, 1166, 1282, 1411, 1552, 1707, 1878, 2066, 
		2272, 2499, 2749, 3024, 3327, 3660, 4026, 4428, 4871, 5358,
		5894, 6484, 7132, 7845, 8630, 9493, 10442, 11487, 12635, 13899, 
		15289, 16818, 18500, 20350, 22385, 24623, 27086, 29794, 32767 
	];
	
	public function new(samples: Int) {
		proceed = 0;
		resync = Std.int((samples+7)/8);
		spb = samples;
	}

	public function reset(): Int {
		proceed = 0;
		return resync;
	}

	function calc(nibble: Int): Float {
		var diff: Int;
		step = ima_step_table[index];
		index += ima_index_table[nibble];
		if (index < 0) index = 0;
		if (index > 88) index = 88;
		//diff = Std.int(((nibble&7)+0.5)*step/4.0);
		diff = step >> 3;
		if (nibble&4!=0) diff += step;
		if (nibble&2!=0) diff += step>>1;
		if (nibble&1!=0) diff += step>>2;
		if (nibble&8!=0) {
			predictor -= diff;
		} else {
			predictor += diff;
		}
		if (predictor < -32768) predictor = -32768;
		if (predictor > 32767) predictor = 32767;
		return predictor / 32767.0;
	}

	public function decodeLength(chunks: Int): Int {
		var f = Math.floor(chunks / resync);
		var r = chunks % resync;
		if (r>0) r = r*8 - 7;
		return f*spb+r;
	}

	public function decode( InBuf : haxe.io.BytesData, Off: Int, OutBuf: Array<Float>, OutOff: Int) : Int {
		if ((proceed++) % resync == 0) {
			// Read initial pack
			predictor = InBuf[Off+1] * 256 + InBuf[Off];
			if (predictor > 32767) predictor = predictor-65536;
			index = InBuf[Off+2];
			OutBuf[OutOff] = predictor/32767.0;
			return 1;
		} else {
			for(i in 0...4) {
				var n: Int = InBuf[Off++];
				OutBuf[OutOff++] = calc(n&0x0F);
				OutBuf[OutOff++] = calc(n>>4);
			}
			return 8;
		}
	}
}
class DecoderIMAADPCM extends fmt.Decoder {
	var channels : Array<IMAADPCM>;

	public function new(bps : Int, chans: Int, ?align: Int, ?samplesPerBlock: Int) {
		var i: Int;
		if (bps != 4) {
			trace("Unsupported BPS");
			throw "Unsupported BPS";
		}
		sampleSize = 4;
		sampleLength = 8;
		if ( ((samplesPerBlock-1)/8+1)*chans != align/4) {
			trace("Unsupported packing ("+(((samplesPerBlock-1)/8+1)*chans)+" != "+(align/4)+")");
			throw "Unsupported packing";
		}
		channels = new Array<IMAADPCM>();
		for (i in 0...chans) {
			channels.push( new IMAADPCM(samplesPerBlock) );
		}
	}

	public override function seek ( chunk: Int ) : Int {
		var chu: Int;
		var res: Int = 1;
		for (channel in channels) {
			res = channel.reset();
		}
		chu = Math.floor(chunk / res) * res;
		return chu;
	}

	public override function decodeLength(chunks: Int): Int {
		return channels[0].decodeLength(chunks);
	}

	public override function decode( InBuf : haxe.io.BytesData, Off: Int, Chan: Int, OutBuf: Array<Float>, OutOff: Int) : Int {
		return channels[Chan].decode(InBuf, Off, OutBuf, OutOff);
	}
}

