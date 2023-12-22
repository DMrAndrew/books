<?php namespace Books\Certificates\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateCertificateTransactionsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_certificates_certificate_transactions', function(Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sender_id');
            $table->unsignedInteger('recipient_id');
            $table->string('status', 50);
            $table->decimal('amount', 64, 0)->default(0);
            $table->boolean('anonymity')->default(false);
            $table->text('description');
            $table->timestamps();

            $table->foreign('sender_id')
                ->on('books_profile_profiles')
                ->references('id');
            $table->foreign('recipient_id')
                ->on('books_profile_profiles')
                ->references('id');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_certificates_certificate_transactions');
    }
};
