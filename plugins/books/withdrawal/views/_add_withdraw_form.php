<div id="addWithdrawFormPopup">
    <?= Form::open(['id' => 'addWithdrawForm', 'flash' => true, 'validate' => true]) ?>
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="popup">&times;</button>
        <h4 class="modal-title">Вывести средства</h4>
    </div>
    <div class="modal-body">
        <div class="row">

            <input type="hidden" name="userId" value="<?= $userId ?>" />

            <div class="form-group form-group-preview partial-field span-full">
                Списать <b><?= $balance ?></b> руб
            </div>
        </div>

    </div>
    <div class="modal-footer">
        <div class="loading-indicator-container">
            <button
                type="button"
                class="btn btn-default"
                data-dismiss="popup">
                <?= e(trans('backend::lang.form.cancel')) ?>
            </button>
            <button
                type="submit"
                class="btn btn-success"
                data-request="onWithdrawUserBalance"
                data-load-indicator="Списание..."
                data-request-flash="true"
                data-request-success="$(this).trigger('close.oc.popup')"
                id="editTypeButton"
                <?= $canWithdraw ? '' : 'disabled'?>>
                Подтвердить и списать с баланса
            </button>
        </div>
    </div>
    <?= Form::close() ?>
</div>
