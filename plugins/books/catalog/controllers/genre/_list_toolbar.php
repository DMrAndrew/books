<div data-control="toolbar">
    <a
        href="<?= Backend::url('books/catalog/genre/create') ?>"
        class="btn btn-primary oc-icon-plus">
        <?= e(trans('backend::lang.list.create_button', ['name'=>'Жанр'])) ?>
    </a>


    <div class="btn-group dropdown dropdown-fixed" data-control="bulk-actions">
        <button
            data-primary-button
            type="button"
            class="btn btn-default"
            data-request="onBulkAction"
            data-trigger-action="enable"
            data-trigger=".control-list input[type=checkbox]"
            data-trigger-condition="checked"
            data-request-success="$(this).prop('disabled', true).next().prop('disabled', true)"
            data-stripe-load-indicator>
           Удалить выбранное
        </button>
        <button
            type="button"
            class="btn btn-default dropdown-toggle dropdown-toggle-split"
            data-trigger-action="enable"
            data-trigger=".control-list input[type=checkbox]"
            data-trigger-condition="checked"
            data-toggle="dropdown">
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" data-dropdown-title="<?= e(trans('books.catalog::lang.genres.bulk_actions')) ?>">
            <li>
                <a href="javascript:;" class="oc-icon-trash-o" data-action="delete" data-confirm="<?= e(trans('books.catalog::lang.genres.delete_selected_confirm')) ?>">
                    <?= e(trans('books.catalog::lang.genres.delete_selected')) ?>
                </a>
            </li>
            <li role="separator" class="divider"></li>
            <li>
                <a href="javascript:;" class="oc-icon-check-circle-o" data-action="activate" data-confirm="<?= e(trans('books.catalog::lang.genres.activate_selected_confirm')) ?>">
                    <?= e(trans('books.catalog::lang.genres.activate_selected')) ?>
                </a>
            </li>
            <li role="separator" class="divider"></li>
            <li>
                <a href="javascript:;" class="oc-icon-ban" data-action="deactivate" data-confirm="<?= e(trans('books.catalog::lang.genres.deactivate_selected_confirm')) ?>">
                    <?= e(trans('books.catalog::lang.genres.deactivate_selected')) ?>
                </a>
            </li>
            <li>
                <a href="javascript:;" class="oc-icon-check-circle-o" data-action="favorite" data-confirm="<?= e(trans('books.catalog::lang.genres.check_as_favorite')) ?>">
                    <?= e(trans('books.catalog::lang.genres.check_as_favorite')) ?>
                </a>
            </li>
            <li role="separator" class="divider"></li>
            <li>
                <a href="javascript:;" class="oc-icon-ban" data-action="unfavorite" data-confirm="<?= e(trans('books.catalog::lang.genres.uncheck_as_favorite')) ?>">
                    <?= e(trans('books.catalog::lang.genres.uncheck_as_favorite')) ?>
                </a>
            </li>

        </ul>
    </div>
</div>
