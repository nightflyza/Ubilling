//    $Id: GSMDecoder.java,v 1.3 2006/02/13 17:40:04 pfisterer Exp $	

//    This file is part of the GSM 6.10 audio decoder library for Java
//    Copyright (C) 1998 Steven Pickles (pix@test.at)

//    This library is free software; you can redistribute it and/or
//    modify it under the terms of the GNU Library General Public
//    License as published by the Free Software Foundation; either
//    version 2 of the License, or (at your option) any later version.

//    This library is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//    Library General Public License for more details.

//    You should have received a copy of the GNU Library General Public
//    License along with this library; if not, write to the Free
//    Software Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

// Code get from http://www.tritonus.org/

//  This software is a port of the GSM Library provided by
//  Jutta Degener (jutta@cs.tu-berlin.de) and 
//  Carsten Bormann (cabo@cs.tu-berlin.de), 
//  Technische Universitaet Berlin

package org.tritonus.lowlevel.gsm;

class GSMDecoder
{
	private static inline var GSM_MAGIC = 0x0d;

	private static var FAC : Array<Int> = [ 18431, 20479, 22527, 24575, 
						26623, 28671, 30719, 32767 ];

	private static var QLB : Array<Int> = [ 3277, 11469, 21299, 32767 ];

	// TODO: can be replaced by Short.MIN_VALUE and Short.MAX_VALUE ?
	private static inline var MIN_WORD = -32767 - 1;
	private static inline var MAX_WORD = 32767;


	private var m_dp0 : Array<Int>; // = new int[280];

	private var u : Array<Int>; // = new int[8];
	private var LARpp : Array<Array<Int>>; // = new int[2][8];
	private var m_j : Int;

	private var nrp : Int;
	private var v : Array<Int>; // = new int[9];
	private var msr : Int;


	// hack used for adapting calling conventions
	private var m_abFrame : haxe.io.BytesData;

	// only to reduce memory allocations
	// (formerly allocated once for each frame to decode)
	private var m_LARc : Array<Int>; // = new int[8];
	private var m_Nc : Array<Int>; // = new int[4];
	private var m_Mc : Array<Int>; // = new int[4];
	private var m_bc : Array<Int>; // = new int[4];
	private var m_xmaxc : Array<Int>; // = new int[4];
	private var m_xmc : Array<Int>; // = new int[13 * 4];

	private var m_erp : Array<Int>; // = new int[40];
	private var m_wt : Array<Int>; // = new int[160];

	private var m_xMp : Array<Int>; // = new int[13];

	private var m_result : Array<Int>; // = new int[2];

	private	var m_LARp : Array<Int>; // = new int[8];

	private var m_s : Array<Int>; // = new int[160];

	private var frame_index : Bool;
	private var frame_chain : Int;

	public function new()
	{
		nrp = 40;
		m_dp0 = new Array<Int>(); // 280
		u = new Array<Int>(); // 8
		LARpp = new Array<Array<Int>>();
		LARpp.push( new Array<Int>() );
		LARpp.push( new Array<Int>() );
    	v = new Array<Int>(); // = new int[9];
    	m_LARc = new Array<Int>(); // = new int[8];
    	m_Nc = new Array<Int>(); // = new int[4];
    	m_Mc = new Array<Int>(); // = new int[4];
    	m_bc = new Array<Int>(); // = new int[4];
    	m_xmaxc = new Array<Int>(); // = new int[4];
    	m_xmc = new Array<Int>(); // = new int[13 * 4];
    	m_erp = new Array<Int>(); // = new int[40];
    	m_wt = new Array<Int>(); // = new int[160];
    	m_xMp = new Array<Int>(); // = new int[13];
    	m_result = new Array<Int>(); // = new int[2];
    	m_LARp = new Array<Int>(); // = new int[8];
    	m_s = new Array<Int>(); // = new int[160];
		frame_index = false;
	}




	/*
	  This is how the method call should look like.
	  @param abFrame the array that contains the GSM frame (encoded data)
	  @param nFrameStart that number of the byte that should be used as starting point
	  for the GSM frame inside abFrame
	  @param abBuffer the array where the decoded data should be written to. The data are
	  written as 16 bit linear samples (actually using the lowest 13 bit), either big
	  or little endian, depending on the value of bBigEndian.
	  @param nBufferStart the byte number where the data should be written.
	  @param bBigEndian whether the decoded data should be written big endian or
	  little endian.
	 */
	
