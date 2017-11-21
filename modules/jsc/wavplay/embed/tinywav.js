// Pass ID of player's <object> tag, and length in second of pre-buffer
function TinyWav(pid, trigger) {
	this.pid = pid;
	this.State = "STOPPED";
	this.initCnt = 0;
	this.player = undefined;
	this.playlist = [];
	this.trigger_buffer = trigger;
	this.doplaylist = false;

	// If one string passed -- 
	// 		Stop any current playback, clear playlist and run play of only file
	// If no argument passed --
	//		Start/resume playback of playlist
	// If list passed --
	//		replace playlist with it, and start playback of it
	this.Play = function(file) {
		var player = this.getPlayer();
		if (!file) {
			this.doplaylist = true;
			if (this.State != "STOPPED" || !this.playlist.length)
				return;
			file = this.playlist[0];
		} else 
		if (typeof file == "object") {
			this.playlist = file;
			this.doplaylist = true;
			if (this.State != "STOPPED" || !this.playlist.length)
				return;
			file = this.playlist[0];
		} else {
			this.doplaylist = false;
		}
		this.Stop();
		player.doPlay(file, this.trigger_buffer);
	}
	// Add file(s) in playlist; does not starts playback
	this.Enqueue = function(file) {
		if (typeof file == "object") {
			this.playlist = this.playlist.concat(file);
		}
		else if (file) {
			if (!this.playlist || !this.playlist.length)
				this.playlist = [file];
			else
				this.playlist[this.playlist.length] = file;
		}
	}
	// Stop playback
	this.Stop = function () {
		var player = this.getPlayer();
		player.doStop();
	}
	// Pause playback
	this.Pause = function () {
		var player = this.getPlayer();
		player.doPause();
	}
	// Continue playback
	this.Resume = function () {
		var player = this.getPlayer();
		player.doResume();
	}
	// Advance to next playlist track
	this.Next = function() {
		var player = this.getPlayer();
		if(this.playlist.length) this.playlist.shift();
		if (!this.playlist.length)
			return;
		file = this.playlist[0];
		player.doStop();
		player.doPlay(file, this.trigger_buffer);
	}
	// ============= END OF API ==========
	// Find player object in page
	this.getPlayer = function() {
		if(this.player!=undefined) return this.player;
		var obj = document.getElementById(this.pid);
		if (!obj) return null;
		if (obj.doPlay) {
			this.player = obj;
			return obj;
		}
		for(i=0; i<obj.childNodes.length; i++) {
			var child = obj.childNodes[i];
			if (child.tagName == "EMBED") {
				this.player = child;
				return child;
			}
		}
	}

	this.SoundState = function (state, position) {
		if (position != undefined) this.SoundPos = position;
		if (this.State == "PLAYING" && state=="STOPPED" && this.doplaylist) {
			window.setTimeout((function(t){ 
				return function(){ t.Next(); };
			})(this), 50);
		}
		this.State = state;
	}
	this.init = function () {
		var player = this.getPlayer();
		this.initCnt++;
		if (!player || !player.attachHandler) {
			if (this.initCnt < 50)
				setTimeout((function(t){ return function(){ return t.init(); } })(this), 100); // Wait for load
		} else {
			player.attachHandler("PLAYER_BUFFERING", "TinyWavSoundState", "BUFFERING");
			player.attachHandler("PLAYER_PLAYING", "TinyWavSoundState", "PLAYING");
			player.attachHandler("PLAYER_STOPPED", "TinyWavSoundState", "STOPPED");
			player.attachHandler("PLAYER_PAUSED", "TinyWavSoundState", "PAUSED");
		}
	}
}
function TinyWavSoundState() { window.TinyWav.SoundState.apply(window.TinyWav, arguments); }
window.TinyWav = new TinyWav('TinyWavBlock', 0.01);
Event.domReady.add(function() {
	var Player = document.createElement("div");
	Player.style.display = "block";
	Player.setAttribute("id", "TinyWavBlock");
	document.body.appendChild(Player);
	var vars = {}; var params = {'scale': 'noscale', 'bgcolor': '#FFFFFF'};
	//swfobject.embedSWF("wavplayer-debug.swf?gui=none", "TinyWavBlock", "600", "300", "10.0.32.18", "embed/expressInstall.swf", vars, params, params);
	swfobject.embedSWF("wavplayer.swf?gui=none", "TinyWavBlock", "1", "1", "10.0.32.18", "embed/expressInstall.swf", vars, params, params);
	window.TinyWav.init();
});

