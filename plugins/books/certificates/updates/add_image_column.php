<?php

namespace Books\Certificates\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use October\Rain\Support\Facades\Schema;

return new class extends Migration {
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::table('books_certificates_certificate_transactions', function (Blueprint $table) {
            $table->string('image');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropColumns('image');
    }
};
