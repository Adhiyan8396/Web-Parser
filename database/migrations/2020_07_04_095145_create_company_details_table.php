<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_details', function (Blueprint $table) {
            $table->unsignedinteger('company_id');
            $table->string('corporate_identification_number')->unique();
            $table->integer('registration_number',20);
            $table->date('age');
            $table->string('category');
            $table->string('sub_category');
            $table->string('company_class');
            $table->string('roc_code');
            $table->integer('members_count');
            $table->string('email');
            $table->string('address', 500);
            $table->boolean('is_listed')->default(1);
            $table->string('state');
            $table->string('district');
            $table->string('city');
            $table->integer('pin', 6);
            $table->string('section');
            $table->string('division');
            $table->string('main_group');
            $table->string('main_class');
            $table->tinyInteger('company_status');
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_details');
    }
}
