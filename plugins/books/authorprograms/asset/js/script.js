oc.ajax('onLoadReaderBirthdayModal', {
    success: function (data) {
        this.success(data).done(function() {
            setTimeout(() => {
                if (data.is_open) {
                    $('#reader-birthday-modal .ui-modal').addClass('active');
                }
            },1000)

        })
    },
})

oc.ajax('onLoadNewReaderModal', {
    success: function (data) {
        this.success(data).done(function() {
            setTimeout(() => {
                if (data.is_open) {
                    $('#new-reader-modal .ui-modal').addClass('active');
                }
            },800)

        })
    },
})

oc.ajax('onLoadRegularReaderModal', {
    success: function (data) {
        this.success(data).done(function() {
            setTimeout(() => {
                if (data.is_open) {
                    $('#regular-reader-modal .ui-modal').addClass('active');
                }
            },500)

        })
    },
})

function onCloseModal(cookieName, modalId) {
    oc.ajax('onCloseModal', {
        data: {'cookieName': cookieName},
        success: function (data) {
            $(modalId+' .ui-modal').removeClass('active');
        },
    })
}

function onNeverShowModal(cookieName, modalId) {
    console.log(cookieName, modalId)
    oc.ajax('onNeverShowModal', {
        data: {'cookieName': cookieName},
        success: function (data) {
            $(modalId+' .ui-modal').removeClass('active');
        },
    })
}



