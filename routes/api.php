<?php

use App\Http\Controllers\AprController;
use App\Http\Controllers\AprGeneratorController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CommunityDialogueDatabaseController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DepartmentsController;
use App\Http\Controllers\DessaggregationController;
use App\Http\Controllers\EnactController;
use App\Http\Controllers\FilterTablesController;
use App\Http\Controllers\GlobalController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\KitDatabaseController;
use App\Http\Controllers\MainDatabaseController;
use App\Http\Controllers\OutcomeController;
use App\Http\Controllers\OutputController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\PsychoeducationDatabaseController;
use App\Http\Controllers\ReferralDatabaseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\UserController;
use App\Models\Enact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get("/test/{project_id}/{database_id}/{province_id}/{fromDate}/{endDate}", [AprGeneratorController::class, "generate"]);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix("authentication")->name("auth.")->group(function () {
    Route::post("/login", [AuthenticationController::class, "login"]);
    Route::post("/logout", [AuthenticationController::class, "logout"]);
});

Route::prefix("global")->name("global.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/beneficiary/indicators/{id}", [GlobalController::class, "indexBeneficiaryIndicators"]);
    // Programs Routes
    Route::get("/programs/{databaseName}", [ProgramController::class, "index"]);
    Route::get("/program/{id}", [ProgramController::class, "show"]);
    Route::post("/program/{databaseName}", [ProgramController::class, "store"]);
    Route::put("/program/{id}", [ProgramController::class, "update"]);
    Route::post("/delete_programs", [ProgramController::class, "destroy"]);
    Route::get("/database/projects/indicators/{db}", [GlobalController::class, "indexDatabaseIndicators"]);

    // Districts routes.
    Route::get("/districts", [GlobalController::class, "indexDistricts"]);

    // Provinces routes.
    Route::get("/provinces", [GlobalController::class, "indexProvinces"]);

    // Projects routes.
    Route::get("/projects", [GlobalController::class, "indexProjects"]);

    // Programs.
    Route::get("/programsForSelection/{databaseName}", [GlobalController::class, "indexProgramsForSelection"]);

    // Indicators routes.
    Route::get("/indicators/{databaseName}", [GlobalController::class, "indexDatabaseIndicators"]);

});


Route::prefix("projects")->name("projects.")->middleware(["auth:sanctum"])->group(function () {
    // Project Routes
    Route::get("/", [ProjectsController::class, "indexProjects"]);
    Route::get("/p/{databaseName}", [ProjectsController::class, "indexProjectsThatHasAtleastOneIndicatorWhichBelongsToSpicificDatabase"]);
    Route::get("/{id}", [ProjectsController::class, "showProject"])->whereNumber(["id"]);
    Route::get("/logs/{id}", [ProjectsController::class, "indexProjectLogs"]);
    Route::get("/projects_for_submition", [ProjectsController::class, "indexProjectsForSubmitting"]);
    Route::get("/project_databases_&_provinces/{id}", [ProjectsController::class, "indexProjectNecessaryDataForSubmition"]);
    Route::get("/provinces/{id}", [ProjectsController::class, "indexProjectProvinces"]);
    Route::post("/", [ProjectsController::class, "storeProject"]);
    Route::post("/delete_projects", [ProjectsController::class, "destroyProject"]);
    Route::post("/submit_new", [ProjectsController::class, "submitNewDatabase"]);
    Route::post("/status/change_apr_status/{id}", [ProjectsController::class, "changeAprStatus"]);
    Route::put("/{id}", [ProjectsController::class, "updateProjectFull"]);

    // Outcomes Routes.
    Route::get("/outcomes/{id}", [ProjectsController::class, "index"]);
    Route::post("/outcome", [OutcomeController::class, "store"]);
    Route::put("/outcome/{id}", [OutcomeController::class, "update"]);
    Route::delete("/outcome/{id}", [OutcomeController::class, "destroy"]);

    // Outputs Routes.
    Route::get("/outputs/{id}", [ProjectsController::class, "indexProjectOutputs"]);
    Route::post("/output", [OutputController::class, "store"]);
    Route::put("/output/{id}", [OutputController::class, "updateOutput"]);
    Route::delete("/output/{id}", [OutputController::class, "destroyOutput"]);

    // Indecators / Activities Routes.
    Route::get("/indicators/{id}", [ProjectsController::class, "indexProjectIndicators"]);
    Route::get("/indicators/{databaseName}/{id}", [ProjectsController::class, "indixProjectSpicificDatabaseIndicator"]);
    Route::get("/indicator/{id}", [IndicatorController::class, "showIndicator"]);
    Route::post("/indicator", [IndicatorController::class, "store"]);
    Route::put("/indicator/{id}", [IndicatorController::class, "update"]);
    Route::put("/indicator/change_status/{id}", [IndicatorController::class, "changeIndicatorStatus"]);
    Route::delete("/indicator/{id}", [IndicatorController::class, "destroy"]);

    // Dessagregations Routes.
    Route::get("/disaggregations/{id}", [DessaggregationController::class, "indexProjectDisaggregations"]);
    Route::post("/disaggregation", [DessaggregationController::class, "store"]);
    Route::delete("/disaggregation/{id}", [DessaggregationController::class, "destroy"]);
});


