<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('psychoeducations', function (Blueprint $table) {
            $table->id();
            $table->foreignId("program_id")->constrained("programs")->onDelete("cascade");
            $table->foreignId("indicator_id")->constrained("indicators")->cascadeOnDelete();
            $table->string("awarenessTopic");
            $table->date("awarenessDate");
            $table->smallInteger("ofMenHostCommunity");
            $table->smallInteger("ofMenIdp");
            $table->smallInteger("ofMenRefugee");
            $table->smallInteger("ofMenReturnee");
            $table->enum("ofMenDisabilityType", ["personWithDisability", "personWithoutDisability"]);
            $table->smallInteger("ofWomenHostCommunity");
            $table->smallInteger("ofWomenIdp");
            $table->smallInteger("ofWomenRefugee");
            $table->smallInteger("ofWomenReturnee");
            $table->enum("ofWomenDisabilityType", ["personWithDisability", "personWithoutDisability"]);
            $table->smallInteger("ofBoyHostCommunity");
            $table->smallInteger("ofBoyIdp");
            $table->smallInteger("ofBoyRefugee");
            $table->smallInteger("ofBoyReturnee");
            $table->enum("ofBoyDisabilityType", ["personWithDisability", "personWithoutDisability"]);
            $table->smallInteger("ofGirlHostCommunity");
            $table->smallInteger("ofGirlIdp");
            $table->smallInteger("ofGirlRefugee");
            $table->smallInteger("ofGirlReturnee");
            $table->enum("ofGirlDisabilityType", ["personWithDisability", "personWithoutDisability"]);
            $table->string("remark");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psychoeducations');
    }
};