	// For WavPlayer changed output buffer from binary to float and
	// added support for ms-gsm packing
	public function decode(abFrame : haxe.io.BytesData, nFrameStart : Int,
			   abBuffer : Array<Float>, nBufferStart : Int, ?msgsm: Bool = false)
	{
		var anDecodedData;
		anDecodedData = msgsm?decodeMsFrame(abFrame, nFrameStart)
							 :decodeStdFrame(abFrame, nFrameStart);
		for (i in 0...160)
			abBuffer[nBufferStart+i] = anDecodedData[i] / 32767.0;

		if (msgsm) { // In MS-GSM mode, there two frames in one call
			anDecodedData = decodeMsFrame(abFrame, nFrameStart+33);
	        for (i in 0...160)
	            abBuffer[nBufferStart+160+i] = anDecodedData[i] / 32767.0;
		}
	}

	private function decodeMsFrame(c : haxe.io.BytesData, offs: Int)
	{
		var sr = 0;
		var i = offs;

		frame_index = !frame_index;
		if (frame_index) {

			sr = c[i++];
			m_LARc[0] = sr & 0x3f;  sr >>= 6;
			sr |= c[i++] << 2;
			m_LARc[1] = sr & 0x3f;  sr >>= 6;
			sr |= c[i++] << 4;
			m_LARc[2] = sr & 0x1f;  sr >>= 5;
			m_LARc[3] = sr & 0x1f;  sr >>= 5;
			sr |= c[i++] << 2;
			m_LARc[4] = sr & 0xf;  sr >>= 4;
			m_LARc[5] = sr & 0xf;  sr >>= 4;
			sr |= c[i++] << 2;			/* 5 */
			m_LARc[6] = sr & 0x7;  sr >>= 3;
			m_LARc[7] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 4;
			m_Nc[0] = sr & 0x7f;  sr >>= 7;
			m_bc[0] = sr & 0x3;  sr >>= 2;
			m_Mc[0] = sr & 0x3;  sr >>= 2;
			sr |= c[i++] << 1;
			m_xmaxc[0] = sr & 0x3f;  sr >>= 6;
			m_xmc[0] = sr & 0x7;  sr >>= 3;
			sr = c[i++];
			m_xmc[1] = sr & 0x7;  sr >>= 3;
			m_xmc[2] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 2;
			m_xmc[3] = sr & 0x7;  sr >>= 3;
			m_xmc[4] = sr & 0x7;  sr >>= 3;
			m_xmc[5] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 1;			/* 10 */
			m_xmc[6] = sr & 0x7;  sr >>= 3;
			m_xmc[7] = sr & 0x7;  sr >>= 3;
			m_xmc[8] = sr & 0x7;  sr >>= 3;
			sr = c[i++];
			m_xmc[9] = sr & 0x7;  sr >>= 3;
			m_xmc[10] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 2;
			m_xmc[11] = sr & 0x7;  sr >>= 3;
			m_xmc[12] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 4;
			m_Nc[1] = sr & 0x7f;  sr >>= 7;
			m_bc[1] = sr & 0x3;  sr >>= 2;
			m_Mc[1] = sr & 0x3;  sr >>= 2;
			sr |= c[i++] << 1;
			m_xmaxc[1] = sr & 0x3f;  sr >>= 6;
			m_xmc[13] = sr & 0x7;  sr >>= 3;
			sr = c[i++];				/* 15 */
			m_xmc[14] = sr & 0x7;  sr >>= 3;
			m_xmc[15] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 2;
			m_xmc[16] = sr & 0x7;  sr >>= 3;
			m_xmc[17] = sr & 0x7;  sr >>= 3;
			m_xmc[18] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 1;
			m_xmc[19] = sr & 0x7;  sr >>= 3;
			m_xmc[20] = sr & 0x7;  sr >>= 3;
			m_xmc[21] = sr & 0x7;  sr >>= 3;
			sr = c[i++];
			m_xmc[22] = sr & 0x7;  sr >>= 3;
			m_xmc[23] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 2;
			m_xmc[24] = sr & 0x7;  sr >>= 3;
			m_xmc[25] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 4;			/* 20 */
			m_Nc[2] = sr & 0x7f;  sr >>= 7;
			m_bc[2] = sr & 0x3;  sr >>= 2;
			m_Mc[2] = sr & 0x3;  sr >>= 2;
			sr |= c[i++] << 1;
			m_xmaxc[2] = sr & 0x3f;  sr >>= 6;
			m_xmc[26] = sr & 0x7;  sr >>= 3;
			sr = c[i++];
			m_xmc[27] = sr & 0x7;  sr >>= 3;
			m_xmc[28] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 2;
			m_xmc[29] = sr & 0x7;  sr >>= 3;
			m_xmc[30] = sr & 0x7;  sr >>= 3;
			m_xmc[31] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 1;
			m_xmc[32] = sr & 0x7;  sr >>= 3;
			m_xmc[33] = sr & 0x7;  sr >>= 3;
			m_xmc[34] = sr & 0x7;  sr >>= 3;
			sr = c[i++];				/* 25 */
			m_xmc[35] = sr & 0x7;  sr >>= 3;
			m_xmc[36] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 2;
			m_xmc[37] = sr & 0x7;  sr >>= 3;
			m_xmc[38] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 4;
			m_Nc[3] = sr & 0x7f;  sr >>= 7;
			m_bc[3] = sr & 0x3;  sr >>= 2;
			m_Mc[3] = sr & 0x3;  sr >>= 2;
			sr |= c[i++] << 1;
			m_xmaxc[3] = sr & 0x3f;  sr >>= 6;
			m_xmc[39] = sr & 0x7;  sr >>= 3;
			sr = c[i++];
			m_xmc[40] = sr & 0x7;  sr >>= 3;
			m_xmc[41] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 2;			/* 30 */
			m_xmc[42] = sr & 0x7;  sr >>= 3;
			m_xmc[43] = sr & 0x7;  sr >>= 3;
			m_xmc[44] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 1;
			m_xmc[45] = sr & 0x7;  sr >>= 3;
			m_xmc[46] = sr & 0x7;  sr >>= 3;
			m_xmc[47] = sr & 0x7;  sr >>= 3;
			sr = c[i++];
			m_xmc[48] = sr & 0x7;  sr >>= 3;
			m_xmc[49] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 2;
			m_xmc[50] = sr & 0x7;  sr >>= 3;
			m_xmc[51] = sr & 0x7;  sr >>= 3;

			frame_chain = sr & 0xf;
		}
		else {
			sr = frame_chain;
			sr |= c[i++] << 4;			/* 1 */
			m_LARc[0] = sr & 0x3f;  sr >>= 6;
			m_LARc[1] = sr & 0x3f;  sr >>= 6;
			sr = c[i++];
			m_LARc[2] = sr & 0x1f;  sr >>= 5;
			sr |= c[i++] << 3;
			m_LARc[3] = sr & 0x1f;  sr >>= 5;
			m_LARc[4] = sr & 0xf;  sr >>= 4;
			sr |= c[i++] << 2;
			m_LARc[5] = sr & 0xf;  sr >>= 4;
			m_LARc[6] = sr & 0x7;  sr >>= 3;
			m_LARc[7] = sr & 0x7;  sr >>= 3;
			sr = c[i++];				/* 5 */
			m_Nc[0] = sr & 0x7f;  sr >>= 7;
			sr |= c[i++] << 1;
			m_bc[0] = sr & 0x3;  sr >>= 2;
			m_Mc[0] = sr & 0x3;  sr >>= 2;
			sr |= c[i++] << 5;
			m_xmaxc[0] = sr & 0x3f;  sr >>= 6;
			m_xmc[0] = sr & 0x7;  sr >>= 3;
			m_xmc[1] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 1;
			m_xmc[2] = sr & 0x7;  sr >>= 3;
			m_xmc[3] = sr & 0x7;  sr >>= 3;
			m_xmc[4] = sr & 0x7;  sr >>= 3;
			sr = c[i++];
			m_xmc[5] = sr & 0x7;  sr >>= 3;
			m_xmc[6] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 2;			/* 10 */
			m_xmc[7] = sr & 0x7;  sr >>= 3;
			m_xmc[8] = sr & 0x7;  sr >>= 3;
			m_xmc[9] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 1;
			m_xmc[10] = sr & 0x7;  sr >>= 3;
			m_xmc[11] = sr & 0x7;  sr >>= 3;
			m_xmc[12] = sr & 0x7;  sr >>= 3;
			sr = c[i++];
			m_Nc[1] = sr & 0x7f;  sr >>= 7;
			sr |= c[i++] << 1;
			m_bc[1] = sr & 0x3;  sr >>= 2;
			m_Mc[1] = sr & 0x3;  sr >>= 2;
			sr |= c[i++] << 5;
			m_xmaxc[1] = sr & 0x3f;  sr >>= 6;
			m_xmc[13] = sr & 0x7;  sr >>= 3;
			m_xmc[14] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 1;			/* 15 */
			m_xmc[15] = sr & 0x7;  sr >>= 3;
			m_xmc[16] = sr & 0x7;  sr >>= 3;
			m_xmc[17] = sr & 0x7;  sr >>= 3;
			sr = c[i++];
			m_xmc[18] = sr & 0x7;  sr >>= 3;
			m_xmc[19] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 2;
			m_xmc[20] = sr & 0x7;  sr >>= 3;
			m_xmc[21] = sr & 0x7;  sr >>= 3;
			m_xmc[22] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 1;
			m_xmc[23] = sr & 0x7;  sr >>= 3;
			m_xmc[24] = sr & 0x7;  sr >>= 3;
			m_xmc[25] = sr & 0x7;  sr >>= 3;
			sr = c[i++];
			m_Nc[2] = sr & 0x7f;  sr >>= 7;
			sr |= c[i++] << 1;			/* 20 */
			m_bc[2] = sr & 0x3;  sr >>= 2;
			m_Mc[2] = sr & 0x3;  sr >>= 2;
			sr |= c[i++] << 5;
			m_xmaxc[2] = sr & 0x3f;  sr >>= 6;
			m_xmc[26] = sr & 0x7;  sr >>= 3;
			m_xmc[27] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 1;	
			m_xmc[28] = sr & 0x7;  sr >>= 3;
			m_xmc[29] = sr & 0x7;  sr >>= 3;
			m_xmc[30] = sr & 0x7;  sr >>= 3;
			sr = c[i++];
			m_xmc[31] = sr & 0x7;  sr >>= 3;
			m_xmc[32] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 2;
			m_xmc[33] = sr & 0x7;  sr >>= 3;
			m_xmc[34] = sr & 0x7;  sr >>= 3;
			m_xmc[35] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 1;			/* 25 */
			m_xmc[36] = sr & 0x7;  sr >>= 3;
			m_xmc[37] = sr & 0x7;  sr >>= 3;
			m_xmc[38] = sr & 0x7;  sr >>= 3;
			sr = c[i++];
			m_Nc[3] = sr & 0x7f;  sr >>= 7;
			sr |= c[i++] << 1;		
			m_bc[3] = sr & 0x3;  sr >>= 2;
			m_Mc[3] = sr & 0x3;  sr >>= 2;
			sr |= c[i++] << 5;
			m_xmaxc[3] = sr & 0x3f;  sr >>= 6;
			m_xmc[39] = sr & 0x7;  sr >>= 3;
			m_xmc[40] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 1;
			m_xmc[41] = sr & 0x7;  sr >>= 3;
			m_xmc[42] = sr & 0x7;  sr >>= 3;
			m_xmc[43] = sr & 0x7;  sr >>= 3;
			sr = c[i++];				/* 30 */
			m_xmc[44] = sr & 0x7;  sr >>= 3;
			m_xmc[45] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 2;
			m_xmc[46] = sr & 0x7;  sr >>= 3;
			m_xmc[47] = sr & 0x7;  sr >>= 3;
			m_xmc[48] = sr & 0x7;  sr >>= 3;
			sr |= c[i++] << 1;
			m_xmc[49] = sr & 0x7;  sr >>= 3;
			m_xmc[50] = sr & 0x7;  sr >>= 3;
			m_xmc[51] = sr & 0x7;  sr >>= 3;
		}

		return decoder(m_LARc, m_Nc, m_bc, m_Mc, m_xmaxc, m_xmc);
	}

