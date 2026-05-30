<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulacion_id')->constrained('postulacion')->restrictOnDelete();
            $table->string('token_pago', 64)->nullable()->unique();
            $table->decimal('monto_bs', 8, 2)->nullable();
            $table->decimal('monto_usd', 8, 2)->nullable();
            $table->decimal('tasa_cambio', 8, 4)->nullable();
            $table->string('metodo', 20)->default('stripe');
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('referencia_externa', 200)->nullable();
            $table->string('numero_factura', 30)->nullable()->unique();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();

            $table->index('postulacion_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago');
    }
};
