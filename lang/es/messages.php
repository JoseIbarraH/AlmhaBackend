<?php
return [
    'service' => [
        'success' => [
            'listServices' => 'Lista de servicios obtenida correctamente',
            'getService' => 'Servicio obtenido correctamente',
            'createService' => 'Servicio creado correctamente',
            'updateService' => 'Servicio actualizado correctamente',
            'deleteService' => 'Servicio eliminado correctamente',
            'updateStatus' => 'Estado actualizado correctamente'
        ],
        'error' => [
            'listServices' => 'Error al obtener los servicios',
            'getService' => 'Error al obtener el servicio',
            'createService' => 'Error al crear el servicio',
            'updateService' => 'Error al actualizar el servicio',
            'deleteService' => 'Error al eliminar el servicio',
            'updateStatus' => 'Error al actualizar el estado'
        ]
    ],
    'blog' => [
        'success' => [
            'listBlogs' => 'Lista de blogs obtenida correctamente',
            'getBlog' => 'Blog obtenido correctamente',
            'createBlog' => 'Blog inicial creado correctamente',
            'updateBlog' => 'Blog actualizado correctamente',
            'deleteBlog' => 'Blog eliminado correctamente',
            'updateStatus' => 'Estado actualizado correctamente',
            'getCategories' => 'Categorias obtenidas correctamente'
        ],
        'error' => [
            'listBlogs' => 'Error al obtener los blogs',
            'getBlog' => 'Error al obtener el blog',
            'createBlog' => 'Error al crear el blog inicial',
            'updateBlog' => 'Error al actualizar el blog',
            'deleteBlog' => 'Error al eliminar el blog',
            'updateStatus' => 'Error al actualizar el estado',
            'uploadImage' => 'Error al subir la imagen',
            'deleteImage' => 'Imagen no encontrada',
            'getCategories' => 'Error al obtener las categorias'
        ]
    ],
    'teamMember' => [
        'success' => [
            'list_teamMember' => 'Lista de miembros del equipo obtenida correctamente',
            'getTeamMember' => 'Miembro del equipo obtenido correctamente',
            'create_teamMember' => 'Miembro del equipo creado correctamente',
            'update_teamMember' => 'Miembro del equipo actualizado correctamente',
            'delete_teamMember' => 'Miembro del equipo eliminado correctamente',
            'updateStatus' => 'Estado del miembro del equipo actualizado correctamente',
        ],
        'error' => [
            'list_teamMember' => 'Error al obtener la lista de miembros del equipo',
            'create_teamMember' => 'Error al crear miembro del equipo',
            'update_teamMember' => 'Error al actualizar al miembro del equipo',
            'delete_teamMember' => 'Error al eliminiar miembro del equipo',
            'updateStatus' => 'Error al actualizar el estado del miembro del equipo',
            'getTeamMember' => 'Error al obtener miembro del equipo'
        ]
    ],
    'design' => [
        'success' => [
            'createItem' => 'Item creado correctamente',
            'updateItem' => 'Iteam actualizado correctamente',
            'getDesign' => 'Diseños obtenidos correctamente',
            'updateState' => 'Seleccion entre carrusel e imagen realizada correctamente'
        ],
        'error' => [
            'createItem' => 'Error al crear item',
            'updateItem' => 'Error al actualizar item',
            'getDesign' => 'Error al obtener los diseños',
            'updateState' => 'Error al seleccionar entre carrusel e imagen.'
        ]
    ],
    'profile' => [
        'success' => [
            'infoProfile' => 'Perfil actualizado correctamente',
            'updatePassword' => 'Contraseña actualizada correctamente',
            'deleteAccount' => 'Cuenta eliminada correctamente'
        ],
        'error' => [
            'updatePassword' => 'Error al actualizar la contraseña',
            'invalidPassword' => 'Contraseña incorrecta',
            'deleteAccount' => 'Error al eliminar la cuenta'
        ]
    ],
    'role' => [
        'success' => [
            'listRoles' => 'Lista de roles obtenida correctamente',
            'createRoles' => 'Rol creado correctamente',
            'updateRoles' => 'Rol actualizado correctamente',
            'updateStatus' => 'Estado del Rol actualizado correctamente',
            'deleteRole' => 'Rol eliminado correctamente',
            'listPermissions' => 'Lista de permisos obtenida correctamente'
        ],
        'error' => [
            'listRoles' => 'Error al obtener la lista de roles',
            'createRoles' => 'Error al crear el rol',
            'updateRoles' => 'Error al actualizar el rol',
            'updateStatus' => 'Error al actualizar el estado del rol',
            'deleteRole' => 'Error al eliminar el rol',
            'deleteAdminRole' => 'No puedes eliminar el rol Super Admin',
            'deleteUserRole' => 'No puedes eliminar el rol User',
            'updateAdminRole' => 'No puedes modificar el rol Super Admin',
            'updateStatusAdminRole' => 'No puedes deshabilitar el rol Super Admin',
            'listPermissions' => 'Error al obtener la list de permisos'
        ]
    ],
    'user' => [
        'success' => [
            'createUser' => 'Usuario creado correctamente',
            'updateStatus' => 'Estado actualizado correctamente',
            'deleteUser' => 'Usuario eliminado correctamente',
            'updateUser' => 'Usuario actualizado correctamente'
        ],
        'error' => [
            'createUser' => 'Error al crear el usuario',
            'updateStatus' => 'Error al actualizar el estado',
            'deleteUser' => 'Error al eliminar el usuario',
            'updateUser' => 'Error al actualizar el usuario'
        ]
    ],
    'permission' => [
        'success' => [
            'listPermissions' => 'Lista de permisos obtenida correctamente'
        ],
        'error' => [
            'listPermissions' => 'Error al obtener la lista de permisos'
        ]
    ]
];
