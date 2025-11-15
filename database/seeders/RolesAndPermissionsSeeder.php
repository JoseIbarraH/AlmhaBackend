<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RoleTranslation;
use App\Models\PermissionTranslation;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_users',
            'create_users',
            'update_users',
            'delete_users',
            'update_users_status',

            'view_roles',
            'create_roles',
            'update_roles',
            'delete_roles',

            'view_permissions',
            'assign_permissions',

            'view_services',
            'create_services',
            'update_services',
            'delete_services',
            'update_services_status',

            'view_blogs',
            'create_blogs',
            'update_blogs',
            'delete_blogs',
            'update_blogs_status',

            'view_teams',
            'create_teams',
            'update_teams',
            'delete_teams',
            'update_teams_status',

            'view_reports',
            'delete_reports',

            'view_design',
            'update_design',

            'view_profile',
            'update_profile',

            'view_dashboard_limited',
        ];

        $translations = [
            'es' => [
                'view_users' => ['title' => 'Ver usuarios', 'description' => 'Ver la lista de usuarios registrados'],
                'create_users' => ['title' => 'Crear usuarios', 'description' => 'Crear nuevos usuarios en el sistema'],
                'update_users' => ['title' => 'Editar usuarios', 'description' => 'Editar información de usuarios existentes'],
                'delete_users' => ['title' => 'Eliminar usuarios', 'description' => 'Eliminar usuarios del sistema'],
                'update_users_status' => ['title' => 'Actualizar estado de usuarios', 'description' => 'Activar o desactivar usuarios del sistema'],

                'view_roles' => ['title' => 'Ver roles', 'description' => 'Ver los roles existentes'],
                'create_roles' => ['title' => 'Crear roles', 'description' => 'Crear nuevos roles de usuario'],
                'update_roles' => ['title' => 'Editar roles', 'description' => 'Editar roles existentes'],
                'delete_roles' => ['title' => 'Eliminar roles', 'description' => 'Eliminar roles del sistema'],

                'view_permissions' => ['title' => 'Ver permisos', 'description' => 'Ver la lista de permisos disponibles'],
                'assign_permissions' => ['title' => 'Asignar permisos', 'description' => 'Asignar permisos a roles'],

                'view_services' => ['title' => 'Ver servicios', 'description' => 'Ver servicios creados en el sistema'],
                'create_services' => ['title' => 'Registrar servicios', 'description' => 'Registrar nuevos servicios'],
                'update_services' => ['title' => 'Editar servicios', 'description' => 'Editar información de los servicios'],
                'delete_services' => ['title' => 'Eliminar servicios', 'description' => 'Eliminar servicios del sistema'],
                'update_services_status' => ['title' => 'Actualizar estado de servicios', 'description' => 'Activar o desactivar servicios'],

                'view_blogs' => ['title' => 'Ver blogs', 'description' => 'Ver publicaciones del blog'],
                'create_blogs' => ['title' => 'Registrar blogs', 'description' => 'Crear nuevas publicaciones en el blog'],
                'update_blogs' => ['title' => 'Editar blogs', 'description' => 'Editar publicaciones del blog'],
                'delete_blogs' => ['title' => 'Eliminar blogs', 'description' => 'Eliminar publicaciones del blog'],
                'update_blogs_status' => ['title' => 'Actualizar estado de blogs', 'description' => 'Publicar o despublicar entradas del blog'],

                'view_teams' => ['title' => 'Ver equipo', 'description' => 'Ver miembros del equipo'],
                'create_teams' => ['title' => 'Registrar equipo', 'description' => 'Agregar nuevos miembros al equipo'],
                'update_teams' => ['title' => 'Editar equipo', 'description' => 'Editar información de miembros del equipo'],
                'delete_teams' => ['title' => 'Eliminar equipo', 'description' => 'Eliminar miembros del equipo'],
                'update_teams_status' => ['title' => 'Actualizar estado del equipo', 'description' => 'Activar o desactivar miembros del equipo'],

                'view_reports' => ['title' => 'Ver reportes', 'description' => 'Ver reportes del sistema'],
                'delete_reports' => ['title' => 'Eliminar reportes', 'description' => 'Eliminar reportes del sistema'],

                'view_design' => ['title' => 'Ver diseño', 'description' => 'Acceder a la configuración de diseño del sitio'],
                'update_design' => ['title' => 'Editar diseño', 'description' => 'Editar configuración de diseño del sitio'],

                'view_profile' => ['title' => 'Ver perfil', 'description' => 'Ver información del perfil personal'],
                'update_profile' => ['title' => 'Actualizar perfil', 'description' => 'Actualizar información del perfil personal'],

                'view_dashboard_limited' => ['title' => 'Ver dashboard limitado', 'description' => 'Acceso limitado al panel de control'],
            ],
            'en' => [
                'view_users' => ['title' => 'View users', 'description' => 'View the list of registered users'],
                'create_users' => ['title' => 'Create users', 'description' => 'Create new users in the system'],
                'update_users' => ['title' => 'Edit users', 'description' => 'Edit existing user information'],
                'delete_users' => ['title' => 'Delete users', 'description' => 'Delete users from the system'],
                'update_users_status' => ['title' => 'Update users status', 'description' => 'Activate or deactivate users in the system'],

                'view_roles' => ['title' => 'View roles', 'description' => 'View existing roles'],
                'create_roles' => ['title' => 'Create roles', 'description' => 'Create new user roles'],
                'update_roles' => ['title' => 'Edit roles', 'description' => 'Edit existing roles'],
                'delete_roles' => ['title' => 'Delete roles', 'description' => 'Delete roles from the system'],

                'view_permissions' => ['title' => 'View permissions', 'description' => 'View the list of available permissions'],
                'assign_permissions' => ['title' => 'Assign permissions', 'description' => 'Assign permissions to roles'],

                'view_services' => ['title' => 'View services', 'description' => 'View created services'],
                'create_services' => ['title' => 'Register services', 'description' => 'Register new services'],
                'update_services' => ['title' => 'Edit services', 'description' => 'Edit service information'],
                'delete_services' => ['title' => 'Delete services', 'description' => 'Delete services from the system'],
                'update_services_status' => ['title' => 'Update services status', 'description' => 'Activate or deactivate services'],

                'view_blogs' => ['title' => 'View blogs', 'description' => 'View blog posts'],
                'create_blogs' => ['title' => 'Register blogs', 'description' => 'Create new blog posts'],
                'update_blogs' => ['title' => 'Edit blogs', 'description' => 'Edit blog posts'],
                'delete_blogs' => ['title' => 'Delete blogs', 'description' => 'Delete blog posts'],
                'update_blogs_status' => ['title' => 'Update blogs status', 'description' => 'Publish or unpublish blog entries'],

                'view_teams' => ['title' => 'View team', 'description' => 'View team members'],
                'create_teams' => ['title' => 'Register team', 'description' => 'Add new team members'],
                'update_teams' => ['title' => 'Edit team', 'description' => 'Edit team member information'],
                'delete_teams' => ['title' => 'Delete team', 'description' => 'Remove team members'],
                'update_teams_status' => ['title' => 'Update team status', 'description' => 'Activate or deactivate team members'],

                'view_reports' => ['title' => 'View reports', 'description' => 'View system reports'],
                'delete_reports' => ['title' => 'Delete reports', 'description' => 'Delete system reports'],

                'view_design' => ['title' => 'View design', 'description' => 'Access site design configuration'],
                'update_design' => ['title' => 'Edit design', 'description' => 'Edit site design configuration'],

                'view_profile' => ['title' => 'View profile', 'description' => 'View personal profile information'],
                'update_profile' => ['title' => 'Update profile', 'description' => 'Update personal profile information'],

                'view_dashboard_limited' => ['title' => 'View limited dashboard', 'description' => 'Limited access to the control panel'],
            ],
        ];

        foreach ($permissions as $code) {
            $perm = Permission::firstOrCreate(['code' => $code]);
            foreach ($translations as $lang => $data) {
                $t = $data[$code] ?? ['title' => $code, 'description' => $code];
                PermissionTranslation::updateOrCreate(
                    ['permission_id' => $perm->id, 'lang' => $lang],
                    ['title' => $t['title'], 'description' => $t['description']]
                );
            }
        }

        $roles = [
            'super_admin' => [
                'es' => ['title' => 'Súper Administrador', 'description' => 'Acceso total al sistema'],
                'en' => ['title' => 'Super Administrator', 'description' => 'Full system access'],
                'permissions' => $permissions
            ],
            'user' => [
                'es' => ['title' => 'Usuario', 'description' => 'Acceso a su perfil personal'],
                'en' => ['title' => 'User', 'description' => 'Access to personal profile'],
                'permissions' => ['view_profile', 'update_profile', 'view_dashboard_limited']
            ],
            'design' => [
                'es' => ['title' => 'Diseñador', 'description' => 'Acceso al diseño'],
                'en' => ['title' => 'Designer', 'description' => 'Access to the design'],
                'permissions' => ['view_design', 'update_design']
            ],
            'team' => [
                'es' => ['title' => 'Equipo', 'description' => 'Acceso al módulo de equipo'],
                'en' => ['title' => 'Team', 'description' => 'Access to the team module'],
                'permissions' => ['view_teams', 'create_teams', 'update_teams', 'delete_teams', 'update_teams_status']
            ],
            'blog' => [
                'es' => ['title' => 'Blog', 'description' => 'Acceso al módulo de blog'],
                'en' => ['title' => 'Blog', 'description' => 'Access to the blog module'],
                'permissions' => ['view_blogs', 'create_blogs', 'update_blogs', 'delete_blogs', 'update_blogs_status']
            ],
            'service' => [
                'es' => ['title' => 'Servicios', 'description' => 'Acceso al módulo de servicios'],
                'en' => ['title' => 'Services', 'description' => 'Access to the services module'],
                'permissions' => ['view_services', 'create_services', 'update_services', 'delete_services', 'update_services_status']
            ],
        ];

        foreach ($roles as $code => $data) {
            // Crear o actualizar rol correctamente
            $role = Role::firstOrCreate(
                ['code' => $code],
                ['status' => 'active']
            );

            foreach (['es', 'en'] as $lang) {
                RoleTranslation::updateOrCreate(
                    ['role_id' => $role->id, 'lang' => $lang],
                    ['title' => $data[$lang]['title'], 'description' => $data[$lang]['description']]
                );
            }

            $permIds = Permission::whereIn('code', $data['permissions'])->pluck('id')->toArray();

            $role->permissions()->sync($permIds);
        }

        echo "✅ Roles y permisos creados correctamente con títulos y descripciones multilenguaje.\n";
    }
}
