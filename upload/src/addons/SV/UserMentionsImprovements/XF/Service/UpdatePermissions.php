<?php

namespace SV\UserMentionsImprovements\XF\Service;



/**
 * Extends \XF\Service\UpdatePermissions
 */
class UpdatePermissions extends XFCP_UpdatePermissions
{
    protected $hadChanges = false;

    /**
     * @param \XF\Entity\Permission                                                                   $permission
     * @param mixed                                                                                   $value
     * @param \XF\Mvc\Entity\Entity|\XF\Entity\PermissionEntry|\XF\Entity\PermissionEntryContent|null $entry
     * @return null|\XF\Entity\PermissionEntry|\XF\Entity\PermissionEntry|\XF\Entity\PermissionEntryContent|\XF\Mvc\Entity\Entity
     * @throws \XF\PrintableException
     */
    protected function writeEntry(\XF\Entity\Permission $permission, $value, \XF\Mvc\Entity\Entity $entry = null)
    {
        if ($value == 'unset' || $value === '0' || $value === 0)
        {
            if ($entry)
            {
                $entry->delete();
            }

            return null;
        }

        if (!$entry)
        {
            if ($this->contentType)
            {
                /** @var \XF\Entity\PermissionEntryContent $entry */
                $entry = $this->em()->create('XF:PermissionEntryContent');
                $entry->content_type = $this->contentType;
                $entry->content_id = $this->contentId;
            }
            else
            {
                /** @var \XF\Entity\PermissionEntry $entry */
                $entry = $this->em()->create('XF:PermissionEntry');
            }

            $entry->permission_group_id = $permission->permission_group_id;
            $entry->permission_id = $permission->permission_id;
        }

        $entry->user_id = $this->user ? $this->user->user_id : 0;
        $entry->user_group_id = $this->userGroup ? $this->userGroup->user_group_id : 0;

        if ($permission->permission_type == 'integer')
        {
            $entry->permission_value = 'use_int';
            $entry->permission_value_int = intval($value);
        }
        else
        {
            $entry->permission_value = $value;
            $entry->permission_value_int = 0;
        }

        $entry->saveIfChanged($saved);
        if ($saved)
        {
            $this->hadChanges = true;
        }

        return $entry;
    }

    public function triggerCacheRebuild()
    {
        if (!$this->hadChanges)
        {
            return;
        }

        /** @var \XF\Repository\PermissionCombination $combinationRepo */
        $combinationRepo = $this->repository('XF:PermissionCombination');

        if ($this->userGroup)
        {
            $combinations = $combinationRepo->getPermissionCombinationsForUserGroup($this->userGroup->user_group_id);
            if (count($combinations) > 8)
            {
                $combinationIds = $combinations->pluckNamed('permission_combination_id');
                // too much to build inline
                $this->app->jobManager()->enqueueUnique('permissionRebuild', 'XF:PermissionRebuild', ['combinationIds' => $combinationIds]);

                return;
            }
        }

        parent::triggerCacheRebuild();
    }
}