interface IPlayer {
	var volume(get_volume, set_volume): Float;
	var pan(get_pan, set_pan): Float;
	var soundTransform(get_soundTransform, set_soundTransform): flash.media.SoundTransform;
	function play(?path : String, ?trigger_buffer : Float): Void;
	function set_volume(volume: Float) : Float;
	function get_volume(): Float;
	function set_pan(pan: Float): Float;
	function get_pan(): Float;
	function set_soundTransform(st: flash.media.SoundTransform): flash.media.SoundTransform;
	function get_soundTransform(): flash.media.SoundTransform;
	function pause(): Void;
	function resume(): Void;
	function seek(pos: Float): Void;
	function stop(): Void;
}
