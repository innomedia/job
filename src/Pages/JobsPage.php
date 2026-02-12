<?php

namespace Job;

use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\HTMLEditor\HtmlEditorField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridField;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use Page;
use SilverStripe\Core\Config\Config;
use SilverStripe\Assets\Image;
use Job\DataObjects\JobCircle;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;


class JobsPage extends Page
{
    private static string $table_name = 'JobsPage';
    
    private static string $singular_name = 'Job';
    
    private static string $plural_name = 'Jobs';
    
    private static string $description = 'Hiermit können Sie eine Jobseite erstellen - Jobs werden in dem Modul direkt gepflegt';

    private static array $db = [
        'InitiativBewerbungsText' => 'HTMLText',
        'FragenText' => 'HTMLText',
        'BewerbungsMail' => 'Text'
    ];

    private static array $has_one = [
        'InitiativBewerbungsImage' => Image::class,
    ];

    private static array $has_many = [
        'Jobs' => Job::class,
        'JobCircle' => JobCircle::class,
        'JobCategories' => JobCategory::class,
    ];

    public function simpleSortedJobs()
    {
        return $this->Jobs()->Sort("Sort ASC");
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'WeitereLeistungen',
            'AngeboteneLeistung',
            'Gallerie',
            'Icon',
            'SecondContent',
            'Jobs',
            'JobCircle',
            'JobCategories',
        ]);

        $fields->addFieldToTab('Root', Tab::create('Jobs', 'Stellenanzeigen'));
        $fields->addFieldToTab('Root', Tab::create('Kategorie', 'Kategorien'));

        $fields->addFieldsToTab(
            'Root.Info',
            [
                HtmlEditorField::create(
                    'InitiativBewerbungsText',
                    'Initiativ Bewerbungs Text'
                ),
                HtmlEditorField::create(
                    'FragenText',
                    'Fragen Text'
                ),
                UploadField::create(
                    'InitiativBewerbungsImage',
                    'Bild'
                )
            ]
        );
        $fields->addFieldToTab(
            'Root.Jobs',
            TextField::create(
                'BewerbungsMail',
                'Email'
            )
        );
        $fields->addFieldToTab(
            'Root.Jobs',
            GridField::create(
                'Jobs',
                'Jobs',
                $this->Jobs()->sort("Sort"),
                GridFieldConfig_RelationEditor::create()->addComponent(new GridFieldOrderableRows("Sort"))
            )
        );


        if (Config::inst()->get("JobModuleConfig")["CirclesEnabled"] != "" && Config::inst()->get("JobModuleConfig")["CategoriesEnabled"] == true) {
            $fields->addFieldToTab(
                'Root.JobCircle',
                GridField::create(
                    'JobCircle',
                    'Job Infokreis',
                    $this->JobCircle(),
                    GridFieldConfig_RecordEditor::create()
                )
            );
        }

        if (Config::inst()->get("JobModuleConfig")["CategoriesEnabled"] != "" && Config::inst()->get("JobModuleConfig")["CategoriesEnabled"] == true) {
            $fields->addFieldToTab(
                'Root.Kategorie',
                GridField::create(
                    'JobCategories',
                    'Kategorien',
                    $this->JobCategories()->sort("Sort"),
                    GridFieldConfig_RelationEditor::create()->addComponent(new GridFieldOrderableRows("Sort"))
                )
            );
        }
        
        $this->extend('updateJobPageCMSFields', $fields);
        return $fields;
    }
}
