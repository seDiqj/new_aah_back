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
use App\Http\Controllers\Isp3Controller;
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

    // Beneficiary
    Route::post("/beneficiary/change_apr_included/{id}", [GlobalController::class, "changeBeneficiaryAprIncluded"]);

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
    Route::get("/get_project_finalizers_details/{id}", [ProjectsController::class, "getProjectFinalizersDetails"]);
    Route::post("/", [ProjectsController::class, "storeProject"])->middleware("permission:Project.create");
    Route::post("/", [ProjectsController::class, "storeProject"])->middleware("permission:Project.create");
    Route::post("/{id}", [ProjectsController::class, "updateProject"]);
    Route::post("/d/delete_projects", [ProjectsController::class, "destroyProject"])->middleware("permission:Project.delete");
    Route::post("/submit_new", [ProjectsController::class, "submitNewDatabase"]);
    Route::post("/status/change_apr_status/{id}", [ProjectsController::class, "changeAprStatus"]);

    // Outcomes Routes.
    Route::get("/outcomes/{id}", [ProjectsController::class, "index"]);
    Route::post("/o/outcome", [OutcomeController::class, "store"]);
    Route::put("/outcome/{id}", [OutcomeController::class, "update"]);
    Route::delete("/outcome/{id}", [OutcomeController::class, "destroy"]);

    // Outputs Routes.
    Route::get("/outputs/{id}", [ProjectsController::class, "indexProjectOutputs"]);
    Route::post("/o/output", [OutputController::class, "store"]);
    Route::put("/output/{id}", [OutputController::class, "update"]);
    Route::delete("/output/{id}", [OutputController::class, "destroyOutput"]);

    // Indecators / Activities Routes.
    Route::get("/indicators/{id}", [ProjectsController::class, "indexProjectIndicators"]);
    Route::get("/indicators/{databaseName}/{id}", [ProjectsController::class, "indixProjectSpicificDatabaseIndicator"]);
    Route::get("/indicator/{id}", [IndicatorController::class, "showIndicator"]);
    Route::post("/i/indicator", [IndicatorController::class, "store"]);
    Route::put("/indicator/{id}", [IndicatorController::class, "update"]);
    Route::put("/indicator/change_status/{id}", [IndicatorController::class, "changeIndicatorStatus"]);
    Route::delete("/indicator/{id}", [IndicatorController::class, "destroy"]);

    // Dessagregations Routes.
    Route::get("/disaggregations/{id}", [DessaggregationController::class, "indexProjectDisaggregations"]);
    Route::post("/d/disaggregation", [DessaggregationController::class, "store"]);
    Route::put("/dissaggregation", [DessaggregationController::class, "update"]);
    Route::delete("/disaggregation/{id}", [DessaggregationController::class, "destroy"]);

    // isp3
    Route::post("/is/isp3", [Isp3Controller::class, "store"]);
});

