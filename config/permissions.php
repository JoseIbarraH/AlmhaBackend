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
    'service' => [
        'list_service' => 'view_services',
        'get_service' => 'show_services',
        'create_service' => 'create_services',
        'update_service' => 'update_services',
        'delete_service' => 'delete_services',
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
        'list_role' => 'view_roles',
        'create_role' => 'create_roles',
        'update_role' => 'update_roles',
        'delete_role' => 'delete_roles',
        'update_status' => 'update_roles_status'
    ],
    'user' => [
        'list_user' => 'view_users',
        'update_status' => 'update_users_status',
        'create_user' => 'create_users',
        'update_user' => 'update_users',
        'delete_user' => 'delete_users'
    ]

];
