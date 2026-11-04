<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Stage 2 rehearsal only. Not in database/migrations; never registered by normal migrate.
return new class extends Migration
{
    public function up(): void
    {
        \Tests\Support\TestDatabase::assertSafe(app());

        Schema::table('lapangan', function (Blueprint $table) {
            $table->time('regular_opens_at')->nullable();
            $table->time('regular_closes_at')->nullable();
            $table->unsignedSmallInteger('booking_window_days')->default(14);
            $table->string('booking_contact')->nullable();
            $table->boolean('hourly_booking_enabled')->default(false);
        });

        Schema::create('venue_courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lapangan_id')->constrained('lapangan')->restrictOnDelete();
            $table->foreignId('legacy_court_id')->nullable()->constrained('courts')->restrictOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->unique(['lapangan_id', 'legacy_court_id'], 'vc_legacy_pair_unique');
        });

        Schema::create('operating_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lapangan_id')->constrained('lapangan')->restrictOnDelete();
            $table->date('date');
            $table->boolean('is_closed')->default(false);
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['lapangan_id', 'date'], 'oe_venue_date_unique');
        });

        Schema::create('court_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_court_id')->constrained('venue_courts')->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('reopened_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();
            $table->index(['venue_court_id', 'starts_at', 'ends_at'], 'cc_court_interval');
        });

        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_court_id')->constrained('venue_courts')->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
            $table->unique(['venue_court_id', 'starts_at'], 'ss_court_start_unique');
            $table->unique(['id', 'venue_court_id'], 'ss_id_court_unique');
        });

        Schema::table('konfirmasi_bookings', function (Blueprint $table) {
            $table->boolean('is_legacy')->default(true);
            $table->foreignId('venue_court_id')->nullable()->constrained('venue_courts')->restrictOnDelete();
            $table->string('source', 24)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('canceled_at')->nullable();
            $table->foreignId('canceled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->char('idempotency_key_hash', 64)->nullable()->unique('kb_idempotency_unique');
            $table->unique(['id', 'venue_court_id'], 'kb_id_court_unique');
            $table->index(['is_legacy', 'status', 'expires_at'], 'kb_expiry_scan');
        });
        DB::statement("ALTER TABLE konfirmasi_bookings MODIFY status ENUM('pending','confirmed','canceled','expired') NOT NULL DEFAULT 'pending'");
        DB::statement('ALTER TABLE konfirmasi_bookings MODIFY jadwal_lapangan_id BIGINT UNSIGNED NULL');

        Schema::create('booking_slots', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('slot_id');
            $table->unsignedBigInteger('venue_court_id');
            $table->primary(['booking_id', 'slot_id']);
            $table->unique(['booking_id', 'slot_id', 'venue_court_id'], 'bs_all_unique');
            $table->foreign(['booking_id', 'venue_court_id'], 'bs_booking_court_fk')
                ->references(['id', 'venue_court_id'])->on('konfirmasi_bookings')->restrictOnDelete();
            $table->foreign(['slot_id', 'venue_court_id'], 'bs_slot_court_fk')
                ->references(['id', 'venue_court_id'])->on('schedule_slots')->restrictOnDelete();
        });

        Schema::create('active_slot_reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('slot_id')->primary();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('venue_court_id');
            $table->foreign(['booking_id', 'slot_id', 'venue_court_id'], 'asr_history_fk')
                ->references(['booking_id', 'slot_id', 'venue_court_id'])->on('booking_slots')->restrictOnDelete();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lapangan_id')->nullable()->constrained('lapangan')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('subject_type', 64);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('action', 64);
            $table->text('reason')->nullable();
            $table->json('changes')->nullable();
            $table->dateTime('occurred_at');
            $table->index(['subject_type', 'subject_id', 'occurred_at'], 'al_subject_time');
        });

        Schema::create('operational_task_runs', function (Blueprint $table) {
            $table->id();
            $table->string('task', 64);
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->string('status', 16);
            $table->unsignedInteger('processed_count')->default(0);
            $table->string('error_code')->nullable();
            $table->index(['task', 'started_at'], 'otr_task_time');
        });

        DB::statement("ALTER TABLE lapangan ADD CONSTRAINT venue_hours_check CHECK (
            booking_window_days > 0 AND (
                (regular_opens_at IS NULL AND regular_closes_at IS NULL) OR
                (regular_opens_at IS NOT NULL AND regular_closes_at IS NOT NULL
                 AND regular_opens_at < regular_closes_at
                 AND TIME_TO_SEC(regular_opens_at) BETWEEN 0 AND 86399
                 AND TIME_TO_SEC(regular_closes_at) BETWEEN 0 AND 86399
                 AND MINUTE(regular_opens_at)=0 AND SECOND(regular_opens_at)=0
                 AND MINUTE(regular_closes_at)=0 AND SECOND(regular_closes_at)=0)
            ))");
        DB::statement("ALTER TABLE schedule_slots ADD CONSTRAINT slot_hour_check CHECK (
            TIMESTAMPDIFF(SECOND,starts_at,ends_at)=3600
            AND DATE(starts_at)=DATE(ends_at)
            AND MINUTE(starts_at)=0 AND SECOND(starts_at)=0)");
        DB::statement("ALTER TABLE court_closures ADD CONSTRAINT closure_interval_check CHECK (
            starts_at < ends_at AND DATE(starts_at)=DATE(ends_at)
            AND MINUTE(starts_at)=0 AND SECOND(starts_at)=0
            AND MINUTE(ends_at)=0 AND SECOND(ends_at)=0)");
        DB::statement("ALTER TABLE operating_exceptions ADD CONSTRAINT exception_hours_check CHECK (
            (is_closed=1 AND opens_at IS NULL AND closes_at IS NULL) OR
            (is_closed=0 AND opens_at IS NOT NULL AND closes_at IS NOT NULL
             AND opens_at < closes_at
             AND TIME_TO_SEC(opens_at) BETWEEN 0 AND 86399
             AND TIME_TO_SEC(closes_at) BETWEEN 0 AND 86399
             AND MINUTE(opens_at)=0 AND SECOND(opens_at)=0
             AND MINUTE(closes_at)=0 AND SECOND(closes_at)=0))");
        DB::statement("ALTER TABLE konfirmasi_bookings ADD CONSTRAINT hourly_booking_check CHECK (
            is_legacy=1 OR (
                jadwal_lapangan_id IS NULL AND venue_court_id IS NOT NULL
                AND source IS NOT NULL AND source IN ('website','walk_in','whatsapp')
                AND starts_at IS NOT NULL AND ends_at IS NOT NULL AND starts_at < ends_at
                AND DATE(starts_at)=DATE(ends_at)
                AND MINUTE(starts_at)=0 AND SECOND(starts_at)=0
                AND MINUTE(ends_at)=0 AND SECOND(ends_at)=0
                AND durasi IS NOT NULL AND durasi > 0
                AND TIMESTAMPDIFF(SECOND,starts_at,ends_at)=durasi*3600
                AND (status <> 'pending' OR (expires_at IS NOT NULL AND expires_at <= starts_at))
            ))");

        foreach ([
            ['lapangan', 'user_id', 'users'],
            ['jadwal_lapangan', 'lapangan_id', 'lapangan'],
            ['jadwal_lapangan', 'court_id', 'courts'],
            ['konfirmasi_bookings', 'jadwal_lapangan_id', 'jadwal_lapangan'],
        ] as [$tableName, $column, $parent]) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $parent) {
                $table->dropForeign([$column]);
                $table->foreign($column)->references('id')->on($parent)->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        throw new LogicException('Restore a verified backup for this rehearsal; do not roll back data-bearing booking tables destructively.');
    }
};
