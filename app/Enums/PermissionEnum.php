<?php

namespace App\Enums;

enum PermissionEnum: string
{
    // ===================== User =====================
    case USER_LIST = 'List User';
    case USER_CREATE = 'Create User';
    case USER_EDIT = 'Edit User';
    case USER_VIEW = 'View User';
    case USER_DELETE = 'Delete User';
    case USER_EXPORT = 'Export User';

    // ===================== Role =====================
    case ROLE_LIST = 'List Role';
    case ROLE_CREATE = 'Create Role';
    case ROLE_EDIT = 'Edit Role';
    case ROLE_VIEW = 'View Role';
    case ROLE_DELETE = 'Delete Role';
    case ROLE_EXPORT = 'Export Role';

    // ===================== Category =====================
    case CATEGORY_LIST = 'List Category';
    case CATEGORY_CREATE = 'Create Category';
    case CATEGORY_EDIT = 'Edit Category';
    case CATEGORY_DELETE = 'Delete Category';

    // ===================== Grant Management =====================
    case GRANT_PROJECT_CREATE = 'Project.create';
    case GRANT_PROJECT_VIEW = 'Project.view';
    case GRANT_PROJECT_EDIT = 'Project.edit';
    case GRANT_PROJECT_DELETE = 'Project.delete';
    case GRANT_PROJECT_SUBMIT = 'Project.submit';
    case GRANT_PROJECT_GRANT_FINALIZE = 'Project.grantFinalize';
    case GRANT_PROJECT_HQ_FINALIZE = 'Project.HQFinalize';

    // ===================== Myspace Main Database (MHPSS) =====================
    case MHPSS_CREATE = 'Maindatabase.create';
    case MHPSS_VIEW = 'Maindatabase.view';
    case MHPSS_EDIT = 'Maindatabase.edit';
    case MHPSS_DELETE = 'Maindatabase.delete';
    case MHPSS_DOWNLOAD_EXCEL_REPORT = 'Maindatabase.download_excel_report';

    // ===================== Myspace Kit Distribution =====================
    case KIT_CREATE = 'Kit.create';
    case KIT_VIEW = 'Kit.view';
    case KIT_EDIT = 'Kit.edit';
    case KIT_DELETE = 'Kit.delete';
    case KIT_ASSIGN = 'Kit.assign';
    case KIT_DATABASE_DOWNLOAD_REPORT = 'Kit_database.download_excel_report';

    // ===================== Myspace Psychoeducation =====================
    case PSYCHO_CREATE = 'Psychoeducation.create';
    case PSYCHO_VIEW = 'Psychoeducation.view';
    case PSYCHO_EDIT = 'Psychoeducation.edit';
    case PSYCHO_DELETE = 'Psychoeducation.delete';
    case PSYCHO_DOWNLOAD_EXCEL = 'Psychoeducation.download_excel_report';

    // ===================== Myspace Community Dialogue =====================
    case DIALOGUE_CREATE = 'Dialogue.create';
    case DIALOGUE_VIEW = 'Dialogue.view';
    case DIALOGUE_EDIT = 'Dialogue.edit';
    case DIALOGUE_DELETE = 'Dialogue.delete';
    case DIALOGUE_ASSIGN = 'Dialogue.assign_community_dailogue';
    case DIALOGUE_CREATE_BENEFICIARY = 'Dialogue.create_beneficiary';
    case COMMUNITY_DIALOGUE_DOWNLOAD = 'Community_dailogue.download_excel_report';

    // ===================== Myspace Training Database =====================
    case TRAINING_CREATE = 'Training.create';
    case TRAINING_EDIT = 'Training.edit';
    case TRAINING_VIEW = 'Training.view';
    case TRAINING_DELETE = 'Training.delete';
    case TRAINING_ASSIGN = 'Training.assign_training';
    case TRAINING_DATABASE_DOWNLOAD = 'Training_database.download_excel_report';

    // ===================== Myspace Referral =====================
    case REFERRAL_CREATE = 'Referral.create';
    case REFERRAL_VIEW = 'Referral.view';
    case REFERRAL_EDIT = 'Referral.edit';
    case REFERRAL_DELETE = 'Referral.delete';
    case REFERRAL_EXPORT = 'Export.referral excel report';

    // ===================== Database Management =====================
    case DATABASE_CREATE = 'Database_submission .create';
    case DATABASE_VIEW = 'Dabase_submission.view';
    case DATABASE_EDIT = 'Database_submission.edit';
    case DATABASE_DELETE = 'Database_submission.delete';
    case DATABASE_APPROVE = 'Database_submission.approve';
    case DATABASE_GENERATE_APR = 'Datbase_submission.generate_apr';
    case DATABASE_DOWNLOAD = 'Database_submission.download';

    // ===================== APR Management =====================
    case APR_REVIEW = 'Apr.review';
    case APR_VIEW = 'Apr.view/list';
    case APR_MARK_AS_REVIEWED = 'Apr.mark_as_reviewed';
    case APR_VALIDATE = 'Apr.validate';
    case APR_DOWNLOAD = 'Apr.download';

    // ===================== Dashboard =====================
    case DASHBOARD_MONTH_REPORT_CHART = 'Month Report Chart';
    case DASHBOARD_PROJECT_ACTIVITIES = 'Project activities';
    case DASHBOARD_FILTERING = 'filtering';

    // ===================== Log System =====================
    case LOG_VIEW = 'View Activities';
    case LOG_DELETE = 'delete Activities';
}