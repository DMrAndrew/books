class Reader {
    reader = null;
    readerBody = null;
    fzDcrBtn = null;
    fzEncrcBtn = null;
    bgDcrBtn = null;
    bgEncrBtn = null;
    curBgIndex = 0;
    bgClasses = ['_bg-yellow', '_bg-gray', '_bg-black', '_bg-white'];
    maxFont = 24;
    curFont = 14;
    minFont = 12;
    #tracker_id = null;
    #trackerInterval = 15000;

    #paginator_id = null

    constructor() {
        this.reader = document.querySelector('.reader')
        this.readerBody = document.querySelector('.reader__body')
        this.fzDcrBtn = document.querySelector('.reader-decrease-fz')
        this.fzEncrcBtn = document.querySelector('.reader-increase-fz')
        this.bgDcrBtn = document.querySelector('.reader-decrease-bg')
        this.bgEncrBtn = document.querySelector('.reader-increase-bg')
        this.curBgIndex = parseInt(Cookies.get('reader_background_color_index')) || this.curBgIndex
        this.curFont = parseInt(Cookies.get('reader_font_size')) || this.curFont
        this.#paginator_id = $(`input[name='paginator_id']`).val()
        this.setFontSize()
        this.changeBg()
        this.initTracker()
    }

    clear() {
        clearInterval(this.#tracker_id)
    }

    track(ms = null) {

        if (this.#paginator_id && this.#trackerInterval >= 5000) {
            oc.ajax('reader::onTrack', {
                data: {
                    paginator_id: this.#paginator_id,
                    ms: ms || this.#trackerInterval
                },
                progressBar: false,
                error: () => {
                }
            })
        }
    }


    initTracker() {
        this.track(1)
        this.#tracker_id = setInterval(() => {
            this.track()
        }, this.#trackerInterval)
    }


    decreaseBG() {
        this.curBgIndex = this.curBgIndex - 1 < 0 ? this.curBgIndex : this.curBgIndex - 1;
        this.changeBg();
    }

    increaseBG() {
        this.curBgIndex = this.curBgIndex + 1 > this.bgClasses.length - 1 ? this.curBgIndex : this.curBgIndex + 1;
        this.changeBg();
    }

    changeBg() {
        for (var i = 0; i < this.bgClasses.length; i++) {
            this.reader.classList.toggle(this.bgClasses[i], i === this.curBgIndex);
        }
        this.setCookie()
        this.changeBtnsState();
    }

    decreaseFZ() {
        this.curFont = this.curFont - 2 < this.minFont ? this.curFont : this.curFont - 2;
        this.setFontSize();
    }

    increaseFZ() {
        this.curFont = this.curFont + 2 > this.maxFont ? this.curFont : this.curFont + 2;
        this.setFontSize();
    }

    setFontSize() {
        this.readerBody.style.fontSize = `${this.curFont}px`;
        this.setCookie()
        this.changeBtnsState();
    }

    changeBtnsState() {
        this.bgDcrBtn.classList.toggle('_disabled', this.curBgIndex <= 0);
        this.bgEncrBtn.classList.toggle('_disabled', this.curBgIndex >= this.bgClasses.length - 1);
        this.fzDcrBtn.classList.toggle('_disabled', this.curFont <= this.minFont);
        this.fzEncrcBtn.classList.toggle('_disabled', this.curFont >= this.maxFont);
    }

    setCookie() {
        Cookies.set(`reader_font_size`, this.curFont, {expires: 2000})
        Cookies.set(`reader_background_color_index`, this.curBgIndex, {expires: 2000})
    }
}

function initReader() {
    window.reader && window.reader.clear()
    window.reader = new Reader();

}
addEventListener('page:unload', function () {
    // console.log('page:unload')

    window.reader && window.reader.clear()
})

