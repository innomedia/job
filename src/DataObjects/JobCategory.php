<?php

namespace Job;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\TextField;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class JobCategory extends DataObject
{
    public function Link()
    {
        return $this->JobsPage()->Link() . "?cat=" . $this->ID;
    }

    private static string $table_name = 'JobCategory';
    
    /**
     * Belongs_many_many relationship
     */
    private static array $belongs_many_many = [
        'Job' => Job::class,
    ];
    
    /**
     * Has_one relationship
     */
    private static array $has_one = [
        'JobsPage' => JobsPage::class,
    ];
    
    /**
     * Database fields
     */
    private static array $db = [
        'Title' => 'Text',
        'TagSortTitle' => 'Text',
        'Sort' => 'Int',
        'URLSegment' => 'Varchar(255)'
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'TagSortTitle',
            'Sort',
            'JobsPageID',
            'Job'
        ]);
        $fields->addFieldsToTab(
            'Root.Main',
            [
                TextField::create(
                    'Title',
                    'Titel'
                )
            ]
        );
        return $fields;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();
        if ($this->URLSegment == "") {
            $this->URLSegment = $this->constructURLSegment();
        }
        
        $this->TagSortTitle = $this->Title;
        $this->extend("updateOnBeforeWrite");
    }

    private function constructURLSegment(): string|array|null
    {
        return $this->cleanLink(strtolower(str_replace(" ", "-", $this->Title)));
    }

    private function cleanLink($string): string|array|null
    {
        $string = str_replace("ä", "ae", $string);
        $string = str_replace("ü", "ue", $string);
        $string = str_replace("ö", "oe", $string);
        $string = str_replace("Ä", "Ae", $string);
        $string = str_replace("Ü", "Ue", $string);
        $string = str_replace("Ö", "Oe", $string);
        $string = str_replace("ß", "ss", $string);
        $string = str_replace(["´", ",", ":", ";"], "", $string);
        $string = str_replace(["´", ",", ":", ";"], "", $string);
        $string = str_replace(["/", "(", ")"], "_", $string);
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    }
}
