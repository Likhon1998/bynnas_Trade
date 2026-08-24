<?php

namespace App\Support;

class PermissionCatalog
{
    /**
     * Module => [permission_suffix => label]
     *
     * @return array<string, array<string, string>>
     */
    public static function modules(): array
    {
        return [
            'users' => [
                'view' => 'View users',
                'create' => 'Create users',
                'edit' => 'Edit users',
                'delete' => 'Delete users',
                'activate' => 'Activate / deactivate users',
                'reset_password' => 'Reset credentials',
            ],
            'roles' => [
                'view' => 'View roles',
                'create' => 'Create roles',
                'edit' => 'Edit roles',
                'delete' => 'Delete roles',
                'assign' => 'Assign roles to users',
            ],
            'permissions' => [
                'view' => 'View permissions',
                'manage' => 'Manage permission assignments',
            ],
            'audit' => [
                'view' => 'View audit logs',
            ],
            'shops' => [
                'view' => 'View shops',
                'create' => 'Create shops',
                'edit' => 'Edit shops',
                'delete' => 'Delete shops',
                'approve' => 'Approve shops',
                'manage_credentials' => 'Manage shop credentials',
            ],
            'categories' => [
                'view' => 'View categories',
                'create' => 'Create categories',
                'edit' => 'Edit categories',
                'delete' => 'Delete categories',
            ],
            'price_groups' => [
                'view' => 'View price groups',
                'manage' => 'Manage price groups and product prices',
            ],
            'salesmen' => [
                'view' => 'View salesmen',
                'create' => 'Create salesmen',
                'edit' => 'Edit salesmen',
                'delete' => 'Delete salesmen',
            ],
            'visits' => [
                'view' => 'View shop visits',
                'create' => 'Record shop visits',
                'edit' => 'Edit shop visits',
            ],
            'orders' => [
                'view' => 'View orders',
                'create' => 'Create orders',
                'edit' => 'Edit orders',
                'approve' => 'Approve orders',
                'reject' => 'Reject orders',
                'cancel' => 'Cancel orders',
                'delete' => 'Delete orders',
                'export' => 'Export orders',
            ],
            'products' => [
                'view' => 'View products',
                'create' => 'Create products',
                'edit' => 'Edit products',
                'delete' => 'Delete products',
                'manage_price' => 'Manage product prices',
                'manage_stock' => 'Manage product stock',
                'import' => 'Import products',
                'export' => 'Export products',
            ],
            'inventory' => [
                'view' => 'View inventory',
                'receive' => 'Receive stock',
                'adjust' => 'Adjust stock',
                'transfer' => 'Transfer stock',
            ],
            'warehouses' => [
                'view' => 'View warehouses',
                'create' => 'Create warehouses',
                'edit' => 'Edit warehouses',
                'delete' => 'Delete warehouses',
            ],
            'fulfilment' => [
                'view' => 'View fulfilment queue',
                'pick' => 'Pick orders',
                'pack' => 'Pack orders',
                'dispatch' => 'Dispatch orders',
            ],
            'shipments' => [
                'view' => 'View shipments',
                'create' => 'Create shipments',
                'edit' => 'Edit shipments',
                'delete' => 'Delete shipments',
                'receive' => 'Receive shipments',
            ],
            'suppliers' => [
                'view' => 'View suppliers',
                'create' => 'Create suppliers',
                'edit' => 'Edit suppliers',
                'delete' => 'Delete suppliers',
            ],
            'purchases' => [
                'view' => 'View purchases',
                'create' => 'Create purchases',
                'edit' => 'Edit purchases',
                'delete' => 'Delete purchases',
            ],
            'invoices' => [
                'view' => 'View invoices',
                'create' => 'Create invoices',
                'edit' => 'Edit invoices',
                'export' => 'Export invoices',
            ],
            'payments' => [
                'view' => 'View payments',
                'create' => 'Create payments',
                'verify' => 'Verify payments',
                'delete' => 'Delete payments',
            ],
            'returns' => [
                'view' => 'View returns',
                'create' => 'Create returns',
                'approve' => 'Approve returns',
                'reject' => 'Reject returns',
            ],
            'deliveries' => [
                'view' => 'View deliveries',
                'assign' => 'Assign deliveries',
                'update_status' => 'Update delivery status',
            ],
            'targets' => [
                'view' => 'View targets',
                'manage' => 'Manage targets',
            ],
            'commissions' => [
                'view' => 'View commissions',
                'manage' => 'Manage commissions',
                'approve' => 'Approve commission payouts',
            ],
            'rewards' => [
                'view' => 'View rewards',
                'manage' => 'Manage rewards',
                'approve' => 'Approve / award rewards',
            ],
            'reports' => [
                'view' => 'View reports',
                'export' => 'Export reports',
            ],
            'analytics' => [
                'view' => 'View analytics',
            ],
            'settings' => [
                'view' => 'View settings',
                'manage' => 'Manage settings',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $permissions = [];

        foreach (self::modules() as $module => $actions) {
            foreach (array_keys($actions) as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return $permissions;
    }

    public static function label(string $permission): string
    {
        [$module, $action] = array_pad(explode('.', $permission, 2), 2, null);

        return self::modules()[$module][$action] ?? $permission;
    }
}
