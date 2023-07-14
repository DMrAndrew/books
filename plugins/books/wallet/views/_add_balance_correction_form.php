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

            <div class="form-group span-full">
                <label for="Form-field-User-password" class="form-label">
                    Баланс после корректировки:
                </label>
                <input type="text" name="targetBalance" id="targetBalance" value="" placeholder="" class="form-control" autocomplete="off" maxlength="255" required>

                <p class="form-text">Введите необходимое значение баланса</p>
            </div>

            <div class="form-group span-full">
                <label for="Form-field-User-password" class="form-label">
                    Дополнительная информация
                </label>
                <textarea name="balance_correction_description" id="balance_correction_description" class="form-control" rows="6" style="width: 100%" maxlength="1000"></textarea>
                <p class="form-text">Пользователь не увидит эту информацию</p>
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
<script>
    $(function () {
        $('body').on('keyup', '#targetBalance', function (e) {
            console.log('keyup');
            e.preventDefault();
            this.value = this.value.replace(/[^\d.]/g, '');
        });
    });
</script>