// permissions done
Route::prefix("main_db")->name("mainDb.")->middleware(["auth:sanctum"])->group(function () {

    Route::get("/beneficiaries", [MainDatabaseController::class, "indexBeneficiaries"])->middleware("permission:Maindatabase.view");
    Route::get("/beneficiary/mealtools/{id}", [MainDatabaseController::class, "indexBeneficiaryMealtools"])->middleware("permission:Maindatabase.view");
    Route::get("/beneficiary/{id}", [MainDatabaseController::class, "showBeneficiary"])->middleware("permission:Maindatabase.view");
    Route::get("/beneficiary/mealtool/{id}", [MainDatabaseController::class, "indexBeneficiaryMealtools"])->middleware("permission:Maindatabase.view");
    Route::get("/beneficiary/evaluation/{id}", [MainDatabaseController::class, "showBeneficiaryEvaluation"])->middleware("permission:Maindatabase.view");
    Route::get("/program/{id}", [MainDatabaseController::class, "showBeneficiaryProgram"])->middleware("permission:Maindatabase.view");
    Route::get("/indicators/{id}", [MainDatabaseController::class, "indexIndicators"])->middleware("permission:Maindatabase.view");
    Route::post("/beneficiaries/referrBeneficiaries", [MainDatabaseController::class, "referrBeneficiaries"])->middleware("permission:Maindatabase.view");
    Route::post("/beneficiary", [MainDatabaseController::class, "storeBeneficiary"])->middleware("permission:Maindatabase.create");
    Route::post("/beneficiary/mealtools/{id}", [MainDatabaseController::class, "storeMealtool"])->middleware("permission:Maindatabase.create");
    Route::post("/beneficiary/evaluation/{id}", [MainDatabaseController::class, "storeBeneficiaryEvaluation"])->middleware("permission:Maindatabase.create");
    Route::post("/reffer_beneficiary/{id}", [MainDatabaseController::class, "refferBeneficiary"])->middleware("permission:Maindatabase.view");
    Route::post("/beneficiary/add_to_kit_list/{id}", [MainDatabaseController::class, "addBeneficiaryToKitList"])->middleware("permission:Maindatabase.view");
    Route::post("/beneficiary/includeOrExcludeBeneficiaryToOrFromAPR/{newState}", [MainDatabaseController::class, "includeOrExcludeBeneficiaryToOrFromAPR"])->middleware("permission:Maindatabase.view");
    Route::post("/beneficiary/change_status/{id}", [MainDatabaseController::class, "changeBeneficiaryStatus"])->middleware("permission:Maindatabase.view");
    Route::post("/delete_beneficiaries", [MainDatabaseController::class, "destroyBeneficiary"])->middleware("permission:Maindatabase.delete");
    Route::post("/sessions/{id}", [MainDatabaseController::class, "storeSessions"])->middleware("permission:Maindatabase.create");
    Route::put("/beneficiary/{id}", [MainDatabaseController::class, "updateBeneficiary"])->middleware("permission:Maindatabase.edit");
    Route::put("/beneficiary/mealtool/{id}", [MainDatabaseController::class, "updateMealtool"])->middleware("permission:Maindatabase.edit");
    Route::put("/beneficiary/evaluation/{id}", [MainDatabaseController::class, "updateBeneficiaryEvaluation"])->middleware("permission:Maindatabase.edit");
    Route::delete("/beneficiary/sessions/delete_session/{id}", [MainDatabaseController::class, "destroySession"])->middleware("permission:Maindatabase.delete");
    Route::delete("/beneficiary/mealtool/{id}", [MainDatabaseController::class, "destroyMealtool"])->middleware("permission:Maindatabase.delete");

});

// permissions done
Route::prefix("kit_db")->name("kit_db.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/beneficiaries", [KitDatabaseController::class, "indexBeneficiaries"])->middleware("permission:Kit.view");
    Route::get("/bnf_kits/{id}", [KitDatabaseController::class, "indexBeneficiaryKitList"])->middleware("permission:Kit.view");
    Route::get("/kit_list", [KitDatabaseController::class, "indexKitList"])->middleware("permission:Kit.view");
    Route::get("/beneficiary/{id}", [KitDatabaseController::class, "showBeneficiary"])->middleware("permission:Kit.view");
    Route::get("/program/{id}", [KitDatabaseController::class, "showBeneficiaryProgram"])->middleware("permission:Kit.view");
    Route::get("/show_kit/{id}", [KitDatabaseController::class, "showKit"])->middleware("permission:Kit.view");
    Route::post("/beneficiary", [KitDatabaseController::class, "storeBeneficiary"]);
    Route::post("/kit", [KitDatabaseController::class, "storeKit"])->middleware("permission:Kit.create");
    Route::post("/delete_kit", [KitDatabaseController::class, "destroyKits"])->middleware("permission:Kit.delete");
    Route::post("/add_kit_to_bnf/{id}", [KitDatabaseController::class, "addNewKitToBeneficiary"])->middleware("permission:Kit.create");
    Route::put("/beneficiary/{id}", [KitDatabaseController::class, "updateBeneficiary"])->middleware("permission:Kit.edit");
    Route::put("/kit/{id}", [MainDatabaseController::class, "updateKit"])->middleware("permission:Kit.edit");
    Route::post("/delete_beneficiaries", [KitDatabaseController::class, "destroyBeneficiary"])->middleware("permission:Kit.delete");
});

