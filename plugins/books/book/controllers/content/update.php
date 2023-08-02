<?php Block::put('breadcrumb') ?>
<ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= Backend::url('books/book/content') ?>">Контент</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= e($this->pageTitle) ?></li>
</ol>
<?php Block::endPut() ?>

<?php if (!$this->fatalError): ?>

    <?= Form::open(['class' => 'layout']) ?>

    <div class="layout-row">
        <?= $this->formRender() ?>
    </div>
    <div class="form-buttons">
        <div class="loading-indicator-container">
            <button
                type="button"
                data-request="onAccept"
                data-request-data="close:1"
                data-browser-redirect-back
                data-hotkey="ctrl+enter, cmd+enter"
                data-load-indicator="<?= e(trans('backend::lang.form.saving_name', ['name' => $formRecordName])) ?>"
                class="btn btn-success">
                Принять
            </button>

            <span class="btn-text danger">
                    <?= e(trans('backend::lang.form.or')) ?> <a href="#" data-request="onReject"
                                                                data-request-data="close:1"
                                                                data-browser-redirect-back
                >Отклонить</a>
                </span>
        </div>
    </div>
<!--    <div class="form-buttons">-->
<!--        <div class="loading-indicator-container">-->
<!--            <button-->
<!--                type="submit"-->
<!--                data-request="onSave"-->
<!--                data-request-data="redirect:0"-->
<!--                data-hotkey="ctrl+s, cmd+s"-->
<!--                data-load-indicator="--><?php //= e(trans('backend::lang.form.saving_name', ['name' => $formRecordName])) ?><!--"-->
<!--                class="btn btn-primary">-->
<!--                --><?php //= e(trans('backend::lang.form.save')) ?>
<!--            </button>-->
<!--            <button-->
<!--                type="button"-->
<!--                data-request="onSave"-->
<!--                data-request-data="close:1"-->
<!--                data-browser-redirect-back-->
<!--                data-hotkey="ctrl+enter, cmd+enter"-->
<!--                data-load-indicator="--><?php //= e(trans('backend::lang.form.saving_name', ['name' => $formRecordName])) ?><!--"-->
<!--                class="btn btn-default">-->
<!--                --><?php //= e(trans('backend::lang.form.save_and_close')) ?>
<!--            </button>-->
<!--            <button-->
<!--                type="button"-->
<!--                class="oc-icon-trash-o btn-icon danger pull-right"-->
<!--                data-request="onDelete"-->
<!--                data-load-indicator="--><?php //= e(trans('backend::lang.form.deleting_name', ['name' => $formRecordName])) ?><!--"-->
<!--                data-request-confirm="--><?php //= e(trans('backend::lang.form.confirm_delete')) ?><!--">-->
<!--            </button>-->
<!--            <span class="btn-text">-->
<!--                    --><?php //= e(trans('backend::lang.form.or')) ?><!-- <a-->
<!--                    href="--><?php //= Backend::url('books/book/content') ?><!--">--><?php //= e(trans('backend::lang.form.cancel')) ?><!--</a>-->
<!--                </span>-->
<!--        </div>-->
<!--    </div>-->

    <?= Form::close() ?>

<?php else: ?>

    <p class="flash-message static error"><?= e($this->fatalError) ?></p>
    <p><a href="<?= Backend::url('books/book/content') ?>"
          class="btn btn-default"><?= e(trans('backend::lang.form.return_to_list')) ?></a></p>

<?php endif ?>
