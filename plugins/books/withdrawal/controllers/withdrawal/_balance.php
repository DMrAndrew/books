: <b><?=$formModel->proxyWallet()?->balance ?? '0' ?></b> руб
<div >
    <?php
        $withdrawalData = $formModel->withdrawalData;

        if ($withdrawalData == null) {
            $agreementStatus = '<span style="color:red">Отсутствует</span>';
            $withdrawAllowed = '<span style="color:red">-</span>';
            $withdrawFrozen = '<span style="color:red">-</span>';
        } else {
            /**
             * Статус договора на вывод средств
             */
            $statusColor = $withdrawalData->agreement_status == Books\Withdrawal\Classes\Enums\WithdrawalAgreementStatusEnum::APPROVED
                    ? 'green'
                    : 'red';
            $agreementStatus = '<span style="color:'.$statusColor.'">' . $withdrawalData->agreement_status->getLabel() . '</span>';

            /**
             * Статус вывода
             */
            $withdrawAllowedColor = $withdrawalData->withdrawal_status == Books\Withdrawal\Classes\Enums\WithdrawalStatusEnum::ALLOWED
                ? 'green'
                : 'red';
            $withdrawAllowed = '<span style="color:'.$withdrawAllowedColor.'">' . $withdrawalData->withdrawal_status->getLabel() . '</span>';

            /**
             * Статус заморозки
             */
            $withdrawFrozenColor = $withdrawalData->withdraw_frozen ? 'red' : 'green';
            $withdrawFrozenLabel = $withdrawalData->withdraw_frozen ? 'Заморожен' : 'Не заморожен';
            $withdrawFrozen = '<span style="color:'.$withdrawFrozenColor.'">' . $withdrawFrozenLabel . '</span>';
        }
    ?>
    <div class="form-field">
        Договор на вывод средств: <?=$agreementStatus;?>
        <?php if($withdrawalData):?>
            (<a href="<?= Backend::url('books/withdrawal/withdrawaldata/update/'.$withdrawalData->id) ?>" target="_blank">открыть</a>)
        <?php endif;?>
    </div>

    <div class="form-field">
        Вывод разрешен: <?=$withdrawAllowed;?>
    </div>

    <div class="form-field">
        Заморожен: <?=$withdrawFrozen;?>
    </div>
</div>

