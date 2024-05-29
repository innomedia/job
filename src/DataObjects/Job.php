<?php

namespace Job;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\Debug;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\TextField;
use SilverStripe\TagField\TagField;
use SilverStripe\Core\Config\Config;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\HTMLEditor\HtmlEditorField;

/**
 * Description
 * @package silverstripe
 * @subpackage mysite
 */
class Job extends DataObject
{

    private static $table_name = 'Job';

    private static $db = [
        'Title' => 'Text',
        'TagSortTitle' => 'Text',
        'Content' => 'HTMLText',
        'Details' =>  'HTMLText',
        'Sort' => 'Int',
        'URLSegment' => 'Varchar(255)'
    ];

    private static $has_one = [
        'JobsPage' => JobsPage::class,
        'PDF' => File::class,
    ];

    private static $many_many = [
        'JobCategories' => JobCategory::class,
    ];

    //private static $belongs_many_many = [
    //  'JobsPages' => JobsPage::class
    //];

    public function Link($action_ = null)
    {
        return Controller::join_links($this->JobsPage()->Link(), "job", $this->URLSegment);
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if($this->URLSegment == "")
        {
            $this->URLSegment = $this->constructURLSegment();
        }
        $this->TagSortTitle = $this->Title;
    }

    private function constructURLSegment()
    {
        return $this->cleanLink(strtolower(str_replace(" ", "-", $this->Title)));
    }

    private function cleanLink($string)
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
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
        return $string;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'TagSortTitle',
            'JobCategories',
        ]);
        $fields->addFieldsToTab(
            'Root.Main',
            [
                TextField::create(
                    'Title',
                    'Titel'
                ),
                HtmlEditorField::create(
                    'Content',
                    'Inhalt'
                ),
                HtmlEditorField::create(
                    'Details',
                    'Details (Beginn,Ort)'
                ),
            ]
        );
        if (Config::inst()->get("JobModuleConfig")["CategoriesEnabled"] != "" && Config::inst()->get("JobModuleConfig")["CategoriesEnabled"] == true) {
            $fields->addFieldToTab(
                'Root.Main',
                TagField::create(
                    'JobCategories',
                    'JobCategories',
                    JobCategory::get(),
                    $this->JobCategories()
                )->setShouldLazyLoad(true)->setCanCreate(false)->setTitleField("TagSortTitle")
            );
        }
        
        $this->extend('updateJobCMSFields', $fields);

        return $fields;
    }

}
