document.addEventListener('DOMContentLoaded', () => {
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
    wavesurfer.load(audioElement.src);

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
        console.log('WaveSurfer is ready!');
        document.getElementById('pbxcallrecfile').disabled = false;
    });

    wavesurfer.on('error', function () {
        alert('Error with audio file or playback. Please try again.');
    });
});