	private function decodeStdFrame(c : haxe.io.BytesData, offs: Int)
	{
		var i=offs;
    
		if (((c[i]>>4) & 0xf) != GSM_MAGIC)
		{
			throw "InvalidGSMFrameException: bad magic";
		}

		m_LARc[0]  = ((c[i++] & 0xF) << 2);           /* 1 */
		m_LARc[0] |= ((c[i] >> 6) & 0x3);
		m_LARc[1]  = (c[i++] & 0x3F);
		m_LARc[2]  = ((c[i] >> 3) & 0x1F);
		m_LARc[3]  = ((c[i++] & 0x7) << 2);
		m_LARc[3] |= ((c[i] >> 6) & 0x3);
		m_LARc[4]  = ((c[i] >> 2) & 0xF);
		m_LARc[5]  = ((c[i++] & 0x3) << 2);
		m_LARc[5] |= ((c[i] >> 6) & 0x3);
		m_LARc[6]  = ((c[i] >> 3) & 0x7);
		m_LARc[7]  = (c[i++] & 0x7);
		m_Nc[0]  = ((c[i] >> 1) & 0x7F);
		m_bc[0]  = ((c[i++] & 0x1) << 1);
		m_bc[0] |= ((c[i] >> 7) & 0x1);
		m_Mc[0]  = ((c[i] >> 5) & 0x3);
		m_xmaxc[0]  = ((c[i++] & 0x1F) << 1);
		m_xmaxc[0] |= ((c[i] >> 7) & 0x1);
		m_xmc[0]  = ((c[i] >> 4) & 0x7);
		m_xmc[1]  = ((c[i] >> 1) & 0x7);
		m_xmc[2]  = ((c[i++] & 0x1) << 2);
		m_xmc[2] |= ((c[i] >> 6) & 0x3);
		m_xmc[3]  = ((c[i] >> 3) & 0x7);
		m_xmc[4]  = (c[i++] & 0x7);
		m_xmc[5]  = ((c[i] >> 5) & 0x7);
		m_xmc[6]  = ((c[i] >> 2) & 0x7);
		m_xmc[7]  = ((c[i++] & 0x3) << 1);            /* 10 */
		m_xmc[7] |= ((c[i] >> 7) & 0x1);
		m_xmc[8]  = ((c[i] >> 4) & 0x7);
		m_xmc[9]  = ((c[i] >> 1) & 0x7);
		m_xmc[10]  = ((c[i++] & 0x1) << 2);
		m_xmc[10] |= ((c[i] >> 6) & 0x3);
		m_xmc[11]  = ((c[i] >> 3) & 0x7);
		m_xmc[12]  = (c[i++] & 0x7);
		m_Nc[1]  = ((c[i] >> 1) & 0x7F);
		m_bc[1]  = ((c[i++] & 0x1) << 1);
		m_bc[1] |= ((c[i] >> 7) & 0x1);
		m_Mc[1]  = ((c[i] >> 5) & 0x3);
		m_xmaxc[1]  = ((c[i++] & 0x1F) << 1);
		m_xmaxc[1] |= ((c[i] >> 7) & 0x1);
		m_xmc[13]  = ((c[i] >> 4) & 0x7);
		m_xmc[14]  = ((c[i] >> 1) & 0x7);
		m_xmc[15]  = ((c[i++] & 0x1) << 2);
		m_xmc[15] |= ((c[i] >> 6) & 0x3);
		m_xmc[16]  = ((c[i] >> 3) & 0x7);
		m_xmc[17]  = (c[i++] & 0x7);
		m_xmc[18]  = ((c[i] >> 5) & 0x7);
		m_xmc[19]  = ((c[i] >> 2) & 0x7);
		m_xmc[20]  = ((c[i++] & 0x3) << 1);
		m_xmc[20] |= ((c[i] >> 7) & 0x1);
		m_xmc[21]  = ((c[i] >> 4) & 0x7);
		m_xmc[22]  = ((c[i] >> 1) & 0x7);
		m_xmc[23]  = ((c[i++] & 0x1) << 2);
		m_xmc[23] |= ((c[i] >> 6) & 0x3);
		m_xmc[24]  = ((c[i] >> 3) & 0x7);
		m_xmc[25]  = (c[i++] & 0x7);
		m_Nc[2]  = ((c[i] >> 1) & 0x7F);
		m_bc[2]  = ((c[i++] & 0x1) << 1);             /* 20 */
		m_bc[2] |= ((c[i] >> 7) & 0x1);
		m_Mc[2]  = ((c[i] >> 5) & 0x3);
		m_xmaxc[2]  = ((c[i++] & 0x1F) << 1);
		m_xmaxc[2] |= ((c[i] >> 7) & 0x1);
		m_xmc[26]  = ((c[i] >> 4) & 0x7);
		m_xmc[27]  = ((c[i] >> 1) & 0x7);
		m_xmc[28]  = ((c[i++] & 0x1) << 2);
		m_xmc[28] |= ((c[i] >> 6) & 0x3);
		m_xmc[29]  = ((c[i] >> 3) & 0x7);
		m_xmc[30]  = (c[i++] & 0x7);
		m_xmc[31]  = ((c[i] >> 5) & 0x7);
		m_xmc[32]  = ((c[i] >> 2) & 0x7);
		m_xmc[33]  = ((c[i++] & 0x3) << 1);
		m_xmc[33] |= ((c[i] >> 7) & 0x1);
		m_xmc[34]  = ((c[i] >> 4) & 0x7);
		m_xmc[35]  = ((c[i] >> 1) & 0x7);
		m_xmc[36]  = ((c[i++] & 0x1) << 2);
		m_xmc[36] |= ((c[i] >> 6) & 0x3);
		m_xmc[37]  = ((c[i] >> 3) & 0x7);
		m_xmc[38]  = (c[i++] & 0x7);
		m_Nc[3]  = ((c[i] >> 1) & 0x7F);
		m_bc[3]  = ((c[i++] & 0x1) << 1);
		m_bc[3] |= ((c[i] >> 7) & 0x1);
		m_Mc[3]  = ((c[i] >> 5) & 0x3);
		m_xmaxc[3]  = ((c[i++] & 0x1F) << 1);
		m_xmaxc[3] |= ((c[i] >> 7) & 0x1);
		m_xmc[39]  = ((c[i] >> 4) & 0x7);
		m_xmc[40]  = ((c[i] >> 1) & 0x7);
		m_xmc[41]  = ((c[i++] & 0x1) << 2);
		m_xmc[41] |= ((c[i] >> 6) & 0x3);
		m_xmc[42]  = ((c[i] >> 3) & 0x7);
		m_xmc[43]  = (c[i++] & 0x7);                  /* 30  */
		m_xmc[44]  = ((c[i] >> 5) & 0x7);
		m_xmc[45]  = ((c[i] >> 2) & 0x7);
		m_xmc[46]  = ((c[i++] & 0x3) << 1);
		m_xmc[46] |= ((c[i] >> 7) & 0x1);
		m_xmc[47]  = ((c[i] >> 4) & 0x7);
		m_xmc[48]  = ((c[i] >> 1) & 0x7);
		m_xmc[49]  = ((c[i++] & 0x1) << 2);
		m_xmc[49] |= ((c[i] >> 6) & 0x3);
		m_xmc[50]  = ((c[i] >> 3) & 0x7);
		m_xmc[51]  = (c[i] & 0x7);                    /* 33 */
   
		return decoder(m_LARc, m_Nc, m_bc, m_Mc, m_xmaxc, m_xmc);
	}



