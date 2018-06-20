/*
 * Copyright 2007 Sun Microsystems, Inc.  All Rights Reserved.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This code is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License version 2 only, as
 * published by the Free Software Foundation.  Sun designates this
 * particular file as subject to the "Classpath" exception as provided
 * by Sun in the LICENSE file that accompanied this code.
 *
 * This code is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
 * version 2 for more details (a copy is included in the LICENSE file that
 * accompanied this code).
 *
 * You should have received a copy of the GNU General Public License version
 * 2 along with this work; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * Please contact Sun Microsystems, Inc., 4150 Network Circle, Santa Clara,
 * CA 95054 USA or visit www.sun.com if you need additional information or
 * have any questions.
 */
package com.sun.media.sound;

/**
 * Lanczos interpolation resampler.
 *
 * @author Karl Helgason
 */
class SoftLanczosResampler implements SoftAbstractResampler {

	var sinc_table : Array<Array<Float>>;
	static inline var sinc_table_fsize : Int = 2000;
	static inline var sinc_table_size : Int = 5;
	static inline var sinc_table_center : Int = Std.int( sinc_table_size / 2 );

	public function new() {
		sinc_table = new Array<Array<Float>>();
		for ( i in 0...sinc_table_fsize ) {
			sinc_table.push(sincTable(sinc_table_size, -i / sinc_table_fsize));
		}
	}

	// Normalized sinc function
	public function sinc(x : Float) : Float {
		return (x == 0.0) ? 1.0 : Math.sin(Math.PI * x) / (Math.PI * x);
	}

	// Generate sinc table
	public static function sincTable(size : Int, offset : Float) : Array<Float> {
		var center : Int = Std.int( size / 2 );
		var w = new Array<Float>();
		for (k in 0...size) {
			var x : Float = (-center + k + offset);
			if (x < -2 || x > 2)
				w.push( 0.0 );
			else if (x == 0)
				w.push( 1.0 );
			else {
				w.push( (2.0 * Math.sin(Math.PI * x)
							 * Math.sin(Math.PI * x / 2.0)
							 / ((Math.PI * x) * (Math.PI * x))) );
			}
		}
		return w;
	}

	public inline function getPadding(): Int // must be at least half of sinc_table_size
	{
		return Std.int(sinc_table_size / 2 + 2);
	}

	public function interpolate(inp : Array<Float>, in_offset : Array<Float>, in_end : Float,
			startpitch : Array<Float>, pitchstep : Float, out : Array<Float>, out_offset : Array<Int>,
			out_end : Int) : Void {
		var pitch : Float = startpitch[0];
		var ix : Float = in_offset[0];
		var ox : Int = out_offset[0];
		var ix_end : Float = in_end;
		var ox_end : Int = out_end;

		if (pitchstep == 0) {
			while (ix < ix_end && ox < ox_end) {
				var iix : Int = Std.int(ix);
				var sinc_table = this.sinc_table[Std.int( ((ix - iix) * sinc_table_fsize) )];
				var xx = iix - sinc_table_center;
				var y : Float = 0.0;
				for (i in 0...sinc_table_size) {
					xx++;
					y += inp[xx] * sinc_table[i];
				}
				out[ox++] = y;
				ix += pitch;
			}
		} else {
			while (ix < ix_end && ox < ox_end) {
				var iix : Int = Std.int(ix);
				var sinc_table = this.sinc_table[Std.int( ((ix - iix) * sinc_table_fsize) )];
				var xx = iix - sinc_table_center;
				var y : Float = 0;
				for (i in 0...sinc_table_size) {
					xx++;
					y += inp[xx] * sinc_table[i];
				}
				out[ox++] = y;

				ix += pitch;
				pitch += pitchstep;
			}
		}
		in_offset[0] = ix;
		out_offset[0] = ox;
		startpitch[0] = pitch;
	}
}