// permissions done need full review
Route::prefix("community_dialogue_db")->name("community_dialogue_db.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/beneficiaries", [CommunityDialogueDatabaseController::class, "indexBeneficiaries"])->middleware("permission:Dialogue.view");
    Route::get("/community_dialogues", [CommunityDialogueDatabaseController::class, "indexCommunityDialogues"])->middleware("permission:Dialogue.view");
    Route::get("/beneficiary/sessions/{id}", [CommunityDialogueDatabaseController::class, "indexBeneficirySessions"])->middleware("permission:Dialogue.view");
    Route::get("/community_dialogue_for_edit/{id}", [CommunityDialogueDatabaseController::class, "showCommunityDialogue"]);
    Route::get("/beneficiary/{id}", [CommunityDialogueDatabaseController::class, "showBeneficiary"])->middleware("permission:Dialogue.view");
    Route::get("/community_dialogues/for_selection", [CommunityDialogueDatabaseController::class, "indexCommunityDialoguesForSelection"])->middleware("permission:Dialogue.view");
    Route::get("/community_dialogue/sessions/{id}", [CommunityDialogueDatabaseController::class, "indexCdSessions"])->middleware("permission:Dialogue.view");
    Route::get("/community_dialogue/groups/beneficiaries/{id}", [CommunityDialogueDatabaseController::class, "indexCommunityDialogueGroupBeneficiaries"])->middleware("permission:Dialogue.view");
    Route::get("/community_dialogue/{id}", [CommunityDialogueDatabaseController::class, "showCD"])->middleware("permission:Dialogue.view");
    Route::get("/community_dialogue/session/{id}", [CommunityDialogueDatabaseController::class, "showSession"])->middleware("permission:Dialogue.view");
    Route::post("/beneficiary", [CommunityDialogueDatabaseController::class, "storeBeneficiary"])->middleware("permission:Dialogue.create_beneficiary");
    Route::post("/beneficiaries/toggle_presence/{id}", [CommunityDialogueDatabaseController::class, "togglePresence"])->middleware("permission:Dialogue.view");
    Route::post("/community_dialogue", [CommunityDialogueDatabaseController::class, "storeCommunityDialogue"])->middleware("permission:Dialogue.create");
    Route::post("/community_dialogue/session", [CommunityDialogueDatabaseController::class, "createNewSession"])->middleware("permission:Dialogue.create");
    Route::post("/beneficiaries/add_community_dialogue", [CommunityDialogueDatabaseController::class, "addCommunityDialogueToBeneficiaries"])->middleware("permission:Dialogue.assign");
    Route::put("/beneficiary/{id}", [CommunityDialogueDatabaseController::class, "updateBeneficiary"])->middleware("permission:Dialogue.edit");
    Route::put("/community_dialogue/{id}", [CommunityDialogueDatabaseController::class, "updateCommunityDialogue"])->middleware("permission:Dialogue.edit");
    Route::put("/community_dialogue/session/{id}", [CommunityDialogueDatabaseController::class, "updateSession"])->middleware("permission:Dialogue.edit");
    Route::post("/delete_beneficiary_sessions/{id}", [CommunityDialogueDatabaseController::class, "destroyBeneficiarySessions"])->middleware("permission:Dialogue.delete");
    Route::post("/delete_cds", [CommunityDialogueDatabaseController::class, "destroyCommunityDialogue"])->middleware("permission:Dialogue.delete");
    Route::post("/community_dialogue_db/community_dialogue/sessions/delete_sessions", [CommunityDialogueDatabaseController::class, "destroySessions"])->middleware("permission:Dialogue.delete");
    Route::post("/delete_beneficiaries", [CommunityDialogueDatabaseController::class, "destroyBeneficiaries"])->middleware("permission:Dialogue.delete");
});