	private function decoder(LARcr: Array<Int>, 
				     Ncr: Array<Int>, 
				     bcr: Array<Int>, 
				     Mcr: Array<Int>, 
				     xmaxcr: Array<Int>, 
				     xMcr: Array<Int>): Array<Int>
	{
		var	j, k;

		for (j in 0...4)
		{
			// find out what is done with xMcr
			RPEDecoding(xmaxcr[j], Mcr[j], xMcr, j * 13, m_erp);

			longTermSynthesisFiltering(Ncr[j], bcr[j], m_erp, m_dp0);
      
			for (k in 0...40)
			{
				m_wt[j * 40 + k] = m_dp0[120 + k];
			}

		}

		var s = shortTermSynthesisFilter(LARcr, m_wt);

		postprocessing(s);

		return s;
	}



	private function RPEDecoding(xmaxcr: Int,
				       Mcr: Int,
				       xMcr: Array<Int>,
				       xMcrOffset: Int,
				       erp: Array<Int>)
	{
		var expAndMant: Array<Int> = new Array<Int>();

		expAndMant = xmaxcToExpAndMant(xmaxcr);

		APCMInverseQuantization(xMcr, xMcrOffset, expAndMant[0], expAndMant[1], m_xMp);

		RPE_grid_positioning( Mcr, m_xMp, erp);
	}