Route::prefix("main_db")->name("mainDb.")->middleware(["auth:sanctum"])->group(function () {

    Route::get("/beneficiaries", [MainDatabaseController::class, "indexBeneficiaries"]);
    Route::get("/beneficiary/mealtools/{id}", [MainDatabaseController::class, "indexBeneficiaryMealtools"]);
    Route::get("/beneficiary/{id}", [MainDatabaseController::class, "showBeneficiary"]);
    Route::get("/beneficiary/mealtool/{id}", [MainDatabaseController::class, "indexBeneficiaryMealtools"]);
    Route::get("/beneficiary/evaluation/{id}", [MainDatabaseController::class, "showBeneficiaryEvaluation"]);
    Route::get("/program/{id}", [MainDatabaseController::class, "showBeneficiaryProgram"]);
    Route::get("/indicators/{id}", [MainDatabaseController::class, "indexIndicators"]);
    Route::post("/beneficiary", [MainDatabaseController::class, "storeBeneficiary"]);
    Route::post("/beneficiary/mealtools/{id}", [MainDatabaseController::class, "storeMealtool"]);
    Route::post("/beneficiary/evaluation/{id}", [MainDatabaseController::class, "storeBeneficiaryEvaluation"]);
    Route::post("/reffer_beneficiary/{id}", [MainDatabaseController::class, "refferBeneficiary"]);
    Route::post("/beneficiary/add_to_kit_list/{id}", [MainDatabaseController::class, "addBeneficiaryToKitList"]);
    Route::post("/beneficiary/includeOrExcludeBeneficiaryToOrFromAPR/{newState}", [MainDatabaseController::class, "includeOrExcludeBeneficiaryToOrFromAPR"]);
    Route::post("/beneficiary/change_status/{id}", [MainDatabaseController::class, "changeBeneficiaryStatus"]);
    Route::post("/delete_beneficiaries", [MainDatabaseController::class, "destroyBeneficiary"]);
    Route::post("/sessions/{id}", [MainDatabaseController::class, "storeSessions"]);
    Route::put("/beneficiary/{id}", [MainDatabaseController::class, "updateBeneficiary"]);
    Route::put("/beneficiary/mealtool/{id}", [MainDatabaseController::class, "updateMealtool"]);
    Route::put("/beneficiary/evaluation/{id}", [MainDatabaseController::class, "updateBeneficiaryEvaluation"]);
    Route::delete("/beneficiary/sessions/delete_session/{id}", [MainDatabaseController::class, "destroySession"]);
    Route::delete("/beneficiary/mealtool/{id}", [MainDatabaseController::class, "destroyMealtool"]);

});


Route::prefix("kit_db")->name("kit_db.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/beneficiaries", [KitDatabaseController::class, "indexBeneficiaries"]);
    Route::get("/bnf_kits/{id}", [KitDatabaseController::class, "indexBeneficiaryKitList"]);
    Route::get("/kit_list", [KitDatabaseController::class, "indexKitList"]);
    Route::get("/beneficiary/{id}", [KitDatabaseController::class, "showBeneficiary"]);
    Route::get("/program/{id}", [KitDatabaseController::class, "showBeneficiaryProgram"]);
    Route::get("/show_kit/{id}", [KitDatabaseController::class, "showKit"]);
    Route::post("/beneficiary", [KitDatabaseController::class, "storeBeneficiary"]);
    Route::post("/kit", [KitDatabaseController::class, "storeKit"]);
    Route::post("/add_kit_to_bnf/{id}", [KitDatabaseController::class, "addNewKitToBeneficiary"]);
    Route::put("/beneficiary/{id}", [KitDatabaseController::class, "updateBeneficiary"]);
    Route::put("/kit/{id}", [MainDatabaseController::class, "updateKit"]);
    Route::post("/delete_beneficiaries", [KitDatabaseController::class, "destroyBeneficiary"]);
    Route::delete("/kit/{id}", [KitDatabaseController::class, "destroyKit"]);
});


