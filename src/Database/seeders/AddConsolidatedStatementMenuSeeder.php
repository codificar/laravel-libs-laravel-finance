<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;

class AddConsolidatedStatementMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission = \Permission::where('url', '/admin/libs/finance/provider_extract')->first();

        if ($permission) {
            $permission1 = \Permission::updateOrCreate(
                ['name' => 'consolidated_statement'],
                [
                    'name' => 'consolidated_statement',
                    'parent_id' => $permission->parent_id,
                    'is_menu' => 1,
                    'order' => 603,
                    'icon' => 'mdi mdi-wallet',
                    'url' => '/admin/libs/finance/consolidated_extract'
                ]
            );

            $admins = \Admin::select('id','profile_id')->get();

            if($admins) {
                $findProfiles = array();

                foreach($admins as $admin) {
                    if ($admin->profile_id && !in_array($admin->profile_id, $findProfiles)) {
                        \ProfilePermission::updateOrCreate(
                            ['profile_id' => $admin->profile_id, 'permission_id' => $permission1->id],
                            ['profile_id' => $admin->profile_id, 'permission_id' => $permission1->id]
                        );
                    }
                }
            }
        }
    }
}