	private function xmaxcToExpAndMant(xmaxc: Int): Array<Int>
	{
		var exp, mant;

		exp = 0;
		if (xmaxc>15)
		{
			exp = ((xmaxc>>3)-1);
		}
		mant=(xmaxc-(exp<<3));

		if (mant==0)
		{
			exp = -4;
			mant = 7;
		}
		else
		{
			while (mant <= 7)
			{
				mant = (mant << 1 | 1);
				exp--;
			}
			mant -= 8;
		}

		return [exp, mant];
	}



	private function APCMInverseQuantization(xMc: Array<Int>,
						   xMcOffset: Int,
						   exp: Int,
						   mant: Int,
						   xMp: Array<Int>)
	{
		var i,p;
		var temp, temp1, temp2, temp3;

		temp1 = FAC[mant];
		temp2 = sub(6,exp);
		temp3 = asl(1,sub(temp2,1));

		p = 0;

		for (i in 0...13)
		{
			temp = ((xMc[xMcOffset++] << 1) - 7);
			temp = (temp<<12);//&0xffff;
			temp = mult_r(temp1, temp);
			temp = add(temp, temp3);
			xMp[p++] = asr(temp, temp2);
		}
	}



	private static inline function saturate(x: Int): Int
	{
		return (x < MIN_WORD ? MIN_WORD : (x > MAX_WORD ? MAX_WORD: x));
	}



