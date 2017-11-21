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

// Generic file stream scanner interface
interface IFile {
	var last: Bool;
	// Push data from audio stream to decoder
	function push(bytes: flash.utils.IDataInput, last:Bool): Void;
	// Require decoder to populate at least <samples> samples from audio stream
	function populate(samples: Int): Void;
	// Rewind on input stream to Pos, and return real pos we have seeked, or seeking to
	function seek(Pos: Float): Float;
	// Returns is stream ready to operate: header readed (1), not ready (0), error(-1)
	function ready(): Int;
	// Get sound samplerate is Hz
	function getRate(): Int;
	// Get sound channels
	function getChannels(): Int;
    // Set known full file length
    function setSize(size: Int): Void;
	// Get estimated sound length
	function getEtaLength(): Null<Float>;
	// Get loaded sound length
	function getLoadedLength(): Float;
	// Get count of complete samples available
	function samplesAvailable(): Int;
	// Get complete samples as array of channel samples
	function getSamples(): Array<Array<Float>>;
}

// File: generic stream file reader. Subclass it to define used sound decoder and headers
class File implements IFile {
	public var last : Bool;
	var Buffer: flash.utils.ByteArray;
	var bufsize: Int;
	var rate : Int;
	var channels : Int;
	var sndDecoder : Null<Decoder>;
	var chunkSize : Int;
    var align : Int;
	var SoundBuffer: Array<Array<Float>>;
	var dataOff: Int;
	var dataSize : Null<Int>;
	var dataLen : Null<Float>;
	var Readed : Int;

	public function new() {
		Buffer = new flash.utils.ByteArray();
		bufsize = 0;
		rate = 0;
		channels = 0;
		chunkSize = 0;
		align = 0;
		Readed = 0;
		dataOff = 0;
		dataSize = 0;
		last = false;
	}

	// Initialize SoundBuffer after header readed
	private function init() {
		SoundBuffer = new Array<Array<Float>>();
		for(c in 0...channels)
			SoundBuffer.push( new Array<Float>() );
	}

	// Set known full file length
	public function setSize(size: Int): Void {
		dataSize = size;
	}
	
	// Get estimated sound length
	public function getEtaLength(): Null<Float> {
		if (rate==0 || chunkSize==0 || dataSize==0) return null;
		if (dataLen == null && dataSize > 0) 
			dataLen = sndDecoder.decodeLength(Math.floor(dataSize/chunkSize))/rate;
		return dataLen;
	}
	
	// Get loaded sound length
	public function getLoadedLength(): Float {
		if (rate == 0 || chunkSize == 0 || sndDecoder==null)
			return 0.0;
		else
			return (bufsize-dataOff > dataLen) 
					? getEtaLength() 
					: sndDecoder.decodeLength(Math.floor((bufsize-dataOff)/chunkSize)) / rate;
	}
	
	// Read file header
	public function readHeader() {
		return; // on raw file no header
	}

	// Push data from audio stream to decoder
	public function push(bytes: flash.utils.IDataInput, last:Bool): Void {
		if (ready() < 0) return; // Do not operate on error
		var avail = bytes.bytesAvailable;
		if (avail > 65536) avail = 65536;
		trace("Pushing "+avail+" bytes...");
		if (avail == 0) return;
		bytes.readBytes(Buffer, bufsize, avail);
		bufsize += avail;
		if (ready() == 0) readHeader();
	}

	// Require decoder to populate at least <samples> samples from audio stream
	public function populate(samples: Int): Void {
		if (ready() != 1) return;
		var i = Readed;
		var chk = 0;
		while(SoundBuffer[0].length < samples && bufsize - i >= chunkSize) {
			for(j in 0...channels) {
				sndDecoder.decode(Buffer, i, j, SoundBuffer[j], SoundBuffer[j].length);
				i += sndDecoder.sampleSize;
			}
			i += align;
			chk++;
		}
		Readed = i;
		last = (Readed-dataOff+chunkSize >= dataSize);
		if(chk>0) trace("Read "+chk+" chunks, last="+last+"; Readed="+Readed+"; dataSize="+dataSize+"; dataOff="+dataOff+"; chunkSize="+chunkSize);
	}

	// Rewind on input stream to Pos, and return real pos we have seeked, or seeking to
	public function seek(Pos: Float): Float {
		if (ready() != 1) return 0;
		var sample = Math.ceil( Pos * rate ); // Wanted sample number
		var chunk = sndDecoder.seek(Math.ceil( sample / sndDecoder.sampleLength )); // Wanted sample chunk
		var offset = chunk * chunkSize + dataOff; // Offset, where to seek
		if (offset > bufsize) // Round to maximal ready
			offset = Math.floor( (bufsize - dataOff) / chunkSize ) * chunkSize + dataOff;
		Readed = offset; // Seek to required offset
		sample = Math.ceil( (offset - dataOff) / chunkSize ) * sndDecoder.sampleLength; // Get final selected sample
        trace("Sync to Pos "+Pos+"; sample = "+sample+"; offset="+offset+"; res="+(sample/rate));
		return sample / rate;
	}
	
	// Returns is stream ready to operate: header readed (1), not ready (0), error(-1)
	public function ready(): Int {
		return -1;
	}

	// Get sound samplerate is Hz
	public function getRate(): Int {
		return rate;
	}

	// Get sound channels
	public function getChannels(): Int {
		return channels;
	}

	// Get count of complete samples available
	public function samplesAvailable(): Int {
		return SoundBuffer[0].length;
	}

	// Get complete samples as array of channel samples
	public function getSamples(): Array<Array<Float>> {
		 var Ret = SoundBuffer;
		 SoundBuffer = new Array<Array<Float>>();
		 for(j in 0...channels)
			 SoundBuffer.push(new Array<Float>());
		 return Ret;
	}
}
