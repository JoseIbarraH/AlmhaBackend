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
        'update_status' => 'update_blogs_status'
    ],
    'procedure' => [
        'list_procedure' => 'view_services',
        'get_procedure' => 'show_services',
        'create_procedure' => 'create_services',
        'update_procedure' => 'update_services',
        'delete_procedure' => 'delete_services',
        'update_status' => 'update_services_status'
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
    'trash' => [
        'list_trash' => 'view_trash',
        'stats_trash' => 'view_trash',
        'empty_trash' => 'delete_trash',
        'force_delete' => 'delete_trash',
        'restore_trash' => 'restore_trash'
    ]

];
