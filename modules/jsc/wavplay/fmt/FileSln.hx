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

// FileRaw: stream raw file reader. Subclass it to define used sound decoder
class FileSln extends fmt.FileRaw {
	public function new(?srate : Int = 8000) {
		super();
		rate = srate;
		channels = 1;
		chunkSize = 2;
		align = 0;
		sndDecoder = new DecoderPCM(16, 2);
		init();
	}
}