// permissions done
Route::prefix("psychoeducation_db")->name("psychoeducation_db.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/psychoeducations", [PsychoeducationDatabaseController::class, "index"])->middleware("permission:Psychoeducation.view");
    Route::get("/psychoeducation/{id}", [PsychoeducationDatabaseController::class, "show"])->middleware("permission:Psychoeducation.view");
    Route::post("/psychoeducation", [PsychoeducationDatabaseController::class, "store"])->middleware("permission:Psychoeducation.create");
    Route::put("/psychoeducation/{id}", [PsychoeducationDatabaseController::class, "update"])->middleware("permission:Psychoeducation.edit");
    Route::post("/delete_psychoeducations", [PsychoeducationDatabaseController::class, "destroy"])->middleware("permission:Psychoeducation.delete");
});

// permissions done
Route::prefix("training_db")->name("training_db.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/trainings", [TrainingController::class, "index"])->middleware("permission:Training.view");
    Route::get("/beneficiaries", [TrainingController::class, "indexBeneficiaries"])->middleware("permission:Training.view");
    Route::get("/beneficiary/trainings/{id}", [TrainingController::class, "indexBeneficiaryTrainings"])->middleware("permission:Training.view");
    Route::get("/trainings/for_selection", [TrainingController::class, "indexTrainingsForSelection"])->middleware("permission:Training.view");
    Route::get("/training/{id}", [TrainingController::class, "show"])->middleware("permission:Training.view");
    Route::get("/beneficiary/{id}", [TrainingController::class, "showBeneficiary"])->middleware("permission:Training.view");
    Route::get("/beneficiary/chapter/preAndPostTest/{bnfId}/{chapterId}", [TrainingController::class, "showBeneficiaryChapterPreAndPostTestScores"])->middleware("permission:Training.view");
    Route::post("/training", [TrainingController::class, "store"])->middleware("permission:Training.create");
    Route::post("/beneficiary", [TrainingController::class, "storeNewBeneficiary"])->middleware("permission:Training.create");
    Route::post("/beneficiaries/add_training", [TrainingController::class, "addTrainingToBeneficiaries"])->middleware("permission:Training.assign_training");
    Route::put("/beneficiary/chapter/setPrecense/{bnfId}/{chapterId}", [TrainingController::class, "togglePresence"])->middleware("permission:Training.view");
    Route::put("/beneficiary/chapter/setPreAndPostTest/{bnfId}/{chapterId}", [TrainingController::class, "setPreAndPostTest"])->middleware("permission:Training.view");
    Route::put("/training/{id}", [TrainingController::class, "update"])->middleware("permission:Training.view");
    Route::put("/beneficiary/{id}", [TrainingController::class, "updateBeneficiary"])->middleware("permission:Training.view");
    Route::post("/training/chapter/{id}", [TrainingController::class, "storeNewChapter"])->middleware("permission:Training.create");
    Route::post("/delete_trainings", [TrainingController::class, "destroy"])->middleware("permission:Training.delete");
    Route::post("/delete_beneficiaries", [TrainingController::class, "destroyBeneficiaries"])->middleware("permission:Training.delete");
    Route::post("/training/evaluation/{id}", [TrainingController::class, "storeTrainingEvaluation"])->middleware("permission:Training.create");
});