	private static inline function sub(a: Int, b: Int): Int
	{
		var diff = a - b;
		return saturate(diff);
	}



	private static inline function add(a: Int, b: Int): Int
	{
		var sum = a + b;
		return saturate(sum);
	}



	private static inline function asl(a: Int, n: Int): Int
	{
		if (n>= 16) return 0; else
		if (n<= -16) return (a<0?-1:0); else
		if (n<0) return (a>>-n); else
		return (a << n);
	}



	private static inline function asr(a: Int, n: Int): Int
	{
		if (n>=16) return (a<0?-1:0); else
		if (n<=-16) return 0; else
		if (n<0) return (a<<-n); else
		return (a>>n);
	}



	private static inline function mult_r(a: Int, b: Int): Int
	{
		if (b == MIN_WORD && a == MIN_WORD) 
			return MAX_WORD;
		else
		{
			var prod: Int = a * b + 16384;
			return saturate(prod>>15);//&0xffff;
		}
	}



	private function longTermSynthesisFiltering(Ncr: Int,
						      bcr: Int,
						      erp: Array<Int>,
						      dp0: Array<Int>)
	{
		var brp, drpp, Nr;

		Nr = Ncr < 40 || Ncr > 120 ? nrp : Ncr;
		nrp = Nr;

		brp = QLB[bcr];

		for (k in 0...40)
		{
			drpp = mult_r(brp,dp0[120 + (k - Nr)]);
			dp0[120 + k] = add(erp[k], drpp);
		}

		for (k in 0...119)
		{
			dp0[k] = dp0[40 + k];
		}
	}



