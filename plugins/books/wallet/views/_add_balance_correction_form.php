<div id="addWithdrawFormPopup">
    <?= Form::open(['id' => 'addWithdrawForm', 'flash' => true, 'validate' => true]) ?>
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="popup">&times;</button>
        <h4 class="modal-title">Корректировка баланса</h4>
    </div>
    <div class="modal-body">
        <div class="row">

            <input type="hidden" name="userId" value="<?= $userId ?>" />

            <div class="form-group form-group-preview partial-field span-full">
                Текущий баланс: <b><?= $currentBalanceAmount ?></b> руб
            </div>

            <div class="form-group span-left">
                <label for="Form-field-User-password" class="form-label">
                    Баланс после корректировки:
                </label>
                <input type="text" name="targetBalance" value="" placeholder="" class="form-control" autocomplete="off" maxlength="255" required>

                <p class="form-text">Введите необходимое значение баланса</p>
            </div>

            <div class="col-12">
                <textarea name="balance_correction_description" id="balance_correction_description" rows="10" style="width: 100%" maxlength="1000"></textarea>
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
                data-request="onCorrectionBalance"
                data-load-indicator="Корректировка..."
                data-request-flash="true"
                data-request-success="$(this).trigger('close.oc.popup')"
                id="editTypeButton">
                Подтвердить и изменить баланс
            </button>
        </div>
    </div>
    <?= Form::close() ?>
</div>
