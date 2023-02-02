function initReader() {
    window.reader = {
        reader: document.querySelector('.reader'),
        readerBody: document.querySelector('.reader__body'),
        fzDcrBtn: document.querySelector('.reader-decrease-fz'),
        fzEncrcBtn: document.querySelector('.reader-increase-fz'),
        bgDcrBtn: document.querySelector('.reader-decrease-bg'),
        bgEncrBtn: document.querySelector('.reader-increase-bg'),
        curBgIndex: parseInt(Cookies.get('reader_background_color_index')) || 0,
        bgClasses: ['_bg-black', '_bg-gray', '_bg-yellow', '_bg-white'],
        maxFont: 24,
        curFont: parseInt(Cookies.get('reader_font_size')) || 16,
        minFont: 12,
        decreaseBG: function () {
            this.curBgIndex = this.curBgIndex - 1 < 0 ? this.curBgIndex : this.curBgIndex - 1;
            this.changeBg();
        },
        increaseBG: function () {
            this.curBgIndex = this.curBgIndex + 1 > this.bgClasses.length - 1 ? this.curBgIndex : this.curBgIndex + 1;
            this.changeBg();
        },
        changeBg: function () {
            for (var i = 0; i < this.bgClasses.length; i++) {
                this.reader.classList.toggle(this.bgClasses[i], i == this.curBgIndex);
            }
            this.setCookie()
            this.changeBtnsState();
        },
        decreaseFZ: function () {
            this.curFont = this.curFont - 2 < this.minFont ? this.curFont : this.curFont - 2;
            this.setFontSize();
        },
        increaseFZ: function () {
            this.curFont = this.curFont + 2 > this.maxFont ? this.curFont : this.curFont + 2;
            this.setFontSize();
        },
        setFontSize: function () {
            this.readerBody.style.fontSize = `${this.curFont}px`;
            this.setCookie()
            this.changeBtnsState();
        },
        changeBtnsState: function () {
            this.bgDcrBtn.classList.toggle('_disabled', this.curBgIndex <= 0);
            this.bgEncrBtn.classList.toggle('_disabled', this.curBgIndex >= this.bgClasses.length - 1);
            this.fzDcrBtn.classList.toggle('_disabled', this.curFont <= this.minFont);
            this.fzEncrcBtn.classList.toggle('_disabled', this.curFont >= this.maxFont);
        },
        setCookie() {
            Cookies.set(`reader_font_size`, this.curFont, {expires: 2000})
            Cookies.set(`reader_background_color_index`, this.curBgIndex, {expires: 2000})
        },
        init: function () {
            this.setFontSize()
            this.changeBg()
        }
    }
    window.reader.init();
}

