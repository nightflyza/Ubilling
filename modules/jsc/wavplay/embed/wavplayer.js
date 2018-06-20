function WavPlayer(pid, heartext) {
	this.pid = pid;
	this.hearText = heartext;
	this.SoundLen = 0;
	this.SoundReady = 0;
	this.SoundPos = 0;
	this.Last = undefined;
	this.State = "STOPPED";
	this.Timer = undefined;
	this.stoptext = undefined;
	this.obj = undefined;
	this.initCnt = 0;
	this.ids = new Array();
	this.player = undefined;
	this.getId = function(id) {
		var element = (id in this.ids ? this.ids[id] : (this.ids[id] = document.getElementById(id)));
	}
	this.log = function(msg) {
		var log = this.getId("JSLOG");
		if(log) log.innerHTML += "<br/>"+msg;
	}
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
	this.doPlay = function(obj) {
		this.log("doPlay("+obj.href+")");
		var player = this.getPlayer();
		this.doStop();
		this.obj = obj;
		this.stoptext = this.obj.innerHTML;
		this.obj.onclick = function(){ window.WavPlayer.doStop(); return false; }
		this.SoundPos = 0;
		this.SoundReady = 0;
		this.SoundLen = 0;
		player.doPlay(obj.href);
	}
	this.doStop = function () {
		this.log("doStop()");
		var player = this.getPlayer();
		player.doStop();
	}
	this.getPerc = function (a, b) {
		return ((b==0?0.0:a/b)*100).toFixed(2);
	}
	this.SoundLoad = function(secLoad, secTotal) {
		this.log("SoundLoad("+secLoad+","+secTotal+")");
		this.SoundReady = secLoad;
		this.SoundLen = secTotal;
		this.Inform();
	}
	this.Inform = function () {
		if (this.Last != undefined) {
			var now = new Date();
			var interval = (now.getTime()-this.Last.getTime())/1000;
			this.SoundPos += interval;
			this.Last = now;
		}
		this.log("Inform("+this.State+","+this.SoundPos+")");
		if (this.State=="STOPPED") {
			this.obj.innerHTML = this.stoptext;
			this.obj.onclick = (function(obj){ return function(){ window.WavPlayer.doPlay(obj); return false; } })(this.obj);
			this.Last = undefined;
		} else {
			this.obj.innerHTML = this.SoundPos.toFixed(2)+"/"+this.SoundReady.toFixed(2)+"/"+this.SoundLen.toFixed(2);
		}
	}
	this.SoundState = function (state, position) {
		this.log("SoundState("+this.State+" => "+state+")");
		if (position != undefined) this.SoundPos = position;
		if (this.State != "PLAYING" && state=="PLAYING") {
			this.Last = new Date();
			this.Timer = setInterval((function(t){ return function(){ t.Inform(); } })(this), 256);
		} else
		if (this.State == "PLAYING" && state!="PLAYING") {
			clearInterval(this.Timer);
			this.Timer = undefined;
		}
		this.State = state;
		this.Inform();
	}
	this.init = function () {
		var player = this.getPlayer();
		this.initCnt++;
		if (!player || !player.attachHandler) {
			if (this.initCnt < 50)
				setTimeout((function(t){ return function(){ return t.init(); } })(this), 100); // Wait for load
		} else {
			
			player.attachHandler("PLAYER_LOAD", "WavPlayerSoundLoad");
			player.attachHandler("PLAYER_BUFFERING", "WavPlayerSoundState", "BUFFERING");
			player.attachHandler("PLAYER_PLAYING", "WavPlayerSoundState", "PLAYING");
			player.attachHandler("PLAYER_STOPPED", "WavPlayerSoundState", "STOPPED");
			player.attachHandler("PLAYER_PAUSED", "WavPlayerSoundState", "PAUSED");
			
			//this.Inform();
			var as = document.body.getElementsByTagName("A");
			for(var i = 0; i<as.length; i++) if (as[i].href.match(/[.](al|alaw|au|gsm|raw|sln|ul|ulaw|wav|wav49)$/i)) {
				as[i].onclick = (function(obj){ return function(){ window.WavPlayer.doPlay(obj); return false; } })(as[i]);
				as[i].innerHTML = this.hearText;
			}
			var wavhead = this.getId('wavplayhead');
			if (wavhead) wavhead.innerHTML = "v:"+player.getVersion();
		}
	}
}
function WavPlayerSoundLoad() { window.WavPlayer.SoundLoad.apply(window.WavPlayer, arguments); }
function WavPlayerSoundState() { window.WavPlayer.SoundState.apply(window.WavPlayer, arguments); }
window.WavPlayer = new WavPlayer('WavPlayerBlock', "Прослушать");
Event.domReady.add(function() {
	var Player = document.createElement("div");
	Player.style.display = "block";
	Player.setAttribute("id", "WavPlayerBlock");
	var attachPoint = document.body;
	var attachAnchor = undefined;
	var hs = document.getElementsByTagName('h3');
	if (hs.length == 1) {
		hs = hs[0];
		attachPoint = hs.parentElement ? hs.parentElement : hs.parentNode;
		attachAnchor = hs.nextSibling;
	}
	attachPoint.insertBefore(Player, attachAnchor);
	var vars = {}; var params = {'scale': 'noscale', 'bgcolor': '#FFFFFF'};
	swfobject.embedSWF("/ec/res/wavplayer.swf?gui=full&w=600&h=20", "WavPlayerBlock", "600", "20", "10.0.32.18", "/ec/res/expressInstall.swf", vars, params, params);
	window.WavPlayer.init();
});
