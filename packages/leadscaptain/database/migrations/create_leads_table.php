<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            $table->string('external_id', 191)->unique();
            $table->string('leadscaptain_public_identifier', 191)->nullable()->index();
            $table->string('leadscaptain_id', 191)->nullable()->index();
            $table->unsignedBigInteger('leadscaptain_member_id')->default(0);

            $table->string('email', 191)->nullable()->index();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('name', 255)->nullable()->index();
            $table->string('gender', 50)->nullable();
            $table->string('country_code', 10)->nullable()->index();
            $table->string('birth_year', 10)->nullable();
            $table->string('headline', 500)->nullable();
            $table->text('summary')->nullable();

            $table->string('team', 150)->nullable();
            $table->string('hierarchy', 100)->nullable();
            $table->string('persona', 100)->nullable();
            $table->string('industry_name', 150)->nullable()->index();
            $table->string('position_title', 255)->nullable()->index();
            $table->string('position_location', 255)->nullable();
            $table->text('position_description')->nullable();

            $table->string('company_name', 255)->nullable()->index();
            $table->string('company_leadscaptain_id', 191)->nullable()->index();
            $table->string('company_leadscaptain_universal_name', 255)->nullable();
            $table->string('company_salesforce_id', 191)->nullable();
            $table->string('company_spendesk_id', 191)->nullable();
            $table->string('company_hubspot_id', 191)->nullable();

            $table->string('linkedin_url', 500)->nullable();
            $table->boolean('linkedin_source')->default(false);
            $table->boolean('sales_navigator_source')->default(false);
            $table->string('email_status', 50)->nullable()->index();
            $table->string('email_last_checked', 100)->nullable();

            $table->json('metadata')->nullable();
            $table->timestamp('position_started_at')->nullable();
            $table->timestamp('remote_created_at')->nullable()->index();
            $table->timestamps();

            $table->index(['created_at', 'external_id']);
            $table->index(['company_name', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
