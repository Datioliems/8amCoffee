<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Bảng cảnh báo chính ──────────────────────────────────────
        Schema::create('SCAN_ANOMALY_ALERT', function (Blueprint $table) {
            $table->id();

            // Định danh IP (không lưu IP gốc trên dashboard)
            $table->string('ip_hash', 255)->nullable()->index();
            $table->string('ip_masked', 64)->nullable(); // đủ chứa IPv6 đã che

            $table->string('ma_ban', 50)->nullable();
            $table->string('ma_chi_nhanh', 50)->nullable()->index();

            // Feature summary
            $table->integer('scan_count')->default(0);
            $table->integer('distinct_tables')->default(0);
            $table->integer('distinct_branches')->default(0);
            $table->integer('unique_user_agents')->default(0);
            $table->integer('created_orders')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0.00);

            // Kết quả phán đoán
            $table->decimal('anomaly_score', 8, 4)->nullable(); // raw score từ ML (thấp = bất thường)
            $table->integer('risk_score')->default(0);          // 0–100 (cao = nguy hiểm)
            $table->string('risk_level', 20)->default('low');   // low / medium / high / critical
            $table->boolean('is_alert')->default(false);        // true = vượt ngưỡng cảnh báo
            $table->string('detection_method', 20)->default('rule'); // rule / ml / hybrid

            // Giải thích
            $table->text('reason')->nullable();
            $table->text('suggested_action')->nullable();
            $table->json('raw_summary')->nullable(); // feature + rule_result + ml_result + model_version

            $table->timestamp('detected_at')->nullable()->index();
            $table->timestamps();
        });

        // ── Bảng phản hồi để dần tạo nhãn cho supervised learning ──
        Schema::create('SCAN_ANOMALY_FEEDBACK', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')
                  ->constrained('SCAN_ANOMALY_ALERT')
                  ->cascadeOnDelete();
            $table->boolean('is_true_positive'); // true = đúng là bất thường
            $table->string('reviewed_by', 100)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SCAN_ANOMALY_FEEDBACK');
        Schema::dropIfExists('SCAN_ANOMALY_ALERT');
    }
};