// permissions done (refferBeneficiary route needs review)
Route::prefix("referral_db")->name("referral_db.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/beneficiaries", [ReferralDatabaseController::class, "index"])->middleware("permission:Referral.view");
    Route::get("/beneficiary/{id}", [ReferralDatabaseController::class, "show"])->middleware("permission:Referral.view");
    Route::put("/beneficiary/updateReferral/{id}", [ReferralDatabaseController::class, "update"])->middleware("permission:Referral.edit");
    Route::post("/delete_beneficiaries", [ReferralDatabaseController::class, "destroy"])->middleware("permission:Referral.delete");
});

// Does not have any permissions.
Route::prefix("enact_database")->name("enact_database.")->middleware(["auth:sanctum"])->group(function () {
    Route::get("/", [EnactController::class, "index"]);
    Route::get("/assessments_list", [EnactController::class, "indexAssessmentsList"]);
    Route::get("/show_for_profile/{id}", [EnactController::class, "showForProfile"]);
    Route::get("/{id}", [EnactController::class, "show"]);
    Route::post("/assess_assessment", [EnactController::class, "assessAssessment"]);
    Route::post("/", [EnactController::class, "store"]);
    Route::put("/{id}", [EnactController::class, "update"]);
    Route::post("/delete_enacts", [EnactController::class, "destroy"]);
});

// permissions done (need quik review)
Route::prefix("user_mng")->name("user_mng.")->middleware("auth:sanctum")->group(function () {
    // User Routes.
    Route::get("/user/me", [UserController::class, "me"]);
    Route::get("/users", [UserController::class, "index"])->middleware("permission:List User");
    Route::get("/user/{id}", [UserController::class, "show"])->middleware("permission:View User");
    Route::get("/permissionsForAuth/{id}", [UserController::class, "getSystemAndUserPermissions"]);
    Route::post("/user", [UserController::class, "store"])->middleware("permission:Create User");
    Route::post("/delete_users", [UserController::class, "destroy"])->middleware("permission:Delete User");
    Route::post("/edit_user/{id}", [UserController::class, "update"])->middleware("permission:Edit User");
    Route::put("/user/change_password/{id}", [UserController::class, "changeUserPassword"]);

    // Role Routes.
    Route::get("/roles", [RoleController::class, "index"])->middleware("permission:List Role");
    Route::get("/role/{id}", [RoleController::class, "show"])->middleware("permission:View Role");
    Route::post("/role", [RoleController::class, "store"])->middleware("permission:Create Role");
    Route::post("/delete_role", [RoleController::class, "destroy"])->middleware("permission:Delete Role");
    Route::put("/role/{id}", [RoleController::class, "update"])->middleware("permission:Edit Role");

    // Permission Routes.
    Route::get("/permissions", [PermissionController::class, "index"]);
    Route::get("/table_permissions", [PermissionController::class, "indexPermissions"]);
    Route::get("/permissionsForAuth", [PermissionController::class, "indexPermissionsForFrontAuthintication"]);
    // Route::get("/permission/{id}", [PermissionController::class, "show"]);
    // Route::post("/permission/delete_permissions", [PermissionController::class, "destroy"]);
    // Route::put("/permission/{id}", [PermissionController::class, "update"]);

    // Roles && Permissions
    Route::get("/permissions_&_roles", [UserController::class, "getAllRolesAndPermissions"]);
});

// Departments Routes.
Route::prefix("departments")->name("departments.")->middleware(["auth"])->group(function () {
    Route::get("/", [DepartmentsController::class, "index"]);
    Route::get("/{id}", [DepartmentsController::class, "show"]);
    Route::post("/", [DepartmentsController::class, "store"]);
    Route::put("/{id}", [DepartmentsController::class, "update"]);
    Route::delete("/{id}", [DepartmentsController::class, "destroy"]);
});

