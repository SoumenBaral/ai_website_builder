<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number'); // per site: #1001, #1002…
            $table->string('status', 20)->default('new'); // new · confirmed · completed · cancelled
            $table->string('customer_name', 100);
            $table->string('customer_email', 150)->nullable();
            $table->string('customer_phone', 40)->nullable();
            $table->string('customer_address', 500)->nullable();
            $table->text('note')->nullable();
            $table->json('items'); // [{id, title, price, amount, quantity, lineTotal}]
            $table->decimal('subtotal', 12, 2)->nullable(); // null when every item is "price on request"
            $table->json('currency')->nullable();
            $table->boolean('has_unpriced')->default(false);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'number']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
