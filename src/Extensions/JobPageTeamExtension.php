<?php

namespace Job;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Extension;
use Team\DataObjects\TeamMember;

class JobPageTeamExtension extends Extension
{
    /**
     * Database fields
     */
    private static array $db = [
        'TeamID' => 'Int',
    ];

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Ansprechpartner',
            DropdownField::create(
                'TeamID',
                'Ansprechpartner',
                TeamMember::get()->map()
            )->setEmptyString('')
        );
        return $fields;
    }

    public function TeamMember()
    {
        return TeamMember::get()->byID($this->owner->TeamID);
    }
}
