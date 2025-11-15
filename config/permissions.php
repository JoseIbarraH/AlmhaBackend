<?php

return [

    // Permisos para team_member
    'team_member' => [
        'list_teamMember'           => 'view_teams',
        'get_teamMember'            => 'view_teams',
        'create_teamMember'         => 'create_teams',
        'update_teamMember'         => 'update_teams',
        'delete_teamMember'         => 'delete_teams',
        'update_status'             => 'update_teams_status'
    ],
    'blog' => [
        'list_blog'                 => 'view_blogs',
        'get_blog'                  => 'view_blogs',
        'create_blog'               => 'create_blogs',
        'update_blog'               => 'update_blogs',
        'delete_blog'               => 'delete_blogs',
        'update_status'             => 'update_blogs_status'
    ],
    'service' => [
        'list_service'              => 'view_services',
        'get_service'               => 'view_services',
        'create_service'            => 'create_services',
        'update_service'            => 'update_services',
        'delete_service'            => 'delete_services',
        'update_status'             => 'update_services_status'
    ]

];
