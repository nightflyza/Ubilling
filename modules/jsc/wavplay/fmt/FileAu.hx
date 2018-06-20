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

// FileAu: stream AU file reader
// Reads any implemented codecs from it
class FileAu extends fmt.File {
	var fileSize: Int;
	var format: Int;

	public function new() {
		super();
		fileSize = 0;
		format = 0;
	}

	// Set known full file length
	public override function setSize(size: Int): Void {
		fileSize = size;
	}
	
	// Get estimated sound length
	public override function getEtaLength(): Null<Float> {
		if (Readed < 0 || dataOff == 0 || Readed<dataOff) return null;
		return super.getEtaLength();
	}
	
	// Read file header
	public override function readHeader() {
		var i = Readed;
		while (i<bufsize) {
			if (dataOff == 0 || i < dataOff) { // Read header
				if (i < 24) {
					if (bufsize-i < 4) break;
					var DW = Buffer[i]*16777216+Buffer[i+1]*65536+Buffer[i+2]*256+Buffer[i+3];
					switch( i ) {
					  case 0:
						if (DW != 0x2E736E64) {
							trace("Wrong file magic! Got "+DW+" instead of 779316836");
							Readed = -1;
							return;
						}
					  case 4:
						dataOff = DW;
						if (dataOff < 24) dataOff = 24;
						trace("dataOff = "+dataOff);
					  case 8:
						dataSize = DW;
						if (dataSize == 0) dataSize = -1; // Fix for incorrect AU file
						if (fileSize > 0 && dataSize == -1)
							dataSize = fileSize - dataOff; // Calc length if we can
						trace("dataSize = "+dataSize);
					  case 12:
						format = DW;
						trace("format = "+format);
					  case 16:
						rate = DW;
						trace("rate = "+rate);
					  case 20:
						channels = DW;
						trace("channels = "+channels);
					}
					i += 4;
					if (i==24) { // Header complete: check consistency
						if (channels < 1 || channels > 2) {
							trace("Wrong number of channels: "+channels);
							Readed = -1;
							return;
						}
						switch ( format ) {
						  case 1:
							trace("File in 8-bit G.711 mu-law format");
							sndDecoder = new DecoderG711u(8, false);
						  case 27: // Really 27!
							trace("File in 8-bit G.711 a-law format");
							sndDecoder = new DecoderG711a(8, false);
						  case 2:
							trace("File in 8-bit PCM format");
							sndDecoder = new DecoderPCM(8);
						  case 3:
							trace("File in 16-bit PCM format");
							sndDecoder = new DecoderPCM(16);
						  case 4:
							trace("File in 24-bit PCM format");
							sndDecoder = new DecoderPCM(24);
						  case 5:
							trace("File in 32-bit PCM format");
							sndDecoder = new DecoderPCM(32);
						  default:
							trace("File in unknown format #"+format);
							Readed = -1;
							return;
						}
						chunkSize = sndDecoder.sampleSize*channels;
						init();
					}
				} else {
					var NeedSkip = dataOff - (Readed+i);
					trace("dataOff = "+dataOff+"; Readed="+Readed+"; i="+i+"; bufsize="+bufsize+"; NeedSkip="+NeedSkip);
					if (NeedSkip > bufsize-i) {
						i = bufsize;
					} else {
						i += NeedSkip;
					}
				}
			} else { // Read sound
				break;
			}
		}
		Readed = i;
	}

	// Returns is stream ready to operate: header readed (1), not ready (0), error(-1)
	public override function ready(): Int {
		if (Readed < 0) return -1;
		if (channels == 0 || chunkSize == 0 || rate == 0 || sndDecoder==null || Readed < dataOff) return 0;
		return 1;
	}
}