	private function shortTermSynthesisFilter(LARcr: Array<Int>,
						     wt: Array<Int>): Array<Int>
	{
		var LARpp_j = LARpp[m_j];
		var LARpp_j_1 = LARpp[m_j^=1];

		decodingOfTheCodedLogAreaRatios(LARcr,LARpp_j);

		Coefficients_0_12(LARpp_j_1,LARpp_j, m_LARp);
		LARp_to_rp(m_LARp);
		shortTermSynthesisFiltering(m_LARp, 13, wt, m_s, 0);

		Coefficients_13_26( LARpp_j_1, LARpp_j, m_LARp);
		LARp_to_rp(m_LARp);
		shortTermSynthesisFiltering( m_LARp, 14, wt, m_s, 13);

		Coefficients_27_39( LARpp_j_1, LARpp_j, m_LARp);
		LARp_to_rp( m_LARp );
		shortTermSynthesisFiltering( m_LARp, 13, wt, m_s, 27 );

		Coefficients_40_159( LARpp_j, m_LARp );
		LARp_to_rp( m_LARp );
		shortTermSynthesisFiltering( m_LARp, 120, wt, m_s, 40);

		return m_s;
	}



	public static function decodingOfTheCodedLogAreaRatios(LARc: Array<Int>,
								 LARpp: Array<Int>)
	{
		var temp1;

		// STEP(      0,  -32,  13107 );

		temp1 = (add(LARc[0],-32)<<10);
		//temp1 = (sub(temp1, 0));
		temp1 = (mult_r(13107,temp1));
		LARpp[0] = (add(temp1, temp1));

		//         STEP(      0,  -32,  13107 );

		temp1 = (add(LARc[1],-32)<<10);
		//temp1 = (sub(temp1, 0));
		temp1 = (mult_r(13107,temp1));
		LARpp[1] = (add(temp1, temp1));
    
		//         STEP(   2048,  -16,  13107 );

		temp1 = (add(LARc[2],-16)<<10);
		temp1 = (sub(temp1, 4096));
		temp1 = (mult_r(13107,temp1));
		LARpp[2] = (add(temp1, temp1));

		//         STEP(  -2560,  -16,  13107 );
    
		temp1 = (add(LARc[3],(-16))<<10);
		temp1 = (sub(temp1, -5120));
		temp1 = (mult_r(13107,temp1));
		LARpp[3] = (add(temp1, temp1));

		//         STEP(     94,   -8,  19223 );

		temp1 = (add(LARc[4],-8)<<10);
		temp1 = (sub(temp1, 188));
		temp1 = (mult_r(19223,temp1));
		LARpp[4] = (add(temp1, temp1));

		//         STEP(  -1792,   -8,  17476 );

		temp1 = (add(LARc[5],(-8))<<10);
		temp1 = (sub(temp1, -3584));
		temp1 = (mult_r(17476,temp1));
		LARpp[5] = (add(temp1, temp1));

		//         STEP(   -341,   -4,  31454 );

		temp1 = (add(LARc[6],(-4))<<10);
		temp1 = (sub(temp1, -682));
		temp1 = (mult_r(31454,temp1));
		LARpp[6] = (add(temp1, temp1));

		//         STEP(  -1144,   -4,  29708 );
    
		temp1 = (add(LARc[7],-4)<<10);
		temp1 = (sub(temp1, -2288));
		temp1 = (mult_r(29708,temp1));
		LARpp[7] = (add(temp1, temp1));

	}



