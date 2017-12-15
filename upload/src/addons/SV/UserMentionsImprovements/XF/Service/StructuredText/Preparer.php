<?php

namespace SV\UserMentionsImprovements\XF\Service\StructuredText;

class Preparer extends XFCP_Preparer
{
    protected $mentionedUserGroups = [];

    protected function filterFinalUserMentions($null, $string)
    {
        $string = parent::filterFinalUserMentions($null, $string);

        /** @var \SV\UserMentionsImprovements\XF\Str\Formatter $formatter */
        $formatter = $this->app->stringFormatter();
        /** @var \SV\UserMentionsImprovements\Str\UserGroupMentionFormatter $mentions */
        $mentions = $formatter->getUserGroupMentionFormatter();

        $string = $mentions->getMentionsStructuredText($string);
        $this->mentionedUserGroups = $mentions->getMentionedUserGroups();

        return $string;
    }

    public function getMentionedUserGroups()
    {
        return $this->mentionedUserGroups;
    }
}