<?php namespace Books\Profile\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Books\User\Classes\PrivacySettingsEnum as PSEnum;

/**
 * CreateProfileSettingsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class CreateProfileSettingsTable extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_profile_profile_settings', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('privacy_allow_fit_account_index_page', PSEnum::values())->default(PSEnum::default());
            $table->enum('privacy_allow_private_messaging', PSEnum::values())->default(PSEnum::default());
            $table->enum('privacy_allow_view_comment_feed', PSEnum::values())->default(PSEnum::default());
            $table->enum('privacy_allow_view_blog', PSEnum::values())->default(PSEnum::default());
            $table->enum('privacy_allow_view_video_blog', PSEnum::values())->default(PSEnum::default());

            $table->boolean('notify_new_record_blog')->default(false);
            $table->boolean('notify_new_record_video_blog')->default(false);
            $table->boolean('notify_update_store_items')->default(false);
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_profile_profile_settings');
    }
}