	private static function Coefficients_0_12(LARpp_j_1: Array<Int>,
						    LARpp_j: Array<Int>,
						    LARp: Array<Int>)
	{
		for(i in 0...8)
		{
			LARp[i] = add((LARpp_j_1[i]>>2),(LARpp_j[i]>>2));
			LARp[i] = add(LARp[i],(LARpp_j_1[i]>>1));
		}
	}



	private static function Coefficients_13_26(LARpp_j_1: Array<Int>,
						     LARpp_j: Array<Int>,
						     LARp: Array<Int>)
	{
		for(i in 0...8)
		{
			LARp[i] = add((LARpp_j_1[i]>>1),(LARpp_j[i]>>1));
		}
	}



	private static function Coefficients_27_39(LARpp_j_1: Array<Int>,
						     LARpp_j: Array<Int>,
						     LARp: Array<Int>)
	{
		for(i in 0...8)
		{
			LARp[i] = add((LARpp_j_1[i]>>2),(LARpp_j[i]>>2));
			LARp[i] = add(LARp[i],(LARpp_j[i]>>1));
		}
	}


  
	private static function Coefficients_40_159(LARpp_j: Array<Int>,
						      LARp: Array<Int>)
	{
		for(i in 0...8)
		{
			LARp[i] = LARpp_j[i];
		}
	}



	private static function LARp_to_rp(LARp: Array<Int>)
	{

		var temp;

		for(i in 0...8)
		{
			if(LARp[i] < 0)
			{
				temp = ((LARp[i]==MIN_WORD)?MAX_WORD:-LARp[i]);
				LARp[i] = (- ((temp < 11059) ? temp << 1
					      : ((temp < 20070) ? temp + 11059
						 : add((temp>>2),26112))));
			}
			else
			{
				temp = LARp[i];
				LARp[i] = ((temp<11059)?temp<<1
					   : ((temp<20070)?temp+11059
					      :add((temp>>2),26112)));
			}
		}
	}



	//      shortTermSynthesisFiltering(LARp,13,wt,s,0);
	private function shortTermSynthesisFiltering(rrp: Array<Int>,
						       k: Int,
						       wt: Array<Int>,
						       sr: Array<Int>,
						       off: Int)
	{
		var sri, tmp1, tmp2;
		var woff = off;
		var soff = off;

		while (k-- > 0)
		{
			sri = wt[woff++];
			for (ri in 0...8)
			{
				var i = 7-ri;
				tmp1 = rrp[i];
				tmp2 = v[i];
				tmp2 = ((tmp1 == MIN_WORD && tmp2 == MIN_WORD
					 ? MAX_WORD
					 : saturate((tmp1 * tmp2 + 16384) >> 15)));
				sri = sub(sri,tmp2);

				tmp1 = ((tmp1 == MIN_WORD && sri == MIN_WORD
					 ? MAX_WORD
					 : saturate( (tmp1 * sri + 16384) >> 15)));
				v[i + 1] = add(v[i], tmp1);
			}
			sr[soff++] = v[0] = sri;
		}
	}



	private function postprocessing(s: Array<Int>)
	{
		var	tmp;
		for(soff in 0...160)
		{
			tmp = mult_r(msr, (28180));
			msr = add(s[soff], tmp);
			//s[soff]=(add(msr,msr) & 0xfff8);
			s[soff] = saturate(add(msr, msr) & ~0x7);
		}
	}



	private static function RPE_grid_positioning(Mc: Int,
						       xMp: Array<Int>,
						       ep: Array<Int>)
	{
		var i = 13;
    
		var epo = 0;
		var po = 0;

		if (Mc>=0 && Mc<4) { // datacompboy: not sure is that always true
			if (Mc>=3) ep[epo++] = 0;
			if (Mc>=2) ep[epo++] = 0;
			if (Mc>=1) ep[epo++] = 0;
			ep[epo++] = xMp[po++];
			i--;
		};
    
		do
		{
			ep[epo++] = 0;
			ep[epo++] = 0;
			ep[epo++] = xMp[po++];
		}
		while (--i>0);

		while (++Mc < 4)
		{
			ep[epo++] = 0;
		}
	}

}



/*** GSMDecoder.java ***/



















