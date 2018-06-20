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
class FileRaw extends fmt.File {
	// Returns is stream ready to operate: header readed (1), not ready (0), error(-1)
	public override function ready(): Int {
		// For raw file, not defined channels/chunkSize/rate or decoder is error
		if (channels == 0 || chunkSize == 0 || rate == 0 || sndDecoder==null) return -1;
		return 1;
	}
}
