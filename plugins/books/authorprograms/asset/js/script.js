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


function onCloseReaderBirthdayModal() {
    oc.ajax('onCloseReaderBirthdayModal', {
        success: function (data) {
            $('#reader-birthday-modal .ui-modal').removeClass('active');
        },
    })
}

function neverShowReaderBirthdayModal() {
    oc.ajax('onNeverShowReaderBirthdayModal', {
        success: function (data) {
            $('#reader-birthday-modal .ui-modal').removeClass('active');
        },
    })
}

oc.ajax('onLoadNewReaderModal', {
    success: function (data) {
        this.success(data).done(function() {
            setTimeout(() => {
                if (data.is_open) {
                    $('#new-reader-modal .ui-modal').addClass('active');
                }
            },1000)

        })
    },
})

function onCloseNewReaderModal() {
    oc.ajax('onCloseNewReaderModal', {
        success: function (data) {
            $('#new-reader-modal .ui-modal').removeClass('active');
        },
    })
}

function neverShowNewReaderModal() {
    oc.ajax('onNeverShowNewReaderModal', {
        success: function (data) {
            $('#new-reader-modal .ui-modal').removeClass('active');
        },
    })
}

oc.ajax('onLoadRegularReaderModal', {
    success: function (data) {
        this.success(data).done(function() {
            setTimeout(() => {
                if (data.is_open) {
                    $('#regular-reader-modal .ui-modal').addClass('active');
                }
            },1000)

        })
    },
})


function onCloseRegularReaderModal() {
    oc.ajax('onCloseRegularReaderModal', {
        success: function (data) {
            $('#regular-reader-modal .ui-modal').removeClass('active');
        },
    })
}

function neverShowRegularReaderModal() {
    oc.ajax('onNeverShowRegularReaderModal', {
        success: function (data) {
            $('#regular-reader-modal .ui-modal').removeClass('active');
        },
    })
}
