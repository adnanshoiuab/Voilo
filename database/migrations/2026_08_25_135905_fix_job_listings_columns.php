<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
        $table->enum('job_type', ['Full-Time', 'Part-Time', 'Contract', 'Freelance', 'Internship'])
              ->nullable()
              ->after('tags');
        $table->boolean('remote')->default(false);
        $table->text('requirements')->nullable();
        $table->text('benefits')->nullable();
        $table->string('address')->nullable();
        $table->string('city')->nullable();
        $table->string('state')->nullable();
        $table->string('zipcode')->nullable();
        $table->string('contact_email')->nullable();
        $table->string('contact_phone')->nullable();
        $table->string('company_name')->nullable();
        $table->text('company_description')->nullable();
        $table->string('company_logo')->nullable();
        $table->string('company_website')->nullable();
        $table->integer('salary')->nullable();
        $table->string('tags')->nullable();
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
    });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn([
                'job_type',
                'remote',
                'requirements',
                'benefits',
                'address',
                'city',
                'state',
                'zipcode',
                'contact_email',
                'contact_phone',
                'company_name',
                'company_description',
                'company_logo',
                'company_website',
            ]);
        });
    }
};