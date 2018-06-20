//
// Code from FOGG project
// http://bazaar.launchpad.net/~arkadini/fogg/trunk/files
// Licensed under GPL
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

package org.xiph.system;

#if flash9
typedef Bytes = haxe.io.BytesData;
#else
typedef Bytes = Array<Int>;
#end
