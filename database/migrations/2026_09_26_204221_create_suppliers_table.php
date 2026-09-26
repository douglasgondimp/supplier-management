<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(1);
            $table->enum('type_person', ['fisica', 'juridica']);
            $table->string('phone_number', 11);
            $table->string('phone_type', 30);
            $table->string('zip_address', 10);
            $table->string('street', 255);
            $table->string('number', 20);
            $table->string('complement', 50)->nullable();
            $table->string('neighborhood', 100);
            $table->string('city', 50);
            $table->string('state', 2);
            $table->string('reference_point')->nullable();
            $table->boolean('has_condominium');
            $table->string('condominium_address', 255)->nullable();
            $table->string('condominium_number', 30)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_individuals', function (Blueprint $table) {
            $table->foreignId('supplier_id')->primary()->constrained('suppliers')->cascadeOnDelete();
            $table->string('cpf', 11);
            $table->string('name', 150);
            $table->string('surname')->nullable();
            $table->string('document_number', 15);
        });

        Schema::create('supplier_corporates', function (Blueprint $table) {
            $table->foreignId('supplier_id')->primary()->constrained('suppliers')->cascadeOnDelete();
            $table->string('cnpj', 14);
            $table->string('company_name', 255);
            $table->string('fantasy_name', 255);
            $table->string('state_registration_indicator', 50);
            $table->string('state_registration', 50)->nullable();
            $table->string('municipal_registration', 50)->nullable();
            $table->string('cnpj_status', 30)->nullable();
            $table->string('remittance', 50);
        });

        Schema::create('supplier_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('email', 150);
            $table->string('email_type', 30);
            $table->timestamps();
        });

        Schema::create('supplier_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('phone_number', 14);
            $table->string('phone_type', 30);
            $table->timestamps();
        });

        Schema::create('supplier_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('company', 150)->nullable();
            $table->string('position', 30)->nullable();
            $table->string('phone_number', 14);
            $table->string('phone_type', 30);
            $table->string('email', 150)->nullable();
            $table->string('email_type', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_contacts');
        Schema::dropIfExists('supplier_phones');
        Schema::dropIfExists('supplier_emails');
        Schema::dropIfExists('supplier_corporates');
        Schema::dropIfExists('supplier_individuals');
        Schema::dropIfExists('suppliers');
    }
};
