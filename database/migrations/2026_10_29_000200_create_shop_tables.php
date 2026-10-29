<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Our own data (public schema). Nothing here comes from the catalog API,
 * so it survives a purge of the catalog schema (API terms §5).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            // How to read this vendor's file: {"brand": "Marka", "article_no": "Kodi", ...}
            $table->jsonb('file_profile')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
        });

        // Canonical part brands, in our own words (BOSCH, MANN-FILTER, ...).
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            // premium | standard | budget, used for ranking
            $table->string('tier', 20)->default('standard');
            $table->timestamps();
        });

        // Every spelling vendors use for a brand, normalised (uppercase, no spaces/punctuation).
        Schema::create('brand_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('alias')->unique();
            $table->timestamps();
        });

        // Albanian shop categories: ferrota, disk-freni, ...
        Schema::create('shop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_sq');
            $table->string('name_en')->nullable();
            $table->jsonb('synonyms')->default('[]');
            // Lowercase fragments of catalog category names that belong here, e.g. ["brake pad"]
            $table->jsonb('match_keywords')->default('[]');
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('sort')->default(100);
            $table->timestamps();
        });

        Schema::create('vendor_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20); // file | api
            $table->string('file_path')->nullable();
            $table->string('status', 20)->default('pending'); // pending|running|done|failed
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_new')->default(0);
            $table->unsignedInteger('rows_updated')->default(0);
            $table->unsignedInteger('rows_invalid')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('raw_brand')->default(''); // '' when the file has no brand
            $table->string('raw_article_no');
            $table->string('norm_article_no')->index();
            $table->string('raw_name')->nullable();
            $table->string('ean', 20)->nullable()->index();
            $table->decimal('price', 12, 2)->nullable();
            $table->integer('qty')->default(0);
            $table->unsignedSmallInteger('lead_days')->nullable();
            // pending | matched | review | unmatched | ignored
            $table->string('match_status', 20)->default('pending')->index();
            $table->foreignId('last_import_id')->nullable()->constrained('vendor_imports')->nullOnDelete();
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();
            $table->unique(['vendor_id', 'raw_brand', 'norm_article_no']);
            $table->index(['brand_id', 'norm_article_no']);
        });

        // Daily count of catalog API calls, so quota spend is visible. Our own data.
        Schema::create('catalog_api_usage', function (Blueprint $table) {
            $table->date('day');
            $table->string('endpoint', 100);
            $table->unsignedInteger('calls')->default(0);
            $table->unsignedInteger('errors')->default(0);
            $table->primary(['day', 'endpoint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_api_usage');
        Schema::dropIfExists('vendor_items');
        Schema::dropIfExists('vendor_imports');
        Schema::dropIfExists('shop_categories');
        Schema::dropIfExists('brand_aliases');
        Schema::dropIfExists('brands');
        Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('vendor_id'));
        Schema::dropIfExists('vendors');
    }
};
