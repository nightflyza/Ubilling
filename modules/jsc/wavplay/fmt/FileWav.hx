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

// FileWav: stream WAV file reader
// Currently able to read only files with one <data> block
package fmt;

class FileWav extends fmt.File {
	var State: Int;
	var fileSize: Int;
	var format: Int;
	var bps : Int;

	public function new() {
		super();
		fileSize = 0;
		format = 0;
		State = 0;
		bps = 0;
	}

	// Set known full file length
	public override function setSize(size: Int): Void {
		fileSize = size;
	}

	// Get estimated sound length
	public override function getEtaLength(): Null<Float> {
		if (Readed < 0 || State<4 || rate==0 || chunkSize==0 || dataSize==0) return null;
		return super.getEtaLength();
	}

	// Read file header
	public override function readHeader() {
		var i = Readed;
		while (i<bufsize) {
			switch (State) {
			  case 0: // Read RIFF header
				if (i < 12) {
					if (bufsize-i < 4) break;
					var DW = Buffer[i+3]*16777216+Buffer[i+2]*65536+Buffer[i+1]*256+Buffer[i];
					switch( i ) {
					  case 0:
						if (DW != 0x46464952) {
							trace("Wrong RIFF magic! Got "+DW+" instead of 0x46464952");
							Readed = -1;
							return;
						}
					  case 4:
						dataSize = DW;
						trace("dataSize = "+dataSize);
					  case 8:
						if (DW != 0x45564157) {
							trace("Wrong WAVE magic! Got "+DW+" instead of 0x45564157");
							Readed = -1;
							return;
						}
					}
					i += 4;
				}
				if (i == 12) { // RIFF header skipped, go to WAVE blocks
					State++;
					dataOff = i;
				}
			  case 1: // Read fmt block
				if (i-dataOff < 24 || i-dataOff < dataSize+8) {
					if (bufsize-i < 4) break;
					var W1 = Buffer[i+1]*256+Buffer[i];
					var W2 = Buffer[i+3]*256+Buffer[i+2];
					var DW = W2*65536+W1;
					switch( i-dataOff ) {
					  case 0:
						if (DW != 0x20746D66) {
							trace("Wrong 'fmt ' magic! Got "+DW+" instead of 0x20746D66");
							Readed = -1;
							return;
						}
					  case 4:
						dataSize = DW;
						trace("dataSize2 = "+dataSize);
					  case 8:
						channels = W2;
						format = W1;
						trace("format = "+format+"; channels = "+channels);
					  case 12:
						rate = DW;
						trace("rate = "+rate);
					  case 20:
						bps = W2;
						align = W1;
						trace("align = "+align+"; bps="+bps);
					  case 24:
						trace("Appendix W1="+W1+", W2="+W2+", DW="+DW);
					}
					i += 4;
					if (i-dataOff >= dataSize+8) {
                        i = dataSize+8 + dataOff; // Workaround for non-round fmt section
						if (channels < 1 || channels > 2) {
							trace("Wrong number of channels: "+channels);
							Readed = -1;
							return;
						}
						switch ( format ) {
						  case 1:
							trace("File in PCM");
							sndDecoder = new DecoderPCM(bps);
						  case 65534:
							trace("File in (Bad?) PCM");
							sndDecoder = new DecoderPCM(bps);
						  case 2:
							trace("File in MS ADPCM");
							Readed = -1; return;
							//sndDecoder = new DecoderMSADPCM(bps);
						  case 6:
							trace("File in 8-bit G.711 a-law format");
							sndDecoder = new DecoderG711a(bps, false);
						  case 7:
							trace("File in 8-bit G.711 mu-law format");
							sndDecoder = new DecoderG711u(bps, false);
						  case 17:
							trace("File in IMA ADPCM");
							sndDecoder = new DecoderIMAADPCM(bps, channels, align, W2);
							align = 0; // hack :(
							//Readed = -1; return;
						  case 20:
							trace("File in G.723 ADPCM");
							Readed = -1; return;
							//sndDecoder = new DecoderG723ADPCM(bps);
						  case 49:
							trace("File in GSM 6.10");
							sndDecoder = new DecoderGSM(bps, align);
						  case 64:
							trace("File in G.721 ADPCM");
							Readed = -1; return;
							//sndDecoder = new DecoderG721ADPCM(bps);
						  case 80:
							trace("File in MPEG");
							//sndDecoder = new DecoderMPEG(bps);
							Readed = -1; return;
						  default:
							trace("File in unknown/unsupported format #"+format);
							Readed = -1;
							return;
						}
						chunkSize = sndDecoder.sampleSize*channels;
						init();
						if (align > chunkSize) align -= chunkSize; else align = 0;
						if (i-dataOff == dataSize+8) {
							State++;
							dataOff = i;
						}
						trace("chunkSize = "+chunkSize+"; sampleSize="+sndDecoder.sampleSize+"; align="+align);
					}
				}
				else if (i-dataOff < dataSize+8) {
					var NeedSkip = (dataSize+8) - (i-dataOff);
					trace("dataOff = "+dataOff+"; dataSize = "+dataSize+"; Readed="+Readed+"; i="+i+"; bufsize="+bufsize+"; NeedSkip="+NeedSkip);
					if (NeedSkip > bufsize-i) {
						i = bufsize;
					} else {
						i += NeedSkip;
					}
					if (i-dataOff == dataSize+8) {
						State++;
						dataOff = i;
					}
				}
			  case 2: // Read any misc header
				if (i-dataOff < 8) {
					if (bufsize-i < 4) break;
					var DW = Buffer[i+3]*16777216+Buffer[i+2]*65536+Buffer[i+1]*256+Buffer[i];
					switch( i-dataOff ) {
					  case 0:
						if (DW == 0x61746164) {
							trace("Data block!");
							State++;
						} else
							trace("Unknown block, skipping ("+DW+")");
					  case 4:
						dataSize = DW;
						trace("dataSize3 = "+dataSize);
					}
					i += 4;
				}
				if (i-dataOff >= 8 && i-dataOff <= dataSize+8) {
					var NeedSkip = (dataSize+8) - (i-dataOff);
					trace("dataOff = "+dataOff+"; dataSize = "+dataSize+"; Readed="+Readed+"; i="+i+"; bufsize="+bufsize+"; NeedSkip="+NeedSkip);
					if (NeedSkip > bufsize-i) {
						i = bufsize;
					} else {
						i += NeedSkip;
					}
					if (i-dataOff == dataSize+8) {
						dataOff = i;
					}
				}
			  case 3: // Read data header
				if (i-dataOff < 8) {
					if (bufsize-i < 4) break;
					var DW = Buffer[i+3]*16777216+Buffer[i+2]*65536+Buffer[i+1]*256+Buffer[i];
					switch( i-dataOff ) {
					  case 0:
						if (DW != 0x61746164) {
							trace("Wrong 'data' magic! Got "+DW+" instead of 0x61746164");
							Readed = -1;
							return;
						}
					  case 4:
						dataSize = DW;
						trace("dataSize(data) = "+dataSize);
						if (dataSize <= 0 && fileSize > 0)
							dataSize = fileSize - dataOff; // Try to get data size
						// Todo: support multiple "DATA" chunks in file, skipping unknown blocks
					}
					i += 4;
				}
				if (i-dataOff == 8) {
					State++;
					dataOff = i;
					trace("Get data block begin (dataOff="+dataOff+"; State="+State+"; bufsize="+bufsize+")");
				}
			  default: // Read sound stream
				break;
			}
		}
		// Remove processed bytes
		Readed = i;
	}

	// Returns is stream ready to operate: header readed (1), not ready (0), error(-1)
	public override function ready(): Int 
	{
		if (Readed < 0) return -1;
		if (State < 4) return 0;
		if (channels == 0 || chunkSize == 0 || rate == 0 || sndDecoder==null) return -1; // if we got audio state without info -- error
		return 1;
	}
}
