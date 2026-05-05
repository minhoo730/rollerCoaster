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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6)->comment('국내주식 단축 종목코드');
            $table->string('name', 120)->comment('종목명');
            $table->string('market', 20)->comment('시장: kospi, kosdaq, konex');
            $table->string('venue', 20)->default('krx')->comment('거래소/체결 venue: krx, nxt');
            $table->string('sector', 120)->nullable()->comment('업종');
            $table->boolean('is_active')->default(true)->comment('현재 마스터 기준 활성 여부');
            $table->string('source', 40)->nullable()->comment('마스터 데이터 제공처');
            $table->json('raw_payload')->nullable()->comment('원천 파일 보조 필드');
            $table->timestamp('last_synced_at')->nullable()->comment('마지막 동기화 시각');
            $table->timestamps();

            $table->unique(['code', 'venue'], 'stocks_code_venue_unique');
            $table->index(['market', 'venue', 'is_active'], 'stocks_market_venue_active_index');
            $table->index('name', 'stocks_name_index');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('stocks', function (Blueprint $table) {
                $table->comment('국내주식 종목 마스터');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