Route::prefix("community_dialogue_db")->name("community_dialogue_db.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/beneficiaries", [CommunityDialogueDatabaseController::class, "indexBeneficiaries"]);
    Route::get("/community_dialogues", [CommunityDialogueDatabaseController::class, "indexCommunityDialogues"]);
    Route::get("/beneficiary/sessions/{id}", [CommunityDialogueDatabaseController::class, "indexBeneficirySessions"]);
    Route::get("/beneficiary/{id}", [CommunityDialogueDatabaseController::class, "showBeneficiary"]);
    Route::get("/community_dialogues/for_selection", [CommunityDialogueDatabaseController::class, "indexCommunityDialoguesForSelection"]);
    Route::get("/community_dialogue/sessions/{id}", [CommunityDialogueDatabaseController::class, "indexCdSessions"]);
    Route::get("/community_dialogue/groups/beneficiaries/{id}", [CommunityDialogueDatabaseController::class, "indexCommunityDialogueGroupBeneficiaries"]);
    Route::get("/community_dialogue/{id}", [CommunityDialogueDatabaseController::class, "showCD"]);
    Route::post("/beneficiary", [CommunityDialogueDatabaseController::class, "storeBeneficiary"]);
    Route::post("/community_dialogue", [CommunityDialogueDatabaseController::class, "storeCommunityDialogue"]);
    Route::post("/beneficiaries/add_community_dialogue", [CommunityDialogueDatabaseController::class, "addCommunityDialogueToBeneficiaries"]);
    Route::put("/beneficiary/{id}", [CommunityDialogueDatabaseController::class, "updateBeneficiary"]);
    Route::post("/delete_beneficiary_sessions/{id}", [CommunityDialogueDatabaseController::class, "destroyBeneficiarySessions"]);
    Route::post("/delete_cds", [CommunityDialogueDatabaseController::class, "destroyCommunityDialogues"]);
    Route::post("/community_dialogue_db/community_dialogue/sessions/delete_sessions", [CommunityDialogueDatabaseController::class, "destroySessions"]);
    Route::post("/delete_beneficiaries", [CommunityDialogueDatabaseController::class, "destroyBeneficiaries"]);
});


Route::prefix("psychoeducation_db")->name("psychoeducation_db.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/psychoeducations", [PsychoeducationDatabaseController::class, "index"]);
    Route::get("/psychoeducation/{id}", [PsychoeducationDatabaseController::class, "show"]);
    Route::post("/psychoeducation", [PsychoeducationDatabaseController::class, "store"]);
    Route::put("/psychoeducation/{id}", [PsychoeducationDatabaseController::class, "update"]);
    Route::post("/delete_psychoeducations", [PsychoeducationDatabaseController::class, "destroy"]);
});

Route::prefix("training_db")->name("training_db.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/trainings", [TrainingController::class, "index"]);
    Route::get("/beneficiaries", [TrainingController::class, "indexBeneficiaries"]);
    Route::get("/beneficiary/trainings/{id}", [TrainingController::class, "indexBeneficiaryTrainings"]);
    Route::get("/trainings/for_selection", [TrainingController::class, "indexTrainingsForSelection"]);
    Route::get("/training/{id}", [TrainingController::class, "show"]);
    Route::get("/beneficiary/chapter/preAndPostTest/{bngId}/{chapterId}", [TrainingController::class, "showBeneficiaryChapterPreAndPostTestScores"]);
    Route::post("/beneficiary", [TrainingController::class, "storeNewBeneficiary"]);
    Route::post("/beneficiaries/add_training", [TrainingController::class, "addTrainingToBeneficiaries"]);
    Route::put("/beneficiary/chapter/setPrecense/{bnfId}/{chapterId}", [TrainingController::class, "togglePresence"]);
    Route::put("/beneficiary/chapter/setPreAndPostTest/{bnfId}/{chapterId}", [TrainingController::class, "setPreAndPostTest"]);
    Route::post("/training", [TrainingController::class, "store"]);
    Route::post("/training/chapter/{id}", [TrainingController::class, "storeNewChapter"]);
    Route::post("/delete_trainings", [TrainingController::class, "destroy"]);
    Route::post("/delete_beneficiaries", [TrainingController::class, "destroyBeneficiaries"]);
    Route::post("/training/evaluation/{id}", [TrainingController::class, "storeTrainingEvaluation"]);
});

