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
            'manage_users',

            'view_services',
            'show_services',
            'create_services',
            'update_services',
            'delete_services',
            'update_services_status',

            'view_blogs',
            'show_blogs',
            'create_blogs',
            'update_blogs',
            'delete_blogs',
            'update_blogs_status',

            'view_teams',
            'show_teams',
            'create_teams',
            'update_teams',
            'delete_teams',
            'update_teams_status',

            'view_reports',
            /* 'delete_reports', */

            'view_design',
            'update_design',

            'update_profile',
            'delete_profile',

            'view_trash',
            'delete_trash',
            'restore_trash',

            'view_dashboard',
        ];

        $translations = [
            'es' => [
                'manage_users' => ['title' => 'Administrador de usuarios', 'description' => 'Administrador de usuarios'],

                'view_services' => ['title' => 'Ver servicios', 'description' => 'Ver servicios creados en el sistema'],
                'show_services' => ['title' => 'Ver servicio especifico', 'description' => 'Ver servicios específico'],
                'create_services' => ['title' => 'Registrar servicios', 'description' => 'Registrar nuevos servicios'],
                'update_services' => ['title' => 'Editar servicios', 'description' => 'Editar información de los servicios'],
                'delete_services' => ['title' => 'Eliminar servicios', 'description' => 'Eliminar servicios del sistema'],
                'update_services_status' => ['title' => 'Actualizar estado de servicios', 'description' => 'Activar o desactivar servicios'],

                'view_blogs' => ['title' => 'Ver blogs', 'description' => 'Ver publicaciones del blog'],
                'show_blogs' => ['title' => 'Ver blog especifico', 'description' => 'Ver blog específico'],
                'create_blogs' => ['title' => 'Registrar blogs', 'description' => 'Crear nuevas publicaciones en el blog'],
                'update_blogs' => ['title' => 'Editar blogs', 'description' => 'Editar publicaciones del blog'],
                'delete_blogs' => ['title' => 'Eliminar blogs', 'description' => 'Eliminar publicaciones del blog'],
                'update_blogs_status' => ['title' => 'Actualizar estado de blogs', 'description' => 'Publicar o despublicar entradas del blog'],

                'view_teams' => ['title' => 'Ver equipo', 'description' => 'Ver miembros del equipo'],
                'show_teams' => ['title' => 'Ver miebro específico', 'description' => 'Ver miembro del equipo específico'],
                'create_teams' => ['title' => 'Registrar equipo', 'description' => 'Agregar nuevos miembros al equipo'],
                'update_teams' => ['title' => 'Editar equipo', 'description' => 'Editar información de miembros del equipo'],
                'delete_teams' => ['title' => 'Eliminar equipo', 'description' => 'Eliminar miembros del equipo'],
                'update_teams_status' => ['title' => 'Actualizar estado del equipo', 'description' => 'Activar o desactivar miembros del equipo'],

                'view_reports' => ['title' => 'Ver reportes', 'description' => 'Ver reportes del sistema'],
                /* 'delete_reports' => ['title' => 'Eliminar reportes', 'description' => 'Eliminar reportes del sistema'], */

                'view_design' => ['title' => 'Ver diseño', 'description' => 'Acceder a la configuración de diseño del sitio'],
                'update_design' => ['title' => 'Editar diseño', 'description' => 'Editar configuración de diseño del sitio'],

                'update_profile' => ['title' => 'Actualizar perfil', 'description' => 'Actualizar información del perfil personal'],
                'delete_profile' => ['title' => 'Eliminar perfil', 'description' => 'Eliminar perfil.'],

                'view_trash' => ['title' => 'Ver papelera', 'description' => 'Permite ver los elementos eliminados en la papelera'],
                'delete_trash' => ['title' => 'Eliminar de la papelera', 'description' => 'Permite eliminar permanentemente los elementos de la papelera'],
                'restore_trash' => ['title' => 'Restaurar desde la papelera', 'description' => 'Permite restaurar los elementos eliminados de la papelera'],

                'view_dashboard' => ['title' => 'Ver dashboard', 'description' => 'Acceso limitado al panel de control'],
            ],
            'en' => [
                'manage_users' => ['title' => 'User administrator', 'description' => 'User administrator'],

                'view_services' => ['title' => 'View services', 'description' => 'View created services'],
                'show_services' => ['title' => 'Show specific service', 'description' => 'Show specific service'],
                'create_services' => ['title' => 'Register services', 'description' => 'Register new services'],
                'update_services' => ['title' => 'Edit services', 'description' => 'Edit service information'],
                'delete_services' => ['title' => 'Delete services', 'description' => 'Delete services from the system'],
                'update_services_status' => ['title' => 'Update services status', 'description' => 'Activate or deactivate services'],

                'view_blogs' => ['title' => 'View blogs', 'description' => 'View blog posts'],
                'show_blogs' => ['title' => 'show specific blogs', 'description' => 'Show specific blog'],
                'create_blogs' => ['title' => 'Register blogs', 'description' => 'Create new blog posts'],
                'update_blogs' => ['title' => 'Edit blogs', 'description' => 'Edit blog posts'],
                'delete_blogs' => ['title' => 'Delete blogs', 'description' => 'Delete blog posts'],
                'update_blogs_status' => ['title' => 'Update blogs status', 'description' => 'Publish or unpublish blog entries'],

                'view_teams' => ['title' => 'View team', 'description' => 'View team members'],
                'show_teams' => ['title' => 'Show specific member', 'description' => 'Show specific member'],
                'create_teams' => ['title' => 'Register team', 'description' => 'Add new team members'],
                'update_teams' => ['title' => 'Edit team', 'description' => 'Edit team member information'],
                'delete_teams' => ['title' => 'Delete team', 'description' => 'Remove team members'],
                'update_teams_status' => ['title' => 'Update team status', 'description' => 'Activate or deactivate team members'],

                'view_reports' => ['title' => 'View reports', 'description' => 'View system reports'],
                /* 'delete_reports' => ['title' => 'Delete reports', 'description' => 'Delete system reports'], */

                'view_design' => ['title' => 'View design', 'description' => 'Access site design configuration'],
                'update_design' => ['title' => 'Edit design', 'description' => 'Edit site design configuration'],

                'update_profile' => ['title' => 'Update profile', 'description' => 'Update personal profile information'],
                'delete_profile' => ['title' => 'Delete profile', 'description' => 'Delete profile'],

                'view_trash' => ['title' => 'View Trash', 'description' => 'Allows viewing deleted items in the trash'],
                'delete_trash' => ['title' => 'Delete from Trash', 'description' => 'Allows permanently deleting items from the trash'],
                'restore_trash' => ['title' => 'Restore from Trash', 'description' => 'Allows restoring deleted items from the trash'],

                'view_dashboard' => ['title' => 'View dashboard', 'description' => 'Limited access to the control panel'],
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
            'user_administrator' => [
                'es' => ['title' => 'Usuario', 'description' => 'Acceso a su perfil personal'],
                'en' => ['title' => 'User', 'description' => 'Access to personal profile'],
                'permissions' => ['manage_users']
            ],
            'design' => [
                'es' => ['title' => 'Diseñador', 'description' => 'Acceso al diseño'],
                'en' => ['title' => 'Designer', 'description' => 'Access to the design'],
                'permissions' => ['view_design', 'update_design']
            ],
            'team' => [
                'es' => ['title' => 'Equipo', 'description' => 'Acceso al módulo de equipo'],
                'en' => ['title' => 'Team', 'description' => 'Access to the team module'],
                'permissions' => ['view_teams', 'show_teams', 'create_teams', 'update_teams', 'delete_teams', 'update_teams_status']
            ],
            'blog' => [
                'es' => ['title' => 'Blog', 'description' => 'Acceso al módulo de blog'],
                'en' => ['title' => 'Blog', 'description' => 'Access to the blog module'],
                'permissions' => ['view_blogs', 'show_blos', 'create_blogs', 'update_blogs', 'delete_blogs', 'update_blogs_status']
            ],
            'service' => [
                'es' => ['title' => 'Servicios', 'description' => 'Acceso al módulo de servicios'],
                'en' => ['title' => 'Services', 'description' => 'Access to the services module'],
                'permissions' => ['view_services', 'show_services', 'create_services', 'update_services', 'delete_services', 'update_services_status']
            ],
            'record' => [
                'es' => ['title' => 'Registro', 'description' => 'Acceso al módulo de registros'],
                'en' => ['title' => 'Record', 'description' => 'Access to the records module'],
                'permissions' => ['view_reports', 'delete_reports']
            ],
            'default' => [
                'es' => ['title' => 'Default', 'description' => 'Rol default'],
                'en' => ['title' => 'Default', 'description' => 'Default role'],
                'permissions' => ['view_dashboard']
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

        echo "Roles y permisos creados correctamente con títulos y descripciones multilenguaje.\n";
    }
}
