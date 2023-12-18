<?php namespace Books\Certificates\Console;

use Books\Certificates\Classes\Enums\CertificateTransactionStatus;
use Books\Certificates\Models\CertificateTransactions;
use Books\Profile\Services\OperationHistoryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * ReturnOfCertificate Command
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class ReturnOfCertificate extends Command
{
    /**
     * @var string signature for the console command.
     */
    protected $signature = 'certificates:return.of.certificate';

    /**
     * @var string description is the console command description
     */
    protected $description = 'No description provided yet...';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $certificates = CertificateTransactions::where('status', CertificateTransactionStatus::SENT)
            ->where('created_at', '<', Carbon::now()->subDays(10))
            ->get();

        $certificates->each(function ($certificate) {
            $certificate->sender->user->proxyWallet()->deposit($certificate->amount);
            $operationHistoryService = app(OperationHistoryService::class);
            $operationHistoryService->returnCertificate($certificate->sender->user, $certificate->amount, $certificate->receiver);
            $certificate->status = CertificateTransactionStatus::RETURNED;
            $certificate->save();
        });

    }
}
