<?php

return [

    // Permisos para team_member
    'team_member' => [
        'list_teamMember' => 'view_teams',
        'get_teamMember' => 'show_teams',
        'create_teamMember' => 'create_teams',
        'update_teamMember' => 'update_teams',
        'delete_teamMember' => 'delete_teams',
        'update_status' => 'update_teams_status'
    ],
    'blog' => [
        'list_blog' => 'view_blogs',
        'get_blog' => 'show_blogs',
        'create_blog' => 'create_blogs',
        'update_blog' => 'update_blogs',
        'delete_blog' => 'delete_blogs',
        'update_status' => 'update_blogs_status',
        'upload_image' => 'create_blogs', // Asumiendo create o update
        'delete_image' => 'delete_blogs'
    ],
    'blogCategory' => [
        'list_categories' => 'update_blogs',
        'create_category' => 'update_blogs',
        'delete_category' => 'update_blogs',
        'update_category' => 'update_blogs'
    ],
    'procedure' => [
        'list_procedure' => 'view_procedures',
        'get_procedure' => 'show_procedures',
        'create_procedure' => 'create_procedures',
        'update_procedure' => 'update_procedures',
        'delete_procedure' => 'delete_procedures',
        'update_status' => 'update_procedures_status'
    ],
    'design' => [
        'get_design' => 'view_design',
        'create_item' => 'update_design',
        'update_item' => 'update_design',
        'delete_item' => 'update_design',
        'update_state' => 'update_design'
    ],
    'role' => [
        'list_role' => 'manage_users',
        'list_permission' => 'manage_users',
        'create_role' => 'manage_users',
        'update_role' => 'manage_users',
        'delete_role' => 'manage_users',
        'update_status' => 'manage_users'
    ],
    'user' => [
        'list_user' => 'manage_users',
        'update_status' => 'manage_users',
        'create_user' => 'manage_users',
        'update_user' => 'manage_users',
        'delete_user' => 'manage_users'
    ],
    'profile' => [
        'update_account' => null, // Generalmente el perfil propio no requiere permiso específico extra
        'change_password' => null,
        'destroy_account' => null
    ],
    'trash' => [
        'list_trash' => 'view_trash',
        'stats_trash' => 'view_trash',
        'empty_trash' => 'delete_trash',
        'force_delete' => 'delete_trash',
        'restore_trash' => 'restore_trash'
    ],
    'setting' => [
        'list_setting' => 'page_settings',
        'get_setting' => 'page_settings',
        'find_group' => 'page_settings',
        'update_settings' => 'page_settings'
    ],
    'audit' => [
        'list_audit' => 'view_reports'
    ]

];
