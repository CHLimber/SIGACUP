<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidato_estudiante', function (Blueprint $table) {
            $table->string('token_pago', 64)->unique()->nullable();
            $table->decimal('monto_bs', 8, 2)->nullable();
            $table->decimal('monto_usd', 8, 2)->nullable();
            $table->decimal('tasa_cambio', 8, 4)->nullable();
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamp('pagado_at')->nullable();
            $table->string('numero_factura', 30)->unique()->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('candidato_estudiante', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'token_pago',
                'monto_bs',
                'monto_usd',
                'tasa_cambio',
                'stripe_session_id',
                'stripe_payment_intent_id',
                'pagado_at',
                'numero_factura',
            ]);
        });
    }
};
