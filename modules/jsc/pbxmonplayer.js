document.addEventListener('DOMContentLoaded', () => {
    const waveformstatus = document.getElementById('waveformstatus');
    waveformstatus.innerHTML = '<img src="skins/ajaxloader.gif" alt="Loading...">';

    const wavesurfer = WaveSurfer.create({
        container: '#waveform',
        barWidth: 4,
        barRadius: 4,
        height: 200,
        backend: 'MediaElement',
        mediaControls: true,
        mediaType: 'audio',
        responsive: true,
        hideScrollbar: false,
        scrollParent: true
    });


    const audioElement = document.getElementById('pbxcallrecfile');

    setTimeout(() => {
        wavesurfer.load(audioElement.src);
        waveformstatus.innerHTML = '';
    }, 1000);


    audioElement.onplay = function () {
        wavesurfer.play();
    };
    audioElement.onpause = function () {
        wavesurfer.pause();
    };
    audioElement.onseeked = function () {
        wavesurfer.seekTo(audioElement.currentTime / audioElement.duration);
    };

    wavesurfer.on('ready', function () {
        document.getElementById('pbxcallrecfile').disabled = false;
    });

    wavesurfer.on('error', function () {
        alert('Error with audio file or playback. Please try again.');
    });
});
