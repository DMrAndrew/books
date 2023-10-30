class AudioPlayer {
    isMobile = null;
    container = null;
    playlist = null;
    prevBtn = null;
    nextBtn = null;
    playBtn = null;
    prev30secBtn = null;
    next30secBtn = null;
    speedBtn = null;
    playId = null;
    howlPlayer = null;
    progress = null;
    rateNode = null;
    volumeInput = null;
    seekInput = null;
    songsLength = null;
    songIndex = null;

    playClass = "audioplayer--plays";
    played = false;
    rate = +1;

    constructor(el) {
        this.isMobile = "ontouchstart" in document.documentElement
        this.container = el
        this.playlist = JSON.parse(el.dataset.sounds)
        this.songsLength = this.playlist.length
        this.songIndex = 0
        this.prevBtn = el.querySelector('.audioplayer__prev')
        this.nextBtn = el.querySelector('.audioplayer__next')
        this.playBtn = el.querySelector('.audioplayer__playpause')
        this.prev30secBtn = el.querySelector('.audioplayer__prev-30')
        this.next30secBtn = el.querySelector('.audioplayer__next-30')
        this.progress = el.querySelector('.audioplayer__progress')
        this.speedBtn = el.querySelector('.audioplayer__speed')
        this.rateNode = el.querySelector('.audioplayer__rate-indicator')
        this.rateBtns = el.querySelectorAll('.audioplayer__rate-btn')
        this.volumeInput = el.querySelector('.audioplayer__volume-input')
        this.seekInput = el.querySelector('.audioplayer__seek')
        this.howlPlayer = this.playlist.map((el, index) => {
            return new Howl({
                src: el,
                onplay: () => this.step(),
                onend: () => this.handleSongEnd(),
                onrate: () => this.handleRateChange(),
                volume: this.volumeInput.value,
            });
        });
        Howler.autoUnlock = false;
        this.initAudioPlayerEvents();
        this.handleRateChange();
    }

    initAudioPlayerEvents() {
        this.prevBtn.onclick = () => this.prevSong();
        this.nextBtn.onclick = () => this.nextSong();
        this.playBtn.onclick = () => this.playPause();
        this.prev30secBtn.onclick = () => this.prev30sec();
        this.next30secBtn.onclick = () => this.next30sec();
        if(!this.isMobile){
            this.speedBtn.onclick = () => this.changeRate();
        }
        this.rateBtns.forEach(el => {
            el.onclick = () => this.setRate(el.dataset.rate);
        })

        this.volumeInput.oninput = (ev) => {
            this.changeVolume(ev);
            this.handleInputChange(ev.target);
        };
        this.handleInputChange(this.volumeInput);
        this.seekInput.onchange = (ev) => this.playPause(true);
        this.seekInput.oninput = (ev) => {
            this.changeSeek(ev);
            this.handleInputChange(ev.target);
        };
        this.handleInputChange(this.seekInput);
        this.togglePrevBtn();
        this.toggleNextBtn();
        document.body.onkeydown = (ev) => this.keyEvents(ev);
        // this.setStartPosition();
    }

    playPause(flag) {
        this.played = typeof(flag) !== 'undefined' ? flag : !this.played;
        if(this.played){
            this.howlPlayer[this.songIndex].play()
        }else{
            this.howlPlayer[this.songIndex].pause()
        }
        this.togglePlayBtn()
    }

    stop(){
        this.played = false;
        this.togglePlayBtn()
        this.howlPlayer.forEach((el, index) => {
            el.stop();
        });
    }

    step() {
        const self = this;
        const seek = self.howlPlayer[self.songIndex].seek() || 0;
        const duration = self.howlPlayer[self.songIndex].seek() || 0;
        self.seekInput.value = (((seek / self.howlPlayer[self.songIndex].duration()) * 100) || 0);
        self.handleInputChange(self.seekInput);
        sessionStorage.setItem(self.howlPlayer[self.songIndex]._src, seek);
        if (self.howlPlayer[self.songIndex].playing()) {
            requestAnimationFrame(self.step.bind(self));
        }
    }

    prevSong () {
        if(this.songIndex > 0){
            this.stop();
            this.songIndex = this.songIndex <= 0 ? 0 : this.songIndex - 1;
            this.togglePrevBtn();
            this.toggleNextBtn();
            this.playPause(true);
        }
    }

    nextSong () {
        if(this.songIndex < this.songsLength-1){
            this.stop();
            this.songIndex = this.songIndex >= this.songsLength-1 ? this.songsLength-1 : this.songIndex + 1;
            this.togglePrevBtn();
            this.toggleNextBtn();
            this.playPause(true);
        }
    }

    prev30sec () {
        this.howlPlayer[this.songIndex].seek(this.howlPlayer[this.songIndex].seek() - 30)
    }

    next30sec () {
        this.howlPlayer[this.songIndex].seek(this.howlPlayer[this.songIndex].seek() + 30)
    }

    changeRate() {
        this.rate += 0.25
        this.rate = this.rate > 2 ? 0.25 : this.rate;
        this.howlPlayer.forEach((el, index) => {
            el.rate(this.rate)
        });
        this.rateNode.textContent = `${this.rate}x`
    }
    setRate(rate){
        this.rate = rate;
        this.howlPlayer.forEach((el, index) => {
            el.rate(this.rate)
        });
        this.rateNode.textContent = `${this.rate}x`
    }
    handleRateChange() {
        this.rateBtns.forEach(el => {
            el.classList.toggle('active', el.dataset.rate == this.rate)
        })
    }
    changeVolume(ev) {
        this.howlPlayer.forEach((el, index) => {
            el.volume(ev.target.value);
        });
    }
    changeSeek(ev){
        const delta = this.howlPlayer[this.songIndex].duration() / 100;
        this.howlPlayer[this.songIndex].seek(ev.target.value * delta);
        this.playPause(false);
    }
    keyEvents(ev){
        if(ev.target && ev.target == this.container || ev.target.closest('.audioplayer')){
            if(ev.keyCode == 32){
                ev.preventDefault();
                ev.stopPropagation();
                this.playPause();
            }
        }
    }
    togglePlayBtn() {
        this.container.classList.toggle(this.playClass, this.played)
    }

    togglePrevBtn() {
        this.prevBtn.classList.toggle('disabled', this.songIndex <= 0)
    }
    toggleNextBtn() {
        this.nextBtn.classList.toggle('disabled', this.songIndex >= this.songsLength-1)
    }
    handleSongEnd() {
        if(this.songIndex < this.songsLength-1){
            this.nextSong()
        }else{
            this.stop();
        }
    }

    handleInputChange(input) {
        const min = input.min
        const max = input.max
        const val = input.value
        let percentage = (val - min) * 100 / (max - min)

        input.style.backgroundSize = percentage + '% 100%'
    }

    setStartPosition() {
        // this.howlPlayer.forEach((el, index) => {
        //     if(sessionStorage.getItem(el._src)){
        //         el.seek(sessionStorage.getItem(el._src));
        //     }
        // });
    }
}

function initAudioPlayer() {
    if(!window.audioPlayer){
        window.audioPlayer = [];
    }
    const containers = document.querySelectorAll('.audioplayer');
    containers.forEach((el) => {
        if(el.dataset.sounds){
            window.audioPlayer[(new Date()).getTime()] = new AudioPlayer(el);
        }
    });
}
