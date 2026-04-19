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
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('price_amount')->default(0)->after('remaining_sessions');
            $table->unsignedBigInteger('amount_paid')->default(0)->after('price_amount');
        });

        DB::table('payments')
            ->orderBy('id')
            ->get()
            ->each(function (object $payment): void {
                $priceAmount = 0;

                if ($payment->package_id) {
                    $priceAmount = (int) (DB::table('packages')->where('id', $payment->package_id)->value('price') ?? 0);
                }

                DB::table('payments')
                    ->where('id', $payment->id)
                    ->update([
                        'price_amount' => $priceAmount,
                        'amount_paid' => $priceAmount,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['price_amount', 'amount_paid']);
        });
    }
};