// permission done needs full review
Route::prefix("db_management")->name("db_management.")->middleware("auth:sanctum")->group(function () {
    Route::get("/submitted_databases", [DatabaseController::class, "indexSubmittedDatabases"])->middleware("permission:Database_submission.view");
    Route::get("/first_approved_databases", [DatabaseController::class, "indexFirstApprovedDatabases"])->middleware("permission:Database_submission.view");
    Route::get("/show_database/{id}", [DatabaseController::class, "showSubmittedDatabase"])->middleware("permission:Database_submission.view");
    Route::post("/change_db_status/{id}", [DatabaseController::class, "changeDatabaseStatus"])->middleware("permission:Database_submission.view");
    Route::post("/submit_new_database", [DatabaseController::class, "submitNewDatabase"])->middleware("permission:Database_submission.create");
    Route::post("/deleted_submitted_databases", [DatabaseController::class, "destroySubmittedDatabases"])->middleware("permission:Database_submission.delete");
    Route::post("/deleted_first_approved_databases", [DatabaseController::class, "destroyFirstApprovedDatabases"])->middleware("permission:Database_submission.delete");
});

// permission done needs full review
Route::prefix("apr_management")->name("apr_management.")->middleware("auth:sanctum")->group(function () {
    Route::get("/show_apr/{id}", [AprController::class, "showGeneratedApr"])->middleware("permission:Apr.review");
    Route::get("/get_system_aprs_status", [AprController::class, "getSystemAprsStatus"])->middleware("permission:Apr.view/list");
    Route::post("/generate_apr/{id}", [AprController::class, "generateApr"])->middleware("permission:Database_submission.generate_apr");
});

// Permissions done
Route::prefix("filter")->name("filter.")->middleware("auth:sanctum")->group(function () {
    Route::post("/projects", [FilterTablesController::class, "filterProjects"])->middleware("permission:Project.view");
    Route::post("/main_database/beneficiaries", [FilterTablesController::class, "filterMainDbBnf"])->middleware("permission:Maindatabase.view");
    Route::post("/main_database/program", [FilterTablesController::class, "filterMainDbPrograms"])->middleware("permission:Maindatabase.view");
    Route::post("/kit_database/beneficiaries", [FilterTablesController::class, "filterKitDbBnf"])->middleware("permission:Kitdatabase.view");
    Route::post("/kit_database/program", [FilterTablesController::class, "filterKitDbPrograms"])->middleware("permission:Kitdatabase.view");
    Route::post("/cd_database/beneficiaries", [FilterTablesController::class, "filterCdDbBnf"])->middleware("permission:Dialogue.view");
    Route::post("/psychoeducation_db/psychoeducations", [FilterTablesController::class, "filterPsychoeducations"])->middleware("permission:Psychoeducation.view");
    Route::post("/cd_database/cds", [FilterTablesController::class, "filterCds"])->middleware("permission:Dialogue.view");
    Route::post("/training_database/trainings", [FilterTablesController::class, "filterTrainings"])->middleware("permission:Training.view");
    Route::post("/training_database/beneficiaries", [FilterTablesController::class, "filterTrainingDatabaseBnf"])->middleware("permission:Training.view");
    Route::post("/refferal_database/beneficiaries", [FilterTablesController::class, "filterRereralDatabaseBnf"])->middleware("permission:Training.view");
    Route::post("/enact_database/enacts", [FilterTablesController::class, "filterEnacts"]);
    Route::post("/users", [FilterTablesController::class, "filterUsers"])->middleware("permission:List User");
    Route::post("/roles", [FilterTablesController::class, "filterRoles"])->middleware("permission:List Role");
    Route::post("/permissions", [FilterTablesController::class, "filterPermissoins"])->middleware("permission:List Role");
    Route::post("/submitted_databases", [FilterTablesController::class, "filterSubmittedDatabases"])->middleware("permission:List Role");
    Route::post("/approved_databases", [FilterTablesController::class, "filterApprovedDatabases"])->middleware("permission:List Role");
    Route::post("/reviewed_aprs", [FilterTablesController::class, "filterReviwedAprs"])->middleware("permission:List Role");
    Route::post("/aprroved_aprs", [FilterTablesController::class, "filterApprovedAprs"])->middleware("permission:List Role");
});
