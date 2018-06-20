WavPlayer -- flash player for asterisk

* Note: Use Haxe-2 to compile.

    WavPlayer is flash player, designed to play files, recorded for and 
    by Asterisk (www.asterisk.org) or any other telephone system.

    If supports playback of:
    Format | Codecs
    =====================================================================
    .au     G711u, G711a, PCM format, any samplerate/channels
    .wav    G711u, G711a, PCM, GSM 6.10 (MS), IMA ADPCM formats, any samplerate/channels
     .wav49 just alias of .wav, can content any of wav codecs
    .gsm    raw GSM 6.10
    .sln    raw PCM 16bit-signed 8kHz
     .raw   alias of .sln
    .alaw   raw G711a 8kHz mono
     .al    alias of .alaw
    .ulaw   raw G711u 8kHz mono
     .ul    alias of .ulaw
     .mu    alias of .ulaw
     .pcm   alias of .ulaw
    .la     raw G711a 8kHz mono in inverted bit order
    .lu     raw G711u 8kHz mono in inverted bit order
    =====================================================================

Flash interface:
    You can select one of two interfaces:
    1: (minimal) Just one button. 
            To select it, pass gui=mini in agruments, or nothing
        shape of circle = buffering, click to stop
        shape of square = playing, click to pause playback
        shape of triangle = stopped, click to play last file
        shape of two bars = paused. click to continue play
    2: (wide) Control button as above plus position bar for scrolling
            To select it, pass gui=full in arguments
        short ticks are 10 second, long ticks are minutes.
        pass arguments: h=height of player, w=width of player.
            field of control button are square of h*h, rest space used for
            position bar.
    3: (none) no interface at all. transparent dot displayed.
            To select it, pass gui=none in arguments

Flash Interface customize:
    Pass parameters to specify colors:
        bg_color:     (default 0x303030) Color of background
        ready_color:  (default 0xA0A0A0) Color of loaded bar
        cursor_color: (default 0x7FA03F) Color of cursor mark
        button_color: (default 0x808080) Color of play/pause button

        minor_tick_color: (default 0x006600) Color of minor tick score
        major_tick_color: (default 0x000066) Color of major tick score

JS interface:
    doPlay([filename][, buffer]) or play([filename][, buffer])
        start playback of given filename. if filename not given -- play last
        buffer argument says minimum buffer length needed to start playback
    doStop() or stop()
        stop playback of current file
    doPause() or pause()
        pauses playback of current file
    doResume() or resume()
        resume playback of current file after pause. will throw error if not started
    doSeek(pos) or seek()
        seeks playback to position (in seconds)
    setVolume(value) or volume(value)
        set playback volume to specifed value. 1.0 by default
        initial value get from flash parameter "value"
    getVolume() or volume()
        get playback volume
    setPan(value) or pan(value)
        set playback pan (-1.0 is 100% left, 0.0 is center(default), 1.0 is 100% right)
        initial value get from flash parameter "pan"
    getPan(value) or pan()
        get playback pan
    attachHandler(Event, Handler[, User]) -> handlerId
        when Event occurs, Handler will be called, with optionally User info as first argument
    detachHandler(Event, Handler[, User])
        detach all Event handlers, identified by Event/Handler/User triplet
    removeHandler(HandlerId)
        detach event handler, identified by handlerId, returent by previous call to attachHandler

JS callbacks:
    onWavPlayerReady(id)
        fired when wavPlayer ready to be controlled

JS events:
        !!! WARNING !!! 
        Do not do any time-consuming operations in callbacks -- 
            It can cause hangs from browser to whole system.
        To change any DOM elements / innerHTML / ask user --
            fire function in separate thread with setTimeout()
        !!! ACHTUNG !!!

    *(eventName[, User][, Arguments])
        fired on any events. first argument then eventName, next is user-supplied argument, rest is event arguments
    PLAYER_BUFFERING([position])
        fired when player starts buffering of sound.
        optionally, passed current file position (if known)
    PLAYER_LOAD(soundAvailable, soundTotal)
        fired when player loads sound stream.
        soundAvailable = sound length in seconds available to play right now
        soundTotal = total sound length in file, if known
    PLAYER_PLAYING([position])
        fired when player starts playing sound
        start of playback position passed, if position known
    PLAYER_STOPPED([position])
        fired when player stops playing sound
        stopped position passed, if known
    PLAYER_PAUSED([position])
        fired when player paused playing sound
        current pause position passed, if known
    progress(bytesLoaded, bytesTotal)
        fired when player loaded next chunk from file.

See usage example in debug.html and index.html

