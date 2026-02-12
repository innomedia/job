<?php

namespace Job;

use SilverStripe\Control\Controller;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\TextField;
use SilverStripe\TagField\TagField;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\HTMLEditor\HtmlEditorField;

/**
 * Description
 * @package silverstripe
 * @subpackage mysite
 */
class Job extends DataObject
{
    private static $singular_name = 'Stellenangebot';
    private static $plural_name = 'Stellenangebote';
    private static string $table_name = 'Job';

    private static array $db = [
        'Title' => 'Text',
        'TagSortTitle' => 'Text',
        'Content' => 'HTMLText',
        'Details' => 'HTMLText',
        'Sort' => 'Int',
        'URLSegment' => 'Varchar(255)'
    ];

    private static array $has_one = [
        'JobsPage' => JobsPage::class,
        'PDF' => File::class,
    ];

    private static array $many_many = [
        'JobCategories' => JobCategory::class,
    ];

    //private static $belongs_many_many = [
    //  'JobsPages' => JobsPage::class
    //];

    public function Link($action_ = null)
    {
        return Controller::join_links($this->JobsPage()->Link(), "job", $this->URLSegment);
    }

    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();
        if ($this->URLSegment == "") {
            $this->URLSegment = $this->constructURLSegment();
        }
        
        $this->TagSortTitle = $this->Title;
    }

    private function constructURLSegment(): string
    {
        $link = $this->cleanLink(strtolower($this->Title));
        $count = 0;

        // Stelle sicher, dass der Link eindeutig ist
        while (Job::get()->filter('URLSegment', $link . ($count > 0 ? '-' . $count : ''))->exists()) {
            ++$count;
        }

        return $link . ($count > 0 ? '-' . $count : '');
    }

    private function cleanLink($string): ?string
    {
        // Entferne führende und nachfolgende Leerzeichen
        $string = trim($string);

        $replacements = [
            " " => "-", "ä" => "ae", "ü" => "ue", "ö" => "oe",
            "Ä" => "Ae", "Ü" => "Ue", "Ö" => "Oe", "ß" => "ss",
            "´" => "", "," => "", ":" => "", ";" => "",
            "/" => "", "(" => "", ")" => ""
        ];

        // Entferne typische Geschlechtskennzeichnungen wie (m/w/d)
        $string = preg_replace('/\b(m\/w\/d|m\/w|w\/m|d|f|div)\b/i', '', $string);

        // Ersetze alle definierten Zeichen
        $string = strtr($string, $replacements);

        // Entferne alle unzulässigen Zeichen
        $string = preg_replace('/[^A-Za-z0-9\-_]/', '', $string);

        // Entferne abschließende Bindestriche
        $string = rtrim($string, '-');

        // Ersetze doppelte Bindestriche oder Unterstriche durch einen einzigen
        $string = preg_replace('/-{2,}/', '-', $string);

        return preg_replace('/_{2,}/', '_', $string);
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