Route::prefix("referral_db")->name("referral_db.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/beneficiaries", [ReferralDatabaseController::class, "index"]);
    Route::get("/beneficiary/{id}", [ReferralDatabaseController::class, "show"]);
    Route::post("/beneficiaries/referrBeneficiaries", [ReferralDatabaseController::class, "referrBeneficiaries"]);
    Route::put("/beneficiary/updateReferral/{id}", [ReferralDatabaseController::class, "update"]);
    Route::post("/delete_beneficiaries", [ReferralDatabaseController::class, "destroy"]);
});

Route::prefix("enact_database")->name("enact_database.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/", [EnactController::class, "index"]);
    Route::get("/assessments_list", [EnactController::class, "indexAssessmentsList"]);
    Route::get("/{id}", [EnactController::class, "show"]);
    Route::post("/assess_assessment", [EnactController::class, "assessAssessment"]);
    Route::post("/", [EnactController::class, "store"]);
    Route::put("/{id}", [EnactController::class, "update"]);
    Route::post("/delete_enacts", [EnactController::class, "destroy"]);
});

Route::prefix("user_mng")->name("user_mng.")->middleware("auth:sanctum")->group(function () {
    // User Routes.
    Route::get("/users", [UserController::class, "index"]);
    Route::get("/user/me", [UserController::class, "me"]);
    Route::get("/user/{id}", [UserController::class, "show"]);
    Route::post("/user", [UserController::class, "store"]);
    Route::post("/delete_users", [UserController::class, "destroy"]);
    Route::post("/user/{id}", [UserController::class, "update"]);

    // Role Routes.
    Route::get("/roles", [RoleController::class, "index"]);
    Route::get("/role/{id}", [RoleController::class, "show"]);
    Route::post("/role", [RoleController::class, "store"]);
    Route::post("/delete_role", [RoleController::class, "destroy"]);
    Route::put("/role/{id}", [RoleController::class, "update"]);

    // Permission Routes.
    Route::get("/permissions", [PermissionController::class, "index"]);
    Route::get("/permissionsForAuth", [PermissionController::class, "indexPermissionsForFrontAuthintication"]);
    Route::get("/permission/{id}", [PermissionController::class, "show"]);
    Route::post("/permission/delete_permissions", [PermissionController::class, "destroy"]);
    Route::put("/permission/{id}", [PermissionController::class, "update"]);
});

// Departments Routes.
Route::prefix("departments")->name("departments.")->middleware(["auth"])->group(function () {
    Route::get("/", [DepartmentsController::class, "index"]);
    Route::get("/{id}", [DepartmentsController::class, "show"]);
    Route::post("/", [DepartmentsController::class, "store"]);
    Route::put("/{id}", [DepartmentsController::class, "update"]);
    Route::delete("/{id}", [DepartmentsController::class, "destroy"]);
});


Route::prefix("db_management")->name("db_management.")->middleware("auth:sanctum")->group(function () {
    Route::get("/submitted_databases", [DatabaseController::class, "indexSubmittedDatabases"]);
    Route::get("/first_approved_databases", [DatabaseController::class, "indexFirstApprovedDatabases"]);
    Route::get("/show_database/{id}", [DatabaseController::class, "showSubmittedDatabase"]);
    Route::post("/change_db_status/{id}", [DatabaseController::class, "changeDatabaseStatus"]);
    Route::post("/submit_new_database", [DatabaseController::class, "submitNewDatabase"]);
    Route::post("/deleted_submitted_databases", [DatabaseController::class, "destroySubmittedDatabases"]);
    Route::post("/deleted_first_approved_databases", [DatabaseController::class, "destroyFirstApprovedDatabases"]);
});

Route::prefix("apr_management")->name("apr_management.")->middleware("auth:sanctum")->group(function () {
    Route::get("/show_apr/{id}", [AprController::class, "showGeneratedApr"]);
    Route::post("/generate_apr/{id}", [AprController::class, "generateApr"]);
});


Route::prefix("filter")->name("filter.")->middleware("auth:sanctum")->group(function () {
    // Filter Projects.
    Route::post("/projects", [FilterTablesController::class, "filterProjects"]);
});
